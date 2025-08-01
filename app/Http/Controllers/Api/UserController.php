<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::with('roles')->latest()->paginate();
        return UserResource::collection($users);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke all existing tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Password updated successfully. All sessions have been logged out.']);
    }

    /**
     * Change the authenticated user's name.
     */
    public function changeName(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($validated);

        return response()->json(['message' => 'Name updated successfully.']);
    }

    /**
     * Change the authenticated user's email.
     */
    public function changeEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
        ]);

        $request->user()->update($validated);

        return response()->json(['message' => 'Email updated successfully.']);
    }

    /**
     * Resend the email verification notification for a specific user.
     */
    public function resendVerificationEmail(Request $request, User $user)
    {
        $this->authorize('manage', $user);

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'User has already verified their email.'], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent successfully.']);
    }
}

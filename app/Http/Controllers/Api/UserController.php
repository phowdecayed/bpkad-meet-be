<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeEmailRequest;
use App\Http\Requests\User\ChangeNameRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $users = User::with('roles')->latest()->paginate();

        return UserResource::collection($users);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all existing tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Password updated successfully. All sessions have been logged out.']);
    }

    /**
     * Change the authenticated user's name.
     */
    public function changeName(ChangeNameRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json(['message' => 'Name updated successfully.']);
    }

    /**
     * Change the authenticated user's email.
     */
    public function changeEmail(ChangeEmailRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json(['message' => 'Email updated successfully.']);
    }

    /**
     * Resend the email verification notification for a specific user.
     */
    public function resendVerificationEmail(Request $request, User $user): JsonResponse
    {
        $this->authorize('manage', $user);

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'User has already verified their email.'], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent successfully.']);
    }
}

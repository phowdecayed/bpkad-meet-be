<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignPermissionRequest;
use App\Http\Requests\Role\RevokePermissionRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(Role::with('permissions')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): RoleResource
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web', // Explicitly set the guard
        ]);

        return new RoleResource($role);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role->update(['name' => $request->name]);

        return new RoleResource($role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(AssignPermissionRequest $request, Role $role): JsonResponse
    {
        $role->givePermissionTo($request->permission);

        return response()->json(['message' => 'Permission assigned successfully.']);
    }

    /**
     * Revoke a permission from a role.
     */
    public function revokePermission(RevokePermissionRequest $request, Role $role): JsonResponse
    {
        $role->revokePermissionTo($request->permission);

        return response()->json(['message' => 'Permission revoked successfully.']);
    }
}

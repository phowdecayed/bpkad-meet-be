<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Role::with('permissions')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web' // Explicitly set the guard
        ]);

        return response()->json($role, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return $role->load('permissions');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id
        ]);

        $role->update(['name' => $request->name]);

        return response()->json($role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(Request $request, Role $role)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        $role->givePermissionTo($request->permission);

        return response()->json(['message' => 'Permission assigned successfully.']);
    }

    /**
     * Revoke a permission from a role.
     */
    public function revokePermission(Request $request, Role $role)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        $role->revokePermissionTo($request->permission);

        return response()->json(['message' => 'Permission revoked successfully.']);
    }
}

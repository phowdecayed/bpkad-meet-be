<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define and create permissions
        $permissions = [
            'view meetings',
            'create meetings',
            'edit meetings',
            'delete meetings',
            'manage users',
            'manage roles',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->syncPermissions(['create meetings']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        // Create demo users
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Example User',
                'password' => Hash::make('password'),
            ]
        );
        $user->assignRole($userRole);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Example Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole($adminRole);
    }
}
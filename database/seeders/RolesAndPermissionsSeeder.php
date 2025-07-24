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

        // create permissions
        Permission::create(['name' => 'manage meetings']);
        Permission::create(['name' => 'delete meetings']);
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage roles']);

        // create roles and assign created permissions
        $userRole = Role::create(['name' => 'user'])
            ->givePermissionTo(['manage meetings']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // create demo users
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
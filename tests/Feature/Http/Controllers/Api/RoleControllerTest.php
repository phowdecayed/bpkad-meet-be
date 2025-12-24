<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create permissions needed for middleware
        Permission::create(['name' => 'manage roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit data', 'guard_name' => 'web']);
    }

    #[Test]
    public function admin_can_list_roles()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage roles');
        Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)->getJson('/api/roles');

        $response->assertSuccessful()
            ->assertJsonStructure([
                '*' => ['id', 'name', 'permissions'],
            ]);
    }

    #[Test]
    public function admin_can_create_role()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage roles');

        $response = $this->actingAs($user)->postJson('/api/roles', [
            'name' => 'new-role',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('roles', ['name' => 'new-role']);
    }

    #[Test]
    public function store_validates_request()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage roles');
        Role::create(['name' => 'existing-role', 'guard_name' => 'web']);

        $response = $this->actingAs($user)->postJson('/api/roles', [
            'name' => 'existing-role',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function admin_can_update_role()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage roles');
        $role = Role::create(['name' => 'old-name', 'guard_name' => 'web']);

        $response = $this->actingAs($user)->putJson("/api/roles/{$role->id}", [
            'name' => 'updated-name',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'updated-name']);
    }

    #[Test]
    public function admin_can_delete_role()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage roles');
        $role = Role::create(['name' => 'to-delete', 'guard_name' => 'web']);

        $response = $this->actingAs($user)->deleteJson("/api/roles/{$role->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    #[Test]
    public function admin_can_assign_permission_to_role()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage roles');
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'new-permission', 'guard_name' => 'web']);

        $response = $this->actingAs($user)->postJson("/api/roles/{$role->id}/permissions", [
            'permission' => 'new-permission',
        ]);

        $response->assertSuccessful();
        $this->assertTrue($role->fresh()->hasPermissionTo('new-permission'));
    }

    #[Test]
    public function admin_can_revoke_permission_from_role()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage roles');
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'test-permission', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $response = $this->actingAs($user)->deleteJson("/api/roles/{$role->id}/permissions", [
            'permission' => 'test-permission',
        ]);

        $response->assertSuccessful();
        $this->assertFalse($role->fresh()->hasPermissionTo('test-permission'));
    }
}

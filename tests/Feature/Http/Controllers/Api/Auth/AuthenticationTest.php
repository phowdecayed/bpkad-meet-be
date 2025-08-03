<?php

namespace Tests\Feature\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $basicUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $manageUsersPermission = Permission::create(['name' => 'manage users']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin'])->givePermissionTo($manageUsersPermission);
        $userRole = Role::create(['name' => 'user']);

        // Create users
        $this->adminUser = User::factory()->create()->assignRole($adminRole);
        $this->basicUser = User::factory()->create()->assignRole($userRole);
    }

    #[Test]
    public function admin_can_register_a_new_user_with_a_specific_role()
    {
        $newUserData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user',
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/api/register', $newUserData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                    'roles' => [
                        '*' => ['id', 'name'],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertTrue(User::whereEmail('test@example.com')->first()->hasRole('user'));
    }

    #[Test]
    public function non_admin_user_cannot_register_a_new_user()
    {
        $newUserData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user',
        ];

        $response = $this->actingAs($this->basicUser)->postJson('/api/register', $newUserData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'User does not have the right permissions.',
            ]);
    }

    #[Test]
    public function get_user_endpoint_returns_correct_resource_structure_with_roles_and_permissions()
    {
        // Ensure the admin role has multiple permissions for a thorough test
        $this->adminUser->roles->first()->givePermissionTo(Permission::create(['name' => 'test permission']));

        $response = $this->actingAs($this->adminUser)->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles' => [
                        '*' => [
                            'id',
                            'name',
                            'permissions' => [
                                '*' => ['id', 'name'],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonMissingPath('data.roles.0.pivot')
            ->assertJsonMissingPath('data.roles.0.guard_name')
            ->assertJsonMissingPath('data.roles.0.permissions.0.pivot');
    }
}

<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;

    protected User $basicUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $manageSettingsPermission = Permission::create(['name' => 'manage settings']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin'])->givePermissionTo($manageSettingsPermission);
        $userRole = Role::create(['name' => 'user']);

        // Create users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->basicUser = User::factory()->create();
        $this->basicUser->assignRole($userRole);
    }

    #[Test]
    public function admin_can_list_settings()
    {
        Setting::factory()->count(3)->create();
        $response = $this->actingAs($this->adminUser)->getJson('/api/settings');
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    #[Test]
    public function non_admin_cannot_list_settings()
    {
        $response = $this->actingAs($this->basicUser)->getJson('/api/settings');
        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_create_a_setting()
    {
        $data = [
            'name' => 'Test Setting',
            'group' => 'test',
            'payload' => ['foo' => 'bar'],
        ];
        $response = $this->actingAs($this->adminUser)->postJson('/api/settings', $data);
        $response->assertStatus(201)->assertJsonFragment(['name' => 'Test Setting']);
        $this->assertDatabaseHas('settings', [
            'name' => 'Test Setting',
            'group' => 'test',
            'payload' => json_encode(['foo' => 'bar']),
        ]);
    }

    #[Test]
    public function admin_can_update_a_setting()
    {
        $setting = Setting::factory()->create();
        $updateData = ['name' => 'Updated Name'];
        $response = $this->actingAs($this->adminUser)->patchJson("/api/settings/{$setting->id}", $updateData);
        $response->assertOk()->assertJsonFragment($updateData);
        $this->assertDatabaseHas('settings', $updateData);
    }

    #[Test]
    public function admin_can_delete_a_setting()
    {
        $setting = Setting::factory()->create();
        $response = $this->actingAs($this->adminUser)->deleteJson("/api/settings/{$setting->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('settings', ['id' => $setting->id]);
    }
}

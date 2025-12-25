<?php

namespace Tests\Feature\Http\Controllers\Api\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangeAvatarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_change_their_avatar()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->postJson('/api/user/change-avatar', [
            'avatar' => $file,
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure(['message', 'avatar_url']);

        // Verify file stored
        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    #[Test]
    public function old_avatar_is_deleted_when_replaced()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $oldAvatar = UploadedFile::fake()->image('old.jpg');
        $this->actingAs($user)->postJson('/api/user/change-avatar', ['avatar' => $oldAvatar]);

        $oldPath = $user->fresh()->avatar;
        Storage::disk('public')->assertExists($oldPath);

        $newAvatar = UploadedFile::fake()->image('new.jpg');
        $this->actingAs($user)->postJson('/api/user/change-avatar', ['avatar' => $newAvatar]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    #[Test]
    public function avatar_must_be_an_image()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/user/change-avatar', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }
}

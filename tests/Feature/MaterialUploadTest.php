<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MaterialUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_upload_material()
    {
        Storage::fake('public');

        $permission = Permission::create(['name' => 'edit meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('edit meetings');

        $meeting = Meeting::factory()->create(['organizer_id' => $user->id]);

        $file = UploadedFile::fake()->create('agenda.pdf', 100); // 100kb

        $this->actingAs($user);

        $response = $this->postJson("/api/meetings/{$meeting->id}/materials", [
            'file' => $file,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('meeting_materials', [
            'meeting_id' => $meeting->id,
            'original_name' => 'agenda.pdf',
        ]);

        // Check file exists in storage
        $material = MeetingMaterial::first();
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->assertExists($material->file_path);
    }

    public function test_organizer_can_delete_material()
    {
        Storage::fake('public');

        $permission = Permission::create(['name' => 'edit meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('edit meetings');

        $meeting = Meeting::factory()->create(['organizer_id' => $user->id]);

        // Manually create material
        $material = $meeting->materials()->create([
            'file_path' => 'materials/dummy.pdf',
            'original_name' => 'dummy.pdf',
            'file_type' => 'application/pdf',
        ]);
        // Put file in fake storage so delete works without error (if check exists)
        Storage::disk('public')->put('materials/dummy.pdf', 'content');

        $this->actingAs($user);

        $response = $this->deleteJson("/api/materials/{$material->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('meeting_materials', ['id' => $material->id]);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->assertMissing('materials/dummy.pdf');
    }
}

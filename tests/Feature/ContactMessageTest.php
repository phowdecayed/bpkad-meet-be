<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_contact_message()
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'This is a test message.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Message sent successfully!',
            ]);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'test@example.com',
        ]);
    }
}

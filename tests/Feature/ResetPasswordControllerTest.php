<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resets_the_password_with_a_valid_token()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(password_verify('new-password', $user->fresh()->password));
    }

    #[Test]
    public function it_returns_an_error_with_an_invalid_token()
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This password reset token is invalid.',
                'errors' => [
                    'token' => [
                        'This password reset token is invalid.'
                    ]
                ]
            ]);
    }
}

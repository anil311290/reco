<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_api_does_not_allow_email_update(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'name' => 'Owner',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', [
            'name' => 'Owner Updated',
            'phone' => '9999999999',
            'email' => 'hacked@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'owner@example.com')
            ->assertJsonPath('data.name', 'Owner Updated');

        $this->assertSame('owner@example.com', $user->fresh()->email);
        $this->assertSame('Owner Updated', $user->fresh()->name);
        $this->assertSame('9999999999', $user->fresh()->phone);
    }

    public function test_change_password_api_updates_password_with_new_password_fields(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old12345678'),
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/change-password', [
            'current_password' => 'old12345678',
            'new_password' => 'new12345678',
            'new_password_confirmation' => 'new12345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password changed successfully');

        $this->assertTrue(Hash::check('new12345678', $user->fresh()->password));
    }

    public function test_change_password_api_rejects_same_as_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('same123456'),
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/v1/change-password', [
            'current_password' => 'same123456',
            'new_password' => 'same123456',
            'new_password_confirmation' => 'same123456',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('new_password');
    }

    public function test_change_password_api_supports_legacy_password_fields(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('legacy12345'),
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/v1/change-password', [
            'current_password' => 'legacy12345',
            'password' => 'legacy67890',
            'password_confirmation' => 'legacy67890',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('legacy67890', $user->fresh()->password));
    }
}

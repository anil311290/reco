<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create default plans
        SubscriptionPlan::factory()->create(['slug' => 'trial', 'trial_days' => 14]);
        SubscriptionPlan::factory()->create(['slug' => 'basic', 'trial_days' => 0]);
    }

    public function test_user_registration_requires_pending_approval(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'plan_slug' => 'trial',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']]);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertNotNull($user);
        $this->assertEquals('pending', $user->status);
        
        // User cannot login while pending
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(422);
    }

    public function test_pending_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'pending@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_approved_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'approved@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'approved@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_company_is_created_on_registration(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'companytest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company Ltd',
            'plan_slug' => 'trial',
        ]);

        $company = Company::where('name', 'Test Company Ltd')->first();
        
        $this->assertNotNull($company);
        $this->assertFalse($company->is_active); // Should be inactive until approved
    }

    public function test_subscription_is_created_on_registration(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'subscriptiontest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Subscription Test Company',
            'plan_slug' => 'trial',
        ]);

        $subscription = Subscription::whereHas('company', function ($q) {
            $q->where('name', 'Subscription Test Company');
        })->first();

        $this->assertNotNull($subscription);
        $this->assertEquals('trial', $subscription->status);
    }

    public function test_financial_year_is_created_on_registration(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'finyeartest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'FinYear Test Company',
            'plan_slug' => 'trial',
        ]);

        $company = Company::where('name', 'FinYear Test Company')->first();
        $fy = FinancialYear::where('company_id', $company->id)->first();

        $this->assertNotNull($fy);
        $this->assertTrue($fy->is_current);
    }
}
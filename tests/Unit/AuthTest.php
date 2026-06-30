<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\FinancialYear;
use App\Services\AuthService;
use App\Interfaces\UserRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;
    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);

        $this->authService = $this->app->make(AuthService::class);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $result = $this->authService->login([
            'email' => $this->user->email,
            'password' => '12345678',
        ]);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($this->user->id, $result['user']->id);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->authService->login([
            'email' => $this->user->email,
            'password' => 'wrongpassword',
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->user->update(['status' => 'inactive']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->authService->login([
            'email' => $this->user->email,
            'password' => '12345678',
        ]);
    }

    public function test_pending_user_cannot_login(): void
    {
        $this->user->update(['status' => 'pending']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->authService->login([
            'email' => $this->user->email,
            'password' => '12345678',
        ]);
    }

    public function test_user_can_register(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => '12345678',
            'company_id' => $this->company->id,
        ];

        $user = $this->authService->register($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('New User', $user->name);
        $this->assertEquals('newuser@example.com', $user->email);
        $this->assertTrue(Hash::check('12345678', $user->password));
    }

    public function test_user_can_change_password(): void
    {
        $result = $this->authService->changePassword(
            $this->user,
            '12345678',
            'new12345678'
        );

        $this->assertTrue($result);
        $this->assertTrue(Hash::check('new12345678', $this->user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_wrong_current(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->authService->changePassword(
            $this->user,
            'wrongpassword',
            'new12345678'
        );
    }

    public function test_user_has_roles_and_permissions(): void
    {
        $this->assertTrue($this->user->isActive());
        $this->assertFalse($this->user->isAdmin());
    }

    public function test_user_belongs_to_company(): void
    {
        $this->assertNotNull($this->user->company);
        $this->assertEquals($this->company->id, $this->user->company->id);
    }

    public function test_user_has_pending_status_after_registration(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => '12345678',
            'company_name' => 'Test Company',
        ];

        $user = $this->authService->register($userData);

        $this->assertTrue($user->isPending());
    }
}
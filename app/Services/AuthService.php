<?php

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\FinancialYear;
use App\Models\SubscriptionPlan;
use App\Services\AccountService;
use App\Services\CompanyRoleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthService
{
    protected UserRepositoryInterface $userRepository;
    protected AccountService $accountService;
    protected CompanyRoleService $companyRoleService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        AccountService $accountService,
        CompanyRoleService $companyRoleService
    ) {
        $this->userRepository = $userRepository;
        $this->accountService = $accountService;
        $this->companyRoleService = $companyRoleService;
    }

    /**
     * Authenticate user and return token
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact administrator.'],
            ]);
        }

        // Update last login information
        $this->userRepository->updateLastLogin($user->id, request()->ip());

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Register new user with company and initial subscription
     */
    public function register(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['status'] = 'pending'; // Requires admin approval
        $data['role'] = 'admin'; // Company owner / tenant administrator

        $user = null;
        $company = null;

        DB::transaction(function () use (&$user, &$company, $data) {
            // Create company
            if (isset($data['company_name'])) {
                $company = Company::create([
                    'uuid' => Str::uuid(),
                    'name' => $data['company_name'],
                    'slug' => $this->uniqueCompanySlug($data['company_name']),
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'is_active' => false, // Disabled until admin approval
                    'created_by_ip' => request()->ip(),
                ]);

                $data['company_id'] = $company->id;

                $this->companyRoleService->provisionDefaultRoles($company);
            }

            // Create user
            $user = $this->userRepository->create($data);

            if ($company && $user) {
                $this->companyRoleService->assignCompanyOwner($user);
            }

            // Create initial subscription (trial by default)
            if ($company) {
                $planSlug = $data['plan_slug'] ?? 'trial';
                $plan = SubscriptionPlan::where('slug', $planSlug)->first();
                
                if ($plan) {
                    Subscription::create([
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'plan_id' => $plan->id,
                        'status' => 'trial',
                        'billing_cycle' => 'monthly',
                        'start_date' => now(),
                        'trial_end_date' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null,
                        'current_period_start' => now(),
                        'current_period_end' => now()->addDays($plan->trial_days > 0 ? $plan->trial_days : 30),
                        'amount' => 0,
                    ]);
                }

                // Create default financial year (April 1 to March 31 - Indian standard)
                $fyStart = $company->financial_year_start ?? '04-01';
                $fyEnd = $company->financial_year_end ?? '03-31';
                $startParts = explode('-', $fyStart);
                $endParts = explode('-', $fyEnd);

                $startDate = now()->month((int)$startParts[0])->day((int)$startParts[1]);
                $endDate = now()->month((int)$endParts[0])->day((int)$endParts[1]);
                if ($endDate->lte($startDate)) {
                    $endDate->addYear();
                }

                FinancialYear::create([
                    'company_id' => $company->id,
                    'name' => 'FY ' . date('Y') . '-' . date('y', strtotime('+1 year')),
                    'year_code' => 'FY' . date('Y'),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'is_current' => true,
                ]);

                $this->accountService->ensureDefaultLedgersAndCleanupDuplicates(
                    $company->id,
                    null,
                    $user->id ?? null,
                    request()->ip()
                );
            }
        });

        // Send admin notification email
        if ($user && $company) {
            try {
                Mail::to(config('admin.email', 'admin@example.com'))->queue(
                    new \App\Mail\CompanyPendingApproval($user, $company)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send admin notification: ' . $e->getMessage());
            }
        }

        if (!$user) {
            throw new \RuntimeException('Registration failed: user was not created.');
        }

        return $user;
    }

    private function uniqueCompanySlug(string $companyName): string
    {
        $baseSlug = Str::slug($companyName);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'company';
        $slug = $baseSlug;
        $suffix = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Logout user
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }
    }

    /**
     * Get authenticated user
     */
    public function getAuthenticatedUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $this->userRepository->update($user->id, $data);

        return $user->fresh();
    }

    /**
     * Change password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password is incorrect.'],
            ]);
        }

        return $this->userRepository->update($user->id, [
            'password' => Hash::make($newPassword),
        ]);
    }
}

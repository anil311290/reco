<?php

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\FinancialYear;
use App\Models\SubscriptionPlan;
use App\Models\RazorpayOrder;
use App\Services\AccountService;
use App\Services\CompanyRoleService;
use App\Services\RazorpayService;
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
    protected RazorpayService $razorpayService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        AccountService $accountService,
        CompanyRoleService $companyRoleService,
        RazorpayService $razorpayService
    ) {
        $this->userRepository = $userRepository;
        $this->accountService = $accountService;
        $this->companyRoleService = $companyRoleService;
        $this->razorpayService = $razorpayService;
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
     * Register new user with company and initial subscription.
     * For paid plans, creates company+user+pending subscription + Razorpay order,
     * then returns checkout data so the frontend can open the payment popup.
     * For free/trial plans, subscription is immediately activated as trial.
     */
    public function register(array $data): User|array
    {
        $data['password'] = Hash::make($data['password']);
        $data['status'] = 'pending';
        $data['role'] = 'admin';

        $user = null;
        $company = null;
        $plan = null;
        $checkoutData = null;

        DB::transaction(function () use (&$user, &$company, &$plan, &$checkoutData, $data) {
            // Create company
            if (isset($data['company_name'])) {
                $company = Company::create([
                    'uuid' => Str::uuid(),
                    'name' => $data['company_name'],
                    'slug' => $this->uniqueCompanySlug($data['company_name']),
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'is_active' => false,
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

            // Create subscription and handle payment if needed
            if ($company) {
                $planSlug = $data['plan_slug'] ?? 'trial';
                $plan = SubscriptionPlan::where('slug', $planSlug)->first();

                if ($plan) {
                    $monthly = (float) $plan->monthly_price;
                    $isFreePlan = $monthly <= 0;

                    // Create financial year and default ledgers (always needed)
                    $this->createDefaultFinancialYear($company);
                    $this->accountService->ensureDefaultLedgersAndCleanupDuplicates(
                        $company->id, null, $user->id ?? null, request()->ip()
                    );

                    if ($isFreePlan) {
                        // Free/trial plan → activate immediately
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
                    } else {
                        // Paid plan → create pending subscription + Razorpay order
                        $subscription = Subscription::create([
                            'uuid' => Str::uuid(),
                            'company_id' => $company->id,
                            'plan_id' => $plan->id,
                            'status' => 'pending_payment',
                            'billing_cycle' => 'monthly',
                            'start_date' => now(),
                            'trial_end_date' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null,
                            'current_period_start' => now(),
                            'current_period_end' => null,
                            'amount' => $monthly,
                        ]);

                        if (!$this->razorpayService->isConfigured()) {
                            throw new \RuntimeException('Online payment is not configured. Contact support or try a free plan.');
                        }

                        $receipt = 'reg_' . $company->id . '_' . time();
                        $gatewayOrder = $this->razorpayService->createOrder($monthly, $receipt, [
                            'company_id' => (string) $company->id,
                            'plan_id' => (string) $plan->id,
                            'billing_cycle' => 'monthly',
                            'action' => 'registration',
                        ]);

                        RazorpayOrder::create([
                            'uuid' => Str::uuid(),
                            'company_id' => $company->id,
                            'subscription_id' => $subscription->id,
                            'razorpay_order_id' => $gatewayOrder['id'],
                            'amount' => $monthly,
                            'currency' => $gatewayOrder['currency'] ?? 'INR',
                            'status' => 'created',
                            'notes' => [
                                'company_id' => $company->id,
                                'plan_id' => $plan->id,
                                'billing_cycle' => 'monthly',
                                'action' => 'registration',
                            ],
                            'gateway_response' => $gatewayOrder,
                            'receipt' => $receipt,
                            'created_by_ip' => request()->ip(),
                        ]);

                        $checkoutData = [
                            'key_id' => $this->razorpayService->getKeyId(),
                            'order_id' => $gatewayOrder['id'],
                            'amount_paise' => (int) round($monthly * 100),
                            'currency' => $gatewayOrder['currency'] ?? 'INR',
                            'description' => $plan->name . ' — Monthly subscription',
                            'plan_name' => $plan->name,
                            'user_name' => $data['name'],
                            'user_email' => $data['email'],
                            'user_phone' => $data['phone'] ?? '',
                        ];
                    }
                }
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

        // For paid plans, return checkout data instead of User
        if ($checkoutData) {
            return [
                'requires_payment' => true,
                'checkout' => $checkoutData,
            ];
        }

        return $user;
    }

    /**
     * Create default financial year for a company.
     */
    protected function createDefaultFinancialYear(Company $company): void
    {
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

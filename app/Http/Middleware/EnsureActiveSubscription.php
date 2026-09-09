<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->isSuperAdmin() || !$user->company_id || $this->isSubscriptionRoute($request)) {
            return $next($request);
        }

        $subscription = $this->subscriptionService->getActiveSubscription((int) $user->company_id);

        if ($subscription && !$subscription->isExpired()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription is inactive. Please renew your subscription to continue.',
                'code' => 'SUBSCRIPTION_INACTIVE',
            ], 402);
        }

        return redirect()
            ->route('admin.subscriptions.current')
            ->with('error', 'Your subscription is inactive. Please renew your subscription to continue.');
    }

    protected function isSubscriptionRoute(Request $request): bool
    {
        $routeName = (string) $request->route()?->getName();

        return str_starts_with($routeName, 'admin.subscriptions.')
            || str_starts_with($routeName, 'api.subscriptions.');
    }
}
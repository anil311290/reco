<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Blade::directive('istDate', function (string $expression) {
            return "<?php echo \\App\\Helpers\\DateHelper::formatDate($expression); ?>";
        });

        Blade::directive('istDateTime', function (string $expression) {
            return "<?php echo \\App\\Helpers\\DateHelper::formatDateTime($expression); ?>";
        });

        Blade::directive('drCr', function (string $expression) {
            return "<?php echo \\App\\Helpers\\BalanceHelper::drCr($expression); ?>";
        });

        // Directive: @permission('slug')
        Blade::if('permission', function (string $permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        // Directive: @role('slug')
        Blade::if('role', function (string $role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        // Directive: @anyrole('admin,manager')
        Blade::if('anyrole', function (string ...$roles) {
            return auth()->check() && auth()->user()->hasAnyRole($roles);
        });

        View::composer('layouts.app', function ($view) {
            if (!auth()->check()) {
                $view->with([
                    'headerNotifications' => collect(),
                    'headerUnreadCount' => 0,
                ]);

                return;
            }

            $notificationService = app(NotificationService::class);
            $userId = auth()->id();

            $view->with([
                'headerNotifications' => $notificationService->getForUser($userId, 8),
                'headerUnreadCount' => $notificationService->getUnreadCount($userId),
            ]);
        });
    }
}

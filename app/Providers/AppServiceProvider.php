<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
    }
}

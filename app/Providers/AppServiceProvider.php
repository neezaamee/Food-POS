<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SaaS\TenantContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        if (str_starts_with((string) config('app.url'), 'https://') || request()->header('X-Forwarded-Proto') === 'https' || request()->isSecure()) {
            URL::forceScheme('https');
        }

        // Dynamic Role-Based Access Control (RBAC) Gate
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin() || $user->isOwner()) {
                return true;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }
}

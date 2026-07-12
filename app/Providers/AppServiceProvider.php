<?php

namespace App\Providers;

use App\Models\Server;
use App\Policies\ServerPolicy;
use App\Services\ExecutionEngine\ExecutionEngine;
use App\Services\ExecutionEngine\ServerExecutionEngine;
use App\Services\SshService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SshService::class);
        $this->app->bind(ExecutionEngine::class, ServerExecutionEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Server::class, ServerPolicy::class);

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}

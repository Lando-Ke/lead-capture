<?php

namespace App\Providers;

use App\Contracts\LeadRepositoryInterface;
use App\Contracts\PlatformRepositoryInterface;
use App\Repositories\LeadRepository;
use App\Repositories\PlatformRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        $this->app->bind(PlatformRepositoryInterface::class, PlatformRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}

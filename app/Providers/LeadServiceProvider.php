<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\LeadRepositoryInterface;
use App\Contracts\LeadServiceInterface;
use App\Contracts\PlatformRepositoryInterface;
use App\Contracts\PlatformServiceInterface;
use App\Repositories\LeadRepository;
use App\Repositories\PlatformRepository;
use App\Services\LeadService;
use App\Services\PlatformService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for binding lead-related interfaces to implementations.
 * 
 * This provider handles the registration of all lead-related bindings including
 * repositories and services, keeping the AppServiceProvider clean and following 
 * single responsibility principle.
 */
final class LeadServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->registerRepositoryBindings();
        $this->registerServiceBindings();
    }

    /**
     * Bootstrap services.
     * 
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register repository interface bindings.
     * 
     * @return void
     */
    private function registerRepositoryBindings(): void
    {
        // Lead repository bindings
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        
        // Platform repository bindings
        $this->app->bind(PlatformRepositoryInterface::class, PlatformRepository::class);
    }

    /**
     * Register service interface bindings.
     * 
     * @return void
     */
    private function registerServiceBindings(): void
    {
        // Lead service bindings
        $this->app->bind(LeadServiceInterface::class, LeadService::class);
        
        // Platform service bindings
        $this->app->bind(PlatformServiceInterface::class, PlatformService::class);
    }
} 
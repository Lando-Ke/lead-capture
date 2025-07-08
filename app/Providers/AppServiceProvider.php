<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // For a small project like this, registering all contracts here would have been sufficient.
        // However, using a dedicated LeadServiceProvider provides several advantages:
        // 1. Single Responsibility: Each provider has a focused purpose
        // 2. Better Organization: Lead-related bindings are grouped together
        // 3. Easier Testing: Can register different implementations per provider
        // 4. Scalability: As the app grows, feature-specific providers keep things organized
        // 5. Clarity: Makes dependencies and their purposes more explicit
        
        // All lead-related bindings are now handled by LeadServiceProvider
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}

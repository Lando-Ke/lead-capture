<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\OneSignalServiceInterface;
use App\Services\OneSignalService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * OneSignal Service Provider.
 *
 * Handles registration and binding of OneSignal services
 * for dependency injection throughout the application.
 */
class OneSignalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the OneSignal service interface to its implementation
        $this->app->bind(OneSignalServiceInterface::class, function (Application $app) {
            return new OneSignalService();
        });

        // Create singleton binding for efficiency
        $this->app->singleton('onesignal', function (Application $app) {
            return $app->make(OneSignalServiceInterface::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register any boot-time logic here if needed
        // For example, validating configuration, setting up event listeners, etc.
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            OneSignalServiceInterface::class,
            'onesignal',
        ];
    }
}

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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
     */
    public function register(): void
    {
        $this->registerRepositoryBindings();
        $this->registerServiceBindings();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureLeadRateLimiting();
    }

    /**
     * Configure rate limiters for lead-related operations.
     *
     * Implements intelligent rate limiting that considers both IP and user identity
     * for better security and user experience.
     */
    private function configureLeadRateLimiting(): void
    {
        // Enhanced rate limiter for lead submissions with per-user logic
        RateLimiter::for('leads', function (Request $request) {
            // For authenticated users: Higher limits, user-based tracking
            if ($request->user()) {
                return [
                    // Per-user limit: 10 submissions per minute
                    Limit::perMinute(10)
                        ->by('user:' . $request->user()->id)
                        ->response(function () {
                            return response()->json([
                                'success' => false,
                                'message' => 'You have submitted too many leads. Please wait before submitting again.',
                                'error_code' => 'USER_RATE_LIMIT_EXCEEDED',
                                'retry_after' => 60,
                                'limit_type' => 'user',
                            ], 429);
                        }),

                    // Fallback IP limit for additional protection
                    Limit::perMinute(15)
                        ->by('ip:' . $request->ip())
                        ->response(function () {
                            return response()->json([
                                'success' => false,
                                'message' => 'Too many requests from this location. Please try again later.',
                                'error_code' => 'IP_RATE_LIMIT_EXCEEDED',
                                'retry_after' => 60,
                                'limit_type' => 'ip',
                            ], 429);
                        }),
                ];
            }

            // For guest users: Stricter IP-based limits
            return Limit::perMinute(3)
                ->by('guest:' . $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many lead submissions from this IP. Please try again later or consider creating an account for higher limits.',
                        'error_code' => 'GUEST_RATE_LIMIT_EXCEEDED',
                        'retry_after' => 60,
                        'limit_type' => 'guest_ip',
                        'suggestion' => 'Create an account for higher submission limits',
                    ], 429);
                });
        });

        // Rate limiter for email checking (less restrictive)
        RateLimiter::for('email-check', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(30)->by('user:email-check:' . $request->user()->id)
                : Limit::perMinute(20)->by('guest:email-check:' . $request->ip());
        });
    }

    /**
     * Register repository interface bindings.
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
     */
    private function registerServiceBindings(): void
    {
        // Lead service bindings
        $this->app->bind(LeadServiceInterface::class, LeadService::class);

        // Platform service bindings
        $this->app->bind(PlatformServiceInterface::class, PlatformService::class);
    }
}

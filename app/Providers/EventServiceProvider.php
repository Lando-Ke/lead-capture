<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\LeadSubmittedEvent;
use App\Listeners\SendNotificationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event Service Provider
 * 
 * Handles registration of application events and their listeners
 * following the project's pattern of dedicated service providers.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        LeadSubmittedEvent::class => [
            SendNotificationListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 
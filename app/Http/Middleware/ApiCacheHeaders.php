<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for adding appropriate cache headers to API responses.
 *
 * Applies different caching strategies based on the endpoint type and content.
 */
final class ApiCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param string|null $strategy The caching strategy to apply
     */
    public function handle(Request $request, \Closure $next, ?string $strategy = 'default'): Response
    {
        $response = $next($request);

        // Only apply caching to successful GET requests
        if (!$request->isMethod('GET') || !$response->isSuccessful()) {
            return $response;
        }

        return $this->applyCachingStrategy($response, $strategy);
    }

    /**
     * Apply the appropriate caching strategy to the response.
     */
    private function applyCachingStrategy(Response $response, string $strategy): Response
    {
        return match ($strategy) {
            'platforms' => $this->applyPlatformCaching($response),
            'form-options' => $this->applyFormOptionsCaching($response),
            'short' => $this->applyShortTermCaching($response),
            'no-cache' => $this->applyNoCaching($response),
            default => $this->applyDefaultCaching($response),
        };
    }

    /**
     * Apply caching for platform endpoints (long-term caching).
     */
    private function applyPlatformCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'public, max-age=3600, stale-while-revalidate=1800');
        $response->headers->set('Expires', now()->addHour()->toRfc7231String());
        $response->headers->set('Last-Modified', now()->startOfHour()->toRfc7231String());
        $response->headers->set('Vary', 'Accept, Accept-Encoding');
        $response->headers->set('X-Cache-Strategy', 'platforms');

        return $response;
    }

    /**
     * Apply caching for form options (medium-term caching).
     */
    private function applyFormOptionsCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'public, max-age=1800, stale-while-revalidate=900');
        $response->headers->set('Expires', now()->addMinutes(30)->toRfc7231String());
        $response->headers->set('Last-Modified', now()->startOfDay()->toRfc7231String());
        $response->headers->set('Vary', 'Accept, Accept-Encoding');
        $response->headers->set('X-Cache-Strategy', 'form-options');

        return $response;
    }

    /**
     * Apply short-term caching (for dynamic but cacheable content).
     */
    private function applyShortTermCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'public, max-age=300, stale-while-revalidate=150');
        $response->headers->set('Expires', now()->addMinutes(5)->toRfc7231String());
        $response->headers->set('Vary', 'Accept, Accept-Encoding');
        $response->headers->set('X-Cache-Strategy', 'short');

        return $response;
    }

    /**
     * Apply no caching (for sensitive or frequently changing data).
     */
    private function applyNoCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Cache-Strategy', 'no-cache');

        return $response;
    }

    /**
     * Apply default caching strategy.
     */
    private function applyDefaultCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'public, max-age=600, stale-while-revalidate=300');
        $response->headers->set('Expires', now()->addMinutes(10)->toRfc7231String());
        $response->headers->set('Vary', 'Accept, Accept-Encoding');
        $response->headers->set('X-Cache-Strategy', 'default');

        return $response;
    }
}

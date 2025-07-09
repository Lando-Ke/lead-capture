<?php

declare(strict_types=1);

use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
})->name('api.health');

// API Documentation
Route::get('/documentation', [ApiDocumentationController::class, 'index'])
    ->name('api.documentation')
    ->middleware('api.cache:short');

Route::get('/openapi', [ApiDocumentationController::class, 'openapi'])
    ->name('api.openapi')
    ->middleware('api.cache:short');

// API v1 Routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Platform routes - Public (used for form options) with caching
    Route::prefix('platforms')->name('platforms.')->middleware('api.cache:platforms')->group(function () {
        Route::get('/', [PlatformController::class, 'index'])
            ->name('index');

        Route::get('/{slug}', [PlatformController::class, 'show'])
            ->name('show')
            ->whereAlphaNumeric('slug');
    });

    // Lead routes - Enhanced rate limiting (per-user for auth, IP-based for guests)
    Route::prefix('leads')->name('leads.')->middleware('throttle:leads')->group(function () {
        Route::post('/', [LeadController::class, 'store'])
            ->name('store');

        Route::get('/{email}/check', [LeadController::class, 'checkEmail'])
            ->name('check-email')
            ->middleware('throttle:email-check')
            ->whereIn('email', ['.*']); // Allow any email format, will be validated in controller
    });

    // Form metadata routes - Cached responses for better performance
    Route::prefix('form')->name('form.')->middleware('api.cache:form-options')->group(function () {
        Route::get('/options', function () {
            return response()->json([
                'website_types' => collect(App\Enums\WebsiteType::cases())->map(fn ($type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'icon' => $type->icon(),
                ])->toArray(),
                'cache_expires_at' => now()->addMinutes(30)->toISOString(),
            ]);
        })->name('options');
    });
});

// API v2 Routes (Future expansion)
Route::prefix('v2')->name('api.v2.')->group(function () {
    // Reserved for future API versions
    Route::any('{any}', function () {
        return response()->json([
            'message' => 'API v2 is not yet available',
            'current_version' => 'v1',
        ], 501);
    })->where('any', '.*');
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found',
        'available_versions' => ['v1'],
        'documentation' => url('/api/documentation'),
    ], 404);
});

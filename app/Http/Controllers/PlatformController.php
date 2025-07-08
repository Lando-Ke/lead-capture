<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PlatformServiceInterface;
use App\Enums\WebsiteType;
use App\Http\Resources\PlatformResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling platform operations.
 * 
 * Manages platform retrieval and filtering with proper
 * error handling and response formatting.
 */
final class PlatformController extends Controller
{
    public function __construct(
        private readonly PlatformServiceInterface $platformService
    ) {}

    /**
     * Get all active platforms.
     * 
     * @return JsonResponse The response with platform data
     */
    public function index(): JsonResponse
    {
        try {
            $platforms = $this->platformService->getAllActivePlatforms();

            return response()->json([
                'success' => true,
                'data' => PlatformResource::collection($platforms),
                'meta' => [
                    'count' => $platforms->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Platform retrieval error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving platforms',
                'error_code' => 'PLATFORM_RETRIEVAL_ERROR',
            ], 500);
        }
    }

    /**
     * Get platforms filtered by website type.
     * 
     * @param string $websiteType The website type to filter by
     * @return JsonResponse The response with filtered platform data
     */
    public function byWebsiteType(string $websiteType): JsonResponse
    {
        try {
            $websiteTypeEnum = WebsiteType::from($websiteType);
            $platforms = $this->platformService->getPlatformsForWebsiteType($websiteTypeEnum);

            return response()->json([
                'success' => true,
                'data' => PlatformResource::collection($platforms),
                'meta' => [
                    'count' => $platforms->count(),
                    'website_type' => [
                        'value' => $websiteTypeEnum->value,
                        'label' => $websiteTypeEnum->label(),
                        'description' => $websiteTypeEnum->description(),
                        'icon' => $websiteTypeEnum->icon(),
                    ],
                ],
            ]);

        } catch (\ValueError $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid website type provided',
                'error_code' => 'INVALID_WEBSITE_TYPE',
                'meta' => [
                    'valid_types' => collect(WebsiteType::cases())->map(fn($type) => [
                        'value' => $type->value,
                        'label' => $type->label(),
                    ])->toArray(),
                ],
            ], 422);

        } catch (\Exception $e) {
            Log::error('Platform filtering error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'website_type' => $websiteType,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while filtering platforms',
                'error_code' => 'PLATFORM_FILTERING_ERROR',
            ], 500);
        }
    }

    /**
     * Get a specific platform by slug.
     * 
     * @param string $slug The platform slug
     * @return JsonResponse The response with platform data
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $platform = $this->platformService->findPlatformBySlug($slug);

            if (!$platform) {
                return response()->json([
                    'success' => false,
                    'message' => 'Platform not found',
                    'error_code' => 'PLATFORM_NOT_FOUND',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new PlatformResource($platform),
            ]);

        } catch (\Exception $e) {
            Log::error('Platform retrieval error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'slug' => $slug,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the platform',
                'error_code' => 'PLATFORM_RETRIEVAL_ERROR',
            ], 500);
        }
    }
} 
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WebsiteType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for API documentation endpoints.
 * 
 * Provides comprehensive documentation for all API endpoints in a structured format.
 */
final class ApiDocumentationController extends Controller
{
    /**
     * Get comprehensive API documentation.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'api' => [
                'name' => 'Lead Capture API',
                'version' => '1.0.0',
                'description' => 'API for capturing and managing leads through a multi-step form process.',
                'base_url' => url('/api/v1'),
                'last_updated' => now()->toDateString(),
            ],
            'authentication' => [
                'type' => 'none',
                'description' => 'This API is publicly accessible and does not require authentication.',
            ],
            'rate_limiting' => [
                'leads' => '5 requests per minute per IP',
                'email_check' => '10 requests per minute per IP',
                'other_endpoints' => '60 requests per minute per IP',
            ],
            'caching' => [
                'platforms' => '1 hour cache with 30 minute stale-while-revalidate',
                'form_options' => '30 minute cache with 15 minute stale-while-revalidate',
                'other_endpoints' => '10 minute cache with 5 minute stale-while-revalidate',
            ],
            'endpoints' => $this->getEndpoints(),
            'models' => $this->getModels(),
            'enums' => $this->getEnums(),
            'examples' => $this->getExamples(),
        ]);
    }

    /**
     * Get OpenAPI specification.
     *
     * @return JsonResponse
     */
    public function openapi(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Lead Capture API',
                'version' => '1.0.0',
                'description' => 'API for capturing and managing leads through a multi-step form process.',
            ],
            'servers' => [
                [
                    'url' => url('/api/v1'),
                    'description' => 'Production server',
                ],
            ],
            'paths' => $this->getOpenApiPaths(),
            'components' => [
                'schemas' => $this->getOpenApiSchemas(),
            ],
        ]);
    }

    /**
     * Get all API endpoints documentation.
     *
     * @return array
     */
    private function getEndpoints(): array
    {
        return [
            'health' => [
                'method' => 'GET',
                'path' => '/health',
                'description' => 'Check API health status',
                'parameters' => [],
                'responses' => [
                    '200' => [
                        'description' => 'API is healthy',
                        'example' => [
                            'status' => 'healthy',
                            'timestamp' => now()->toISOString(),
                            'version' => '1.0.0',
                        ],
                    ],
                ],
            ],
            'platforms.index' => [
                'method' => 'GET',
                'path' => '/v1/platforms',
                'description' => 'Get all active platforms',
                'parameters' => [],
                'responses' => [
                    '200' => [
                        'description' => 'List of active platforms',
                        'example' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Shopify',
                                    'slug' => 'shopify',
                                    'description' => 'All-in-one commerce platform',
                                    'website_types' => ['ecommerce'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'platforms.by-website-type' => [
                'method' => 'GET',
                'path' => '/v1/platforms/website-type/{websiteType}',
                'description' => 'Get platforms filtered by website type',
                'parameters' => [
                    'websiteType' => [
                        'type' => 'string',
                        'enum' => collect(WebsiteType::cases())->map(fn($type) => $type->value)->toArray(),
                        'description' => 'Website type to filter by',
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Filtered platforms',
                        'example' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Shopify',
                                    'slug' => 'shopify',
                                    'description' => 'All-in-one commerce platform',
                                    'website_types' => ['ecommerce'],
                                ],
                            ],
                            'website_type' => [
                                'value' => 'ecommerce',
                                'label' => 'E-commerce',
                            ],
                        ],
                    ],
                    '422' => [
                        'description' => 'Invalid website type',
                        'example' => [
                            'error' => 'Invalid website type provided',
                            'valid_types' => ['ecommerce', 'blog', 'business', 'portfolio', 'other'],
                        ],
                    ],
                ],
            ],
            'platforms.show' => [
                'method' => 'GET',
                'path' => '/v1/platforms/{slug}',
                'description' => 'Get platform by slug',
                'parameters' => [
                    'slug' => [
                        'type' => 'string',
                        'description' => 'Platform slug identifier',
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Platform details',
                        'example' => [
                            'data' => [
                                'id' => 1,
                                'name' => 'Shopify',
                                'slug' => 'shopify',
                                'description' => 'All-in-one commerce platform',
                                'website_types' => ['ecommerce'],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Platform not found',
                        'example' => [
                            'message' => 'Platform not found',
                        ],
                    ],
                ],
            ],
            'leads.store' => [
                'method' => 'POST',
                'path' => '/v1/leads',
                'description' => 'Submit a new lead',
                'parameters' => [],
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['name', 'email', 'website_type'],
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                        'minLength' => 2,
                                        'maxLength' => 255,
                                        'description' => 'Full name of the lead',
                                    ],
                                    'email' => [
                                        'type' => 'string',
                                        'format' => 'email',
                                        'maxLength' => 255,
                                        'description' => 'Email address (must be unique)',
                                    ],
                                    'company' => [
                                        'type' => 'string',
                                        'maxLength' => 255,
                                        'description' => 'Company name (optional)',
                                    ],
                                    'website_url' => [
                                        'type' => 'string',
                                        'format' => 'url',
                                        'maxLength' => 255,
                                        'description' => 'Website URL (optional)',
                                    ],
                                    'website_type' => [
                                        'type' => 'string',
                                        'enum' => collect(WebsiteType::cases())->map(fn($type) => $type->value)->toArray(),
                                        'description' => 'Type of website',
                                    ],
                                    'platform_id' => [
                                        'type' => 'integer',
                                        'description' => 'Platform ID (required if website_type is ecommerce)',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => [
                        'description' => 'Lead created successfully',
                        'example' => [
                            'success' => true,
                            'message' => 'Lead submitted successfully',
                            'data' => [
                                'id' => 1,
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'website_type' => [
                                    'value' => 'ecommerce',
                                    'label' => 'E-commerce',
                                    'description' => 'An online store selling products',
                                    'icon' => '🛒',
                                ],
                                'submitted_at' => now()->toISOString(),
                            ],
                        ],
                    ],
                    '409' => [
                        'description' => 'Lead already exists',
                        'example' => [
                            'success' => false,
                            'message' => 'A lead with email john@example.com already exists',
                            'error_code' => 'LEAD_EXISTS',
                        ],
                    ],
                    '422' => [
                        'description' => 'Validation error',
                        'example' => [
                            'message' => 'The given data was invalid',
                            'errors' => [
                                'email' => ['The email field is required.'],
                                'name' => ['The name field is required.'],
                            ],
                        ],
                    ],
                ],
            ],
            'leads.check-email' => [
                'method' => 'GET',
                'path' => '/v1/leads/{email}/check',
                'description' => 'Check if email already exists',
                'parameters' => [
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'Email address to check',
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Email check result',
                        'example' => [
                            'exists' => true,
                            'submitted_at' => now()->toISOString(),
                        ],
                    ],
                ],
            ],
            'form.options' => [
                'method' => 'GET',
                'path' => '/v1/form/options',
                'description' => 'Get form options and metadata',
                'parameters' => [],
                'responses' => [
                    '200' => [
                        'description' => 'Form options',
                        'example' => [
                            'website_types' => [
                                [
                                    'value' => 'ecommerce',
                                    'label' => 'E-commerce',
                                    'description' => 'An online store selling products',
                                    'icon' => '🛒',
                                ],
                            ],
                            'cache_expires_at' => now()->addMinutes(30)->toISOString(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get data models documentation.
     *
     * @return array
     */
    private function getModels(): array
    {
        return [
            'Lead' => [
                'description' => 'Represents a lead submission',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Unique identifier'],
                    'name' => ['type' => 'string', 'description' => 'Full name'],
                    'email' => ['type' => 'string', 'description' => 'Email address'],
                    'company' => ['type' => 'string', 'nullable' => true, 'description' => 'Company name'],
                    'website_url' => ['type' => 'string', 'nullable' => true, 'description' => 'Website URL'],
                    'website_type' => ['type' => 'object', 'description' => 'Website type information'],
                    'platform' => ['type' => 'object', 'nullable' => true, 'description' => 'Platform information'],
                    'submitted_at' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Submission timestamp'],
                ],
            ],
            'Platform' => [
                'description' => 'Represents a platform option',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Unique identifier'],
                    'name' => ['type' => 'string', 'description' => 'Platform name'],
                    'slug' => ['type' => 'string', 'description' => 'URL-friendly identifier'],
                    'description' => ['type' => 'string', 'description' => 'Platform description'],
                    'website_types' => ['type' => 'array', 'description' => 'Supported website types'],
                ],
            ],
        ];
    }

    /**
     * Get enums documentation.
     *
     * @return array
     */
    private function getEnums(): array
    {
        return [
            'WebsiteType' => [
                'description' => 'Available website types',
                'cases' => collect(WebsiteType::cases())->map(fn($type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                ])->toArray(),
            ],
        ];
    }

    /**
     * Get usage examples.
     *
     * @return array
     */
    private function getExamples(): array
    {
        return [
            'lead_submission' => [
                'title' => 'Submit a lead',
                'description' => 'Example of submitting a lead with required fields',
                'request' => [
                    'method' => 'POST',
                    'url' => '/api/v1/leads',
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'body' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'company' => 'Acme Corp',
                        'website_url' => 'https://example.com',
                        'website_type' => 'ecommerce',
                        'platform_id' => 1,
                    ],
                ],
                'response' => [
                    'status' => 201,
                    'body' => [
                        'success' => true,
                        'message' => 'Lead submitted successfully',
                        'data' => [
                            'id' => 1,
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'website_type' => [
                                'value' => 'ecommerce',
                                'label' => 'E-commerce',
                            ],
                            'submitted_at' => now()->toISOString(),
                        ],
                    ],
                ],
            ],
            'platform_lookup' => [
                'title' => 'Get platforms by website type',
                'description' => 'Example of fetching platforms for a specific website type',
                'request' => [
                    'method' => 'GET',
                    'url' => '/api/v1/platforms/website-type/ecommerce',
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
                'response' => [
                    'status' => 200,
                    'body' => [
                        'data' => [
                            [
                                'id' => 1,
                                'name' => 'Shopify',
                                'slug' => 'shopify',
                                'description' => 'All-in-one commerce platform',
                                'website_types' => ['ecommerce'],
                            ],
                        ],
                        'website_type' => [
                            'value' => 'ecommerce',
                            'label' => 'E-commerce',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get OpenAPI paths specification.
     *
     * @return array
     */
    private function getOpenApiPaths(): array
    {
        // This is a simplified version - in a full implementation,
        // you'd want to build this more comprehensively
        return [
            '/leads' => [
                'post' => [
                    'summary' => 'Submit a new lead',
                    'operationId' => 'submitLead',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/LeadRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Lead created successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/LeadResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get OpenAPI schemas.
     *
     * @return array
     */
    private function getOpenApiSchemas(): array
    {
        return [
            'LeadRequest' => [
                'type' => 'object',
                'required' => ['name', 'email', 'website_type'],
                'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 255],
                    'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 255],
                    'company' => ['type' => 'string', 'maxLength' => 255],
                    'website_url' => ['type' => 'string', 'format' => 'url', 'maxLength' => 255],
                    'website_type' => ['type' => 'string', 'enum' => collect(WebsiteType::cases())->map(fn($type) => $type->value)->toArray()],
                    'platform_id' => ['type' => 'integer'],
                ],
            ],
            'LeadResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'data' => ['$ref' => '#/components/schemas/Lead'],
                ],
            ],
            'Lead' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'company' => ['type' => 'string'],
                    'website_url' => ['type' => 'string'],
                    'website_type' => ['type' => 'object'],
                    'platform' => ['type' => 'object'],
                    'submitted_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ];
    }
} 
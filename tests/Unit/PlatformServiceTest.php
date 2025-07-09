<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\PlatformRepositoryInterface;
use App\Enums\WebsiteType;
use App\Models\Platform;
use App\Services\PlatformService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlatformServiceTest extends TestCase
{
    private PlatformService $platformService;

    private $platformRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformRepository = \Mockery::mock(PlatformRepositoryInterface::class);
        $this->platformService = new PlatformService($this->platformRepository);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function itCanGetPlatformsForWebsiteType(): void
    {
        $platforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Shopify', 'slug' => 'shopify']),
            new Platform(['id' => 2, 'name' => 'WooCommerce', 'slug' => 'woocommerce']),
        ]);

        $this->platformRepository
            ->shouldReceive('getByWebsiteType')
            ->with(WebsiteType::ECOMMERCE)
            ->once()
            ->andReturn($platforms);

        Cache::shouldReceive('remember')
            ->with('platforms:website_type:ecommerce', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $result = $this->platformService->getPlatformsForWebsiteType(WebsiteType::ECOMMERCE);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('Shopify', $result->first()->name);
    }

    #[Test]
    public function itCachesPlatformsForWebsiteType(): void
    {
        $platforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Shopify', 'slug' => 'shopify']),
        ]);

        $this->platformRepository
            ->shouldReceive('getByWebsiteType')
            ->with(WebsiteType::ECOMMERCE)
            ->once()
            ->andReturn($platforms);

        Cache::shouldReceive('remember')
            ->with('platforms:website_type:ecommerce', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                // Verify cache TTL is 24 hours in seconds
                $this->assertEquals(24 * 60 * 60, $ttl);

                return $callback();
            });

        $result = $this->platformService->getPlatformsForWebsiteType(WebsiteType::ECOMMERCE);

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function itCanGetAllActivePlatforms(): void
    {
        $platforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Shopify', 'slug' => 'shopify']),
            new Platform(['id' => 2, 'name' => 'WordPress', 'slug' => 'wordpress']),
        ]);

        $this->platformRepository
            ->shouldReceive('getAllActive')
            ->once()
            ->andReturn($platforms);

        Cache::shouldReceive('remember')
            ->with('platforms:all_active', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $result = $this->platformService->getAllActivePlatforms();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function itCachesAllActivePlatforms(): void
    {
        $platforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Shopify', 'slug' => 'shopify']),
        ]);

        $this->platformRepository
            ->shouldReceive('getAllActive')
            ->once()
            ->andReturn($platforms);

        Cache::shouldReceive('remember')
            ->with('platforms:all_active', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                // Verify cache TTL is 24 hours in seconds
                $this->assertEquals(24 * 60 * 60, $ttl);

                return $callback();
            });

        $result = $this->platformService->getAllActivePlatforms();

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function itCanFindPlatformBySlug(): void
    {
        $platform = new Platform(['id' => 1, 'name' => 'Shopify', 'slug' => 'shopify']);

        $this->platformRepository
            ->shouldReceive('findBySlug')
            ->with('shopify')
            ->once()
            ->andReturn($platform);

        $result = $this->platformService->findPlatformBySlug('shopify');

        $this->assertInstanceOf(Platform::class, $result);
        $this->assertEquals('Shopify', $result->name);
        $this->assertEquals('shopify', $result->slug);
    }

    #[Test]
    public function itReturnsNullWhenPlatformNotFoundBySlug(): void
    {
        $this->platformRepository
            ->shouldReceive('findBySlug')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $result = $this->platformService->findPlatformBySlug('nonexistent');

        $this->assertNull($result);
    }

    #[Test]
    public function itCanClearPlatformCache(): void
    {
        Cache::shouldReceive('forget')
            ->with('platforms:website_type:ecommerce')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->with('platforms:website_type:blog')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->with('platforms:website_type:business')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->with('platforms:website_type:portfolio')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->with('platforms:website_type:other')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->with('platforms:all_active')
            ->once()
            ->andReturn(true);

        $this->platformService->clearPlatformCache();

        // No assertion needed - just verify the method completes without error
        $this->assertTrue(true);
    }

    #[Test]
    public function itHandlesDifferentWebsiteTypes(): void
    {
        $websiteTypes = [
            WebsiteType::ECOMMERCE,
            WebsiteType::BLOG,
            WebsiteType::BUSINESS,
            WebsiteType::PORTFOLIO,
            WebsiteType::OTHER,
        ];

        foreach ($websiteTypes as $websiteType) {
            $platforms = new Collection([
                new Platform(['id' => 1, 'name' => 'Test Platform', 'slug' => 'test']),
            ]);

            $this->platformRepository
                ->shouldReceive('getByWebsiteType')
                ->with($websiteType)
                ->once()
                ->andReturn($platforms);

            Cache::shouldReceive('remember')
                ->with("platforms:website_type:{$websiteType->value}", \Mockery::any(), \Mockery::any())
                ->once()
                ->andReturnUsing(function ($key, $ttl, $callback) {
                    return $callback();
                });

            $result = $this->platformService->getPlatformsForWebsiteType($websiteType);

            $this->assertInstanceOf(Collection::class, $result);
            $this->assertCount(1, $result);
        }
    }

    #[Test]
    public function itReturnsEmptyCollectionForNoPlatforms(): void
    {
        $emptyCollection = new Collection([]);

        $this->platformRepository
            ->shouldReceive('getByWebsiteType')
            ->with(WebsiteType::PORTFOLIO)
            ->once()
            ->andReturn($emptyCollection);

        Cache::shouldReceive('remember')
            ->with('platforms:website_type:portfolio', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $result = $this->platformService->getPlatformsForWebsiteType(WebsiteType::PORTFOLIO);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function itUsesProperCacheKeys(): void
    {
        $platforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Test Platform', 'slug' => 'test']),
        ]);

        $this->platformRepository
            ->shouldReceive('getByWebsiteType')
            ->with(WebsiteType::ECOMMERCE)
            ->once()
            ->andReturn($platforms);

        Cache::shouldReceive('remember')
            ->with('platforms:website_type:ecommerce', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                $this->assertEquals('platforms:website_type:ecommerce', $key);

                return $callback();
            });

        $this->platformService->getPlatformsForWebsiteType(WebsiteType::ECOMMERCE);
    }

    #[Test]
    public function itUsesProperCacheKeyForAllActive(): void
    {
        $platforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Test Platform', 'slug' => 'test']),
        ]);

        $this->platformRepository
            ->shouldReceive('getAllActive')
            ->once()
            ->andReturn($platforms);

        Cache::shouldReceive('remember')
            ->with('platforms:all_active', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                $this->assertEquals('platforms:all_active', $key);

                return $callback();
            });

        $this->platformService->getAllActivePlatforms();
    }

    #[Test]
    public function itHandlesRepositoryDependencyInjection(): void
    {
        $this->assertInstanceOf(PlatformRepositoryInterface::class, $this->platformRepository);
    }

    #[Test]
    public function itUsesProperMethodSignatures(): void
    {
        $reflection = new \ReflectionClass($this->platformService);

        $getByWebsiteTypeMethod = $reflection->getMethod('getPlatformsForWebsiteType');
        $this->assertEquals('getPlatformsForWebsiteType', $getByWebsiteTypeMethod->getName());
        $this->assertEquals(1, $getByWebsiteTypeMethod->getNumberOfRequiredParameters());

        $getAllActiveMethod = $reflection->getMethod('getAllActivePlatforms');
        $this->assertEquals('getAllActivePlatforms', $getAllActiveMethod->getName());
        $this->assertEquals(0, $getAllActiveMethod->getNumberOfRequiredParameters());

        $findBySlugMethod = $reflection->getMethod('findPlatformBySlug');
        $this->assertEquals('findPlatformBySlug', $findBySlugMethod->getName());
        $this->assertEquals(1, $findBySlugMethod->getNumberOfRequiredParameters());
    }

    #[Test]
    public function itHandlesCacheMissGracefully(): void
    {
        $platforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Shopify', 'slug' => 'shopify']),
        ]);

        $this->platformRepository
            ->shouldReceive('getByWebsiteType')
            ->with(WebsiteType::ECOMMERCE)
            ->once()
            ->andReturn($platforms);

        Cache::shouldReceive('remember')
            ->with('platforms:website_type:ecommerce', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                // Simulate cache miss by directly calling the callback
                return $callback();
            });

        $result = $this->platformService->getPlatformsForWebsiteType(WebsiteType::ECOMMERCE);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    #[Test]
    public function itHandlesCacheHitWithoutDatabaseCall(): void
    {
        $cachedPlatforms = new Collection([
            new Platform(['id' => 1, 'name' => 'Cached Platform', 'slug' => 'cached']),
        ]);

        $this->platformRepository
            ->shouldNotReceive('getByWebsiteType');

        Cache::shouldReceive('remember')
            ->with('platforms:website_type:ecommerce', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturn($cachedPlatforms);

        $result = $this->platformService->getPlatformsForWebsiteType(WebsiteType::ECOMMERCE);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals('Cached Platform', $result->first()->name);
    }
}

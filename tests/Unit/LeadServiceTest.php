<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\LeadRepositoryInterface;
use App\DTOs\LeadDTO;
use App\Enums\WebsiteType;
use App\Exceptions\LeadAlreadyExistsException;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadServiceTest extends TestCase
{
    private LeadService $leadService;

    private $leadRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leadRepository = \Mockery::mock(LeadRepositoryInterface::class);
        $this->leadService = new LeadService($this->leadRepository);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function itCanCreateANewLead(): void
    {
        $leadDTO = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $expectedLead = new Lead([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'website_url' => 'https://example.com',
            'website_type' => 'ecommerce',
            'platform_id' => 1,
            'created_at' => now(),
        ]);
        $expectedLead->id = 1;

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn(null);

        $this->leadRepository
            ->shouldReceive('create')
            ->with($leadDTO)
            ->once()
            ->andReturn($expectedLead);

        Log::shouldReceive('info')
            ->with('New lead created successfully', \Mockery::on(function ($data) {
                return $data['lead_id'] === 1
                       && $data['email'] === 'john@example.com'
                       && $data['website_type'] === 'ecommerce'
                       && $data['platform_id'] === 1
                       && isset($data['created_at']);
            }))
            ->once();

        $result = $this->leadService->createLead($leadDTO);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    #[Test]
    public function itCanGetLeadByEmail(): void
    {
        $expectedLead = new Lead([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn($expectedLead);

        $result = $this->leadService->getLeadByEmail('john@example.com');

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertEquals('john@example.com', $result->email);
    }

    #[Test]
    public function itReturnsNullWhenLeadDoesNotExist(): void
    {
        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->once()
            ->andReturn(null);

        $result = $this->leadService->getLeadByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }

    #[Test]
    public function itCanCheckIfLeadExists(): void
    {
        $existingLead = new Lead([
            'id' => 1,
            'email' => 'john@example.com',
        ]);

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn($existingLead);

        $result = $this->leadService->leadExists('john@example.com');

        $this->assertTrue($result);
    }

    #[Test]
    public function itReturnsFalseWhenLeadDoesNotExist(): void
    {
        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->once()
            ->andReturn(null);

        $result = $this->leadService->leadExists('nonexistent@example.com');

        $this->assertFalse($result);
    }

    #[Test]
    public function itHandlesLeadDtoWithMinimalData(): void
    {
        $leadDTO = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: null,
            websiteUrl: null,
            websiteType: WebsiteType::BUSINESS,
            platform: 1
        );

        $expectedLead = new Lead([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => null,
            'website_url' => null,
            'website_type' => 'business',
            'platform_id' => 1,
            'created_at' => now(),
        ]);
        $expectedLead->id = 1;

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn(null);

        $this->leadRepository
            ->shouldReceive('create')
            ->with($leadDTO)
            ->once()
            ->andReturn($expectedLead);

        Log::shouldReceive('info')
            ->with('New lead created successfully', \Mockery::on(function ($data) {
                return $data['lead_id'] === 1
                       && $data['email'] === 'john@example.com'
                       && $data['website_type'] === 'business'
                       && $data['platform_id'] === 1
                       && isset($data['created_at']);
            }))
            ->once();

        $result = $this->leadService->createLead($leadDTO);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
        $this->assertEquals(WebsiteType::BUSINESS, $result->website_type);
        $this->assertEquals(1, $result->platform_id);
    }

    #[Test]
    public function itHandlesDifferentWebsiteTypes(): void
    {
        $testCases = [
            WebsiteType::ECOMMERCE,
            WebsiteType::BLOG,
            WebsiteType::BUSINESS,
            WebsiteType::PORTFOLIO,
            WebsiteType::OTHER,
        ];

        foreach ($testCases as $index => $websiteType) {
            $leadDTO = new LeadDTO(
                name: 'Test User ' . $index,
                email: "test{$index}@example.com",
                company: 'Test Company',
                websiteUrl: 'https://example.com',
                websiteType: $websiteType,
                platform: $index + 1
            );

            $expectedLead = new Lead([
                'id' => $index + 1,
                'name' => 'Test User ' . $index,
                'email' => "test{$index}@example.com",
                'company' => 'Test Company',
                'website_url' => 'https://example.com',
                'website_type' => $websiteType->value,
                'platform_id' => $index + 1,
                'created_at' => now(),
            ]);
            $expectedLead->id = $index + 1;

            $this->leadRepository
                ->shouldReceive('findByEmail')
                ->with("test{$index}@example.com")
                ->once()
                ->andReturn(null);

            $this->leadRepository
                ->shouldReceive('create')
                ->with($leadDTO)
                ->once()
                ->andReturn($expectedLead);

            Log::shouldReceive('info')
                ->with('New lead created successfully', \Mockery::any())
                ->once();

            $result = $this->leadService->createLead($leadDTO);

            $this->assertInstanceOf(Lead::class, $result);
            $this->assertEquals($websiteType, $result->website_type);
        }
    }

    #[Test]
    public function itLogsAppropriateMessages(): void
    {
        $leadDTO = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $expectedLead = new Lead([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'website_url' => 'https://example.com',
            'website_type' => 'ecommerce',
            'platform_id' => 1,
            'created_at' => now(),
        ]);
        $expectedLead->id = 1;

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn(null);

        $this->leadRepository
            ->shouldReceive('create')
            ->with($leadDTO)
            ->once()
            ->andReturn($expectedLead);

        Log::shouldReceive('info')
            ->with('New lead created successfully', \Mockery::on(function ($data) {
                $this->assertArrayHasKey('lead_id', $data);
                $this->assertArrayHasKey('email', $data);
                $this->assertArrayHasKey('website_type', $data);
                $this->assertArrayHasKey('platform_id', $data);
                $this->assertArrayHasKey('created_at', $data);

                return true;
            }))
            ->once();

        $this->leadService->createLead($leadDTO);
    }

    #[Test]
    public function itLogsDuplicateSubmissionAttempts(): void
    {
        $leadDTO = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $existingLead = new Lead([
            'id' => 1,
            'email' => 'john@example.com',
            'created_at' => now()->subDay(),
        ]);
        $existingLead->id = 1;

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn($existingLead);

        Log::shouldReceive('info')
            ->with('Duplicate lead submission attempt', \Mockery::on(function ($data) {
                return $data['email'] === 'john@example.com'
                       && isset($data['existing_lead_id'])
                       && isset($data['attempted_at']);
            }))
            ->once();

        $this->expectException(LeadAlreadyExistsException::class);
        $this->expectExceptionMessage("A lead with email 'john@example.com' already exists (ID: 1)");

        $this->leadService->createLead($leadDTO);
    }

    #[Test]
    public function itHandlesRepositoryDependencyInjection(): void
    {
        $this->assertInstanceOf(LeadService::class, $this->leadService);
    }

    #[Test]
    public function itUsesProperMethodSignatures(): void
    {
        $reflection = new \ReflectionClass(LeadService::class);

        // Check createLead method
        $createMethod = $reflection->getMethod('createLead');
        $this->assertEquals('createLead', $createMethod->getName());

        // Check getLeadByEmail method
        $getMethod = $reflection->getMethod('getLeadByEmail');
        $this->assertEquals('getLeadByEmail', $getMethod->getName());

        // Check leadExists method
        $existsMethod = $reflection->getMethod('leadExists');
        $this->assertEquals('leadExists', $existsMethod->getName());
    }
}

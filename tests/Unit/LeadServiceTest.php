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
use Mockery;
use Tests\TestCase;

class LeadServiceTest extends TestCase
{
    private LeadService $leadService;
    private $leadRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->leadRepository = Mockery::mock(LeadRepositoryInterface::class);
        $this->leadService = new LeadService($this->leadRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_new_lead(): void
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
            ->with('New lead created successfully', Mockery::on(function ($data) {
                return $data['lead_id'] === 1 &&
                       $data['email'] === 'john@example.com' &&
                       $data['website_type'] === 'ecommerce' &&
                       $data['platform_id'] === 1 &&
                       isset($data['created_at']);
            }))
            ->once();

        $result = $this->leadService->createLead($leadDTO);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }



    /** @test */
    public function it_can_get_lead_by_email(): void
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

    /** @test */
    public function it_returns_null_when_lead_does_not_exist(): void
    {
        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->once()
            ->andReturn(null);

        $result = $this->leadService->getLeadByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_check_if_lead_exists(): void
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

    /** @test */
    public function it_returns_false_when_lead_does_not_exist(): void
    {
        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->once()
            ->andReturn(null);

        $result = $this->leadService->leadExists('nonexistent@example.com');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_lead_dto_with_minimal_data(): void
    {
        $leadDTO = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: null,
            websiteUrl: null,
            websiteType: WebsiteType::BUSINESS,
            platform: null
        );

        $expectedLead = new Lead([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => null,
            'website_url' => null,
            'website_type' => 'business',
            'platform_id' => null,
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
            ->with('New lead created successfully', Mockery::on(function ($data) {
                return $data['lead_id'] === 1 &&
                       $data['email'] === 'john@example.com' &&
                       $data['website_type'] === 'business' &&
                       $data['platform_id'] === null &&
                       isset($data['created_at']);
            }))
            ->once();

        $result = $this->leadService->createLead($leadDTO);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
        $this->assertNull($result->company);
        $this->assertNull($result->website_url);
        $this->assertNull($result->platform_id);
    }

    /** @test */
    public function it_handles_different_website_types(): void
    {
        $websiteTypes = [
            WebsiteType::ECOMMERCE,
            WebsiteType::BLOG,
            WebsiteType::BUSINESS,
            WebsiteType::PORTFOLIO,
            WebsiteType::OTHER,
        ];

        foreach ($websiteTypes as $websiteType) {
            $leadDTO = new LeadDTO(
                name: 'John Doe',
                email: "john+{$websiteType->value}@example.com",
                company: 'Acme Corp',
                websiteUrl: 'https://example.com',
                websiteType: $websiteType,
                platform: $websiteType === WebsiteType::ECOMMERCE ? 1 : null
            );

            $expectedLead = new Lead([
                'name' => 'John Doe',
                'email' => "john+{$websiteType->value}@example.com",
                'website_type' => $websiteType->value,
                'created_at' => now(),
            ]);
            $expectedLead->id = 1;

            $this->leadRepository
                ->shouldReceive('findByEmail')
                ->with("john+{$websiteType->value}@example.com")
                ->once()
                ->andReturn(null);

            $this->leadRepository
                ->shouldReceive('create')
                ->with($leadDTO)
                ->once()
                ->andReturn($expectedLead);

            Log::shouldReceive('info')
                ->with('New lead created successfully', Mockery::on(function ($data) use ($websiteType) {
                    return $data['lead_id'] === 1 &&
                           $data['email'] === "john+{$websiteType->value}@example.com" &&
                           $data['website_type'] === $websiteType->value &&
                           isset($data['created_at']);
                }))
                ->once();

            $result = $this->leadService->createLead($leadDTO);

            $this->assertInstanceOf(Lead::class, $result);
            $this->assertEquals($websiteType, $result->website_type);
        }
    }

    /** @test */
    public function it_logs_appropriate_messages(): void
    {
        $leadDTO = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $newLead = new Lead([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => 'ecommerce',
            'created_at' => now(),
        ]);
        $newLead->id = 1;

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->andReturn(null);

        $this->leadRepository
            ->shouldReceive('create')
            ->andReturn($newLead);

        Log::shouldReceive('info')
            ->with('New lead created successfully', Mockery::on(function ($data) {
                return $data['lead_id'] === 1 &&
                       $data['email'] === 'john@example.com' &&
                       $data['website_type'] === 'ecommerce' &&
                       isset($data['created_at']);
            }))
            ->once();

        $result = $this->leadService->createLead($leadDTO);
        
        $this->assertInstanceOf(Lead::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    /** @test */
    public function it_logs_duplicate_submission_attempts(): void
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
            'name' => 'Existing User',
            'email' => 'john@example.com',
        ]);
        $existingLead->id = 5;

        $this->leadRepository
            ->shouldReceive('findByEmail')
            ->andReturn($existingLead);

        Log::shouldReceive('info')
            ->with('Duplicate lead submission attempt', Mockery::on(function ($data) {
                return $data['email'] === 'john@example.com' &&
                       $data['existing_lead_id'] === 5 &&
                       isset($data['attempted_at']);
            }))
            ->once();

        $this->expectException(LeadAlreadyExistsException::class);

        $this->leadService->createLead($leadDTO);
    }

    /** @test */
    public function it_handles_repository_dependency_injection(): void
    {
        $this->assertInstanceOf(LeadRepositoryInterface::class, $this->leadRepository);
    }

    /** @test */
    public function it_uses_proper_method_signatures(): void
    {
        $reflection = new \ReflectionClass($this->leadService);
        
        $createMethod = $reflection->getMethod('createLead');
        $this->assertEquals('createLead', $createMethod->getName());
        $this->assertEquals(1, $createMethod->getNumberOfRequiredParameters());
        
        $getMethod = $reflection->getMethod('getLeadByEmail');
        $this->assertEquals('getLeadByEmail', $getMethod->getName());
        $this->assertEquals(1, $getMethod->getNumberOfRequiredParameters());
        
        $existsMethod = $reflection->getMethod('leadExists');
        $this->assertEquals('leadExists', $existsMethod->getName());
        $this->assertEquals(1, $existsMethod->getNumberOfRequiredParameters());
    }
} 
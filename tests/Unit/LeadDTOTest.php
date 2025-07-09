<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\LeadDTO;
use App\Enums\WebsiteType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadDTOTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiatedWithAllProperties(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('Acme Corp', $dto->company);
        $this->assertEquals('https://example.com', $dto->websiteUrl);
        $this->assertEquals(WebsiteType::ECOMMERCE, $dto->websiteType);
        $this->assertEquals(1, $dto->platform);
    }

    #[Test]
    public function itCanBeInstantiatedWithMinimalProperties(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: null,
            websiteUrl: null,
            websiteType: WebsiteType::BUSINESS,
            platform: 1
        );

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertNull($dto->company);
        $this->assertNull($dto->websiteUrl);
        $this->assertEquals(WebsiteType::BUSINESS, $dto->websiteType);
        $this->assertEquals(1, $dto->platform);
    }

    #[Test]
    public function itCanBeCreatedFromArray(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'website_url' => 'https://example.com',
            'website_type' => 'ecommerce',
            'platform_id' => 1,
        ];

        $dto = LeadDTO::fromArray($data);

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('Acme Corp', $dto->company);
        $this->assertEquals('https://example.com', $dto->websiteUrl);
        $this->assertEquals(WebsiteType::ECOMMERCE, $dto->websiteType);
        $this->assertEquals(1, $dto->platform);
    }

    #[Test]
    public function itCanBeCreatedFromArrayWithNullableFields(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'website_type' => 'business',
            'platform_id' => 1,
        ];

        $dto = LeadDTO::fromArray($data);

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertNull($dto->company);
        $this->assertNull($dto->websiteUrl);
        $this->assertEquals(WebsiteType::BUSINESS, $dto->websiteType);
        $this->assertEquals(1, $dto->platform);
    }

    #[Test]
    public function itCanBeConvertedToArray(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'website_url' => 'https://example.com',
            'website_type' => 'ecommerce',
            'platform_id' => 1,
        ], $array);
    }

    #[Test]
    public function itCanBeConvertedToArrayWithNullValues(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: null,
            websiteUrl: null,
            websiteType: WebsiteType::BUSINESS,
            platform: 1
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => null,
            'website_url' => null,
            'website_type' => 'business',
            'platform_id' => 1,
        ], $array);
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

        foreach ($websiteTypes as $typeEnum) {
            $dto = new LeadDTO(
                name: 'John Doe',
                email: 'john@example.com',
                company: null,
                websiteUrl: null,
                websiteType: $typeEnum,
                platform: 1
            );

            $array = $dto->toArray();

            $this->assertEquals($typeEnum->value, $array['website_type']);
        }
    }

    #[Test]
    public function itIsImmutable(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        // Verify properties are readonly
        $reflection = new \ReflectionClass($dto);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    #[Test]
    public function itHandlesPlatformForEcommerce(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: null,
            websiteUrl: null,
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $this->assertEquals(WebsiteType::ECOMMERCE, $dto->websiteType);
        $this->assertNotNull($dto->platform);
    }

    #[Test]
    public function itHandlesPlatformForNonEcommerce(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: null,
            websiteUrl: null,
            websiteType: WebsiteType::BLOG,
            platform: 1
        );

        $this->assertEquals(WebsiteType::BLOG, $dto->websiteType);
        $this->assertNotNull($dto->platform);
    }

    #[Test]
    public function itCanBeSerializedToJson(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $json = json_encode($dto->toArray());
        $decoded = json_decode($json, true);

        $this->assertEquals('John Doe', $decoded['name']);
        $this->assertEquals('john@example.com', $decoded['email']);
        $this->assertEquals('Acme Corp', $decoded['company']);
        $this->assertEquals('https://example.com', $decoded['website_url']);
        $this->assertEquals('ecommerce', $decoded['website_type']);
        $this->assertEquals(1, $decoded['platform_id']);
    }

    #[Test]
    public function itHandlesNullValuesCorrectly(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: null,
            websiteUrl: null,
            websiteType: WebsiteType::BUSINESS,
            platform: 1
        );

        $this->assertNull($dto->company);
        $this->assertNull($dto->websiteUrl);
        $this->assertEquals(1, $dto->platform);
    }

    #[Test]
    public function itPreservesDataTypes(): void
    {
        $dto = new LeadDTO(
            name: 'John Doe',
            email: 'john@example.com',
            company: 'Acme Corp',
            websiteUrl: 'https://example.com',
            websiteType: WebsiteType::ECOMMERCE,
            platform: 1
        );

        $this->assertIsString($dto->name);
        $this->assertIsString($dto->email);
        $this->assertIsString($dto->company);
        $this->assertIsString($dto->websiteUrl);
        $this->assertInstanceOf(WebsiteType::class, $dto->websiteType);
        $this->assertIsInt($dto->platform);
    }
}

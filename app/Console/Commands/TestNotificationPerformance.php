<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

/**
 * Performance testing command for the notification system.
 *
 * Simulates concurrent loads and measures response times,
 * success rates, and system performance under stress.
 */
final class TestNotificationPerformance extends Command
{
    protected $signature = 'test:notification-performance
                          {--concurrent=10 : Number of concurrent requests}
                          {--requests=100 : Total number of requests}
                          {--endpoint=leads : Endpoint to test (leads|notifications)}
                          {--delay=0 : Delay between batches in milliseconds}';

    protected $description = 'Test notification system performance under load';

    private array $results = [];

    private array $responseTimes = [];

    private array $errors = [];

    private Carbon $startTime;

    public function handle(): int
    {
        $this->startTime = Carbon::now();

        $concurrent = (int) $this->option('concurrent');
        $totalRequests = (int) $this->option('requests');
        $endpoint = $this->option('endpoint');
        $delay = (int) $this->option('delay');

        $this->info('🚀 Starting Performance Test');
        $this->info("   Endpoint: {$endpoint}");
        $this->info("   Total Requests: {$totalRequests}");
        $this->info("   Concurrent: {$concurrent}");
        $this->info("   Delay: {$delay}ms");
        $this->newLine();

        // Ensure we have test data
        $this->ensureTestData();

        $batches = (int) ceil($totalRequests / $concurrent);
        $progressBar = $this->output->createProgressBar($batches);
        $progressBar->start();

        for ($batch = 0; $batch < $batches; $batch++) {
            $batchStart = Carbon::now();
            $requestsInBatch = min($concurrent, $totalRequests - ($batch * $concurrent));

            if ($endpoint === 'leads') {
                $this->testLeadSubmissions($requestsInBatch);
            } else {
                $this->testNotificationEndpoints($requestsInBatch);
            }

            $progressBar->advance();

            if ($delay > 0 && $batch < $batches - 1) {
                usleep($delay * 1000);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->displayResults();

        return Command::SUCCESS;
    }

    private function ensureTestData(): void
    {
        if (Platform::count() === 0) {
            $this->warn('No platforms found. Creating test platform...');
            Platform::factory()->create([
                'name' => 'Performance Test Platform',
                'is_active' => true,
            ]);
        }
    }

    private function testLeadSubmissions(int $concurrent): void
    {
        $platform = Platform::first();

        Http::pool(function (Pool $pool) use ($concurrent, $platform) {
            for ($i = 0; $i < $concurrent; $i++) {
                $pool->post('http://127.0.0.1:8000/api/v1/leads', [
                    'name' => "Performance Test User {$i}",
                    'email' => "perf-test-{$i}-" . time() . '@test.com',
                    'phone' => '+1234567' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'platform_id' => $platform->id,
                    'website_type' => 'ecommerce',
                    'website_url' => "https://test-{$i}.com",
                    'monthly_revenue' => 10000 + ($i * 1000),
                    'current_provider' => 'None',
                    'timeline' => 'immediate',
                    'additional_info' => "Performance test submission {$i}",
                ]);
            }
        })->then(function (array $responses) {
            $this->recordResponses($responses, 'lead_submission');
        });
    }

    private function testNotificationEndpoints(int $concurrent): void
    {
        $endpoints = [
            '/api/v1/notifications/status',
            '/api/v1/notifications/health',
            '/api/v1/notifications/analytics',
            '/api/v1/notifications/queue',
            '/api/v1/notifications/logs?per_page=10',
        ];

        Http::pool(function (Pool $pool) use ($concurrent, $endpoints) {
            for ($i = 0; $i < $concurrent; $i++) {
                $endpoint = $endpoints[$i % count($endpoints)];
                $pool->get('http://127.0.0.1:8000' . $endpoint);
            }
        })->then(function (array $responses) {
            $this->recordResponses($responses, 'notification_endpoint');
        });
    }

    private function recordResponses(array $responses, string $type): void
    {
        foreach ($responses as $response) {
            $responseTime = $response->handlerStats()['total_time'] ?? 0;
            $responseTimeMs = $responseTime * 1000;

            $this->responseTimes[] = $responseTimeMs;

            $result = [
                'type' => $type,
                'status_code' => $response->status(),
                'response_time_ms' => $responseTimeMs,
                'success' => $response->successful(),
                'timestamp' => Carbon::now(),
            ];

            if (!$response->successful()) {
                $this->errors[] = [
                    'status_code' => $response->status(),
                    'body' => $response->body(),
                    'type' => $type,
                ];
            }

            $this->results[] = $result;
        }
    }

    private function displayResults(): void
    {
        $totalRequests = count($this->results);
        $successfulRequests = collect($this->results)->where('success', true)->count();
        $failedRequests = $totalRequests - $successfulRequests;

        $successRate = $totalRequests > 0 ? ($successfulRequests / $totalRequests) * 100 : 0;

        $totalDuration = $this->startTime->diffInMilliseconds(Carbon::now());
        $requestsPerSecond = $totalRequests > 0 ? ($totalRequests / ($totalDuration / 1000)) : 0;

        // Response time statistics
        $responseTimes = collect($this->responseTimes);
        $avgResponseTime = $responseTimes->avg();
        $minResponseTime = $responseTimes->min();
        $maxResponseTime = $responseTimes->max();
        $p95ResponseTime = $responseTimes->percentile(95);
        $p99ResponseTime = $responseTimes->percentile(99);

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $peakMemoryUsage = memory_get_peak_usage(true);

        $this->info('📊 Performance Test Results');
        $this->info('========================');
        $this->newLine();

        // Overall Statistics
        $this->info('🎯 Overall Statistics:');
        $this->line("   Total Requests: {$totalRequests}");
        $this->line("   Successful: {$successfulRequests}");
        $this->line("   Failed: {$failedRequests}");
        $this->line('   Success Rate: ' . number_format($successRate, 2) . '%');
        $this->line('   Total Duration: ' . number_format($totalDuration / 1000, 2) . 's');
        $this->line('   Requests/Second: ' . number_format($requestsPerSecond, 2));
        $this->newLine();

        // Response Time Statistics
        $this->info('⏱️  Response Time Statistics:');
        $this->line('   Average: ' . number_format($avgResponseTime, 2) . 'ms');
        $this->line('   Min: ' . number_format($minResponseTime, 2) . 'ms');
        $this->line('   Max: ' . number_format($maxResponseTime, 2) . 'ms');
        $this->line('   95th Percentile: ' . number_format($p95ResponseTime, 2) . 'ms');
        $this->line('   99th Percentile: ' . number_format($p99ResponseTime, 2) . 'ms');
        $this->newLine();

        // Memory Usage
        $this->info('💾 Memory Usage:');
        $this->line('   Current: ' . $this->formatBytes($memoryUsage));
        $this->line('   Peak: ' . $this->formatBytes($peakMemoryUsage));
        $this->newLine();

        // Error Analysis
        if (!empty($this->errors)) {
            $this->error('❌ Errors Encountered:');
            $errorCounts = collect($this->errors)->countBy('status_code');
            foreach ($errorCounts as $statusCode => $count) {
                $this->line("   HTTP {$statusCode}: {$count} errors");
            }
            $this->newLine();
        }

        // Performance Assessment
        $this->assessPerformance($avgResponseTime, $successRate, $requestsPerSecond);
    }

    private function assessPerformance(float $avgResponseTime, float $successRate, float $requestsPerSecond): void
    {
        $this->info('🏆 Performance Assessment:');

        // Response Time Assessment
        if ($avgResponseTime < 200) {
            $this->info('   ✅ Excellent response time (< 200ms)');
        } elseif ($avgResponseTime < 500) {
            $this->line('   ✅ Good response time (< 500ms)');
        } elseif ($avgResponseTime < 1000) {
            $this->warn('   ⚠️  Acceptable response time (< 1s)');
        } else {
            $this->error('   ❌ Poor response time (> 1s)');
        }

        // Success Rate Assessment
        if ($successRate >= 99.5) {
            $this->info('   ✅ Excellent success rate (≥ 99.5%)');
        } elseif ($successRate >= 99) {
            $this->line('   ✅ Good success rate (≥ 99%)');
        } elseif ($successRate >= 95) {
            $this->warn('   ⚠️  Acceptable success rate (≥ 95%)');
        } else {
            $this->error('   ❌ Poor success rate (< 95%)');
        }

        // Throughput Assessment
        if ($requestsPerSecond >= 100) {
            $this->info('   ✅ Excellent throughput (≥ 100 req/s)');
        } elseif ($requestsPerSecond >= 50) {
            $this->line('   ✅ Good throughput (≥ 50 req/s)');
        } elseif ($requestsPerSecond >= 20) {
            $this->warn('   ⚠️  Acceptable throughput (≥ 20 req/s)');
        } else {
            $this->error('   ❌ Poor throughput (< 20 req/s)');
        }

        $this->newLine();

        // Recommendations
        $this->info('💡 Recommendations:');

        if ($avgResponseTime > 500) {
            $this->line('   • Consider implementing response caching');
            $this->line('   • Optimize database queries');
            $this->line('   • Review OneSignal API timeout settings');
        }

        if ($successRate < 99) {
            $this->line('   • Implement retry mechanisms for failed requests');
            $this->line('   • Add request validation and error handling');
        }

        if ($requestsPerSecond < 50) {
            $this->line('   • Consider implementing queue workers for async processing');
            $this->line('   • Review server configuration and scaling options');
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }
}

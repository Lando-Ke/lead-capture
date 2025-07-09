<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\OneSignalServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for monitoring notification status and metrics.
 *
 * Provides endpoints for checking OneSignal service health,
 * queue status, and notification statistics.
 */
final class NotificationStatusController extends Controller
{
    public function __construct(
        private readonly OneSignalServiceInterface $oneSignalService
    ) {
    }

    /**
     * Get comprehensive notification system status.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $status = [
                'service_status' => $this->getServiceStatus(),
                'queue_status' => $this->getQueueStatus(),
                'statistics' => $this->getNotificationStatistics(),
                'recent_activity' => $this->getRecentActivity(),
                'timestamp' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve notification status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification status',
                'error_code' => 'STATUS_ERROR',
            ], 500);
        }
    }

    /**
     * Test OneSignal connection and get service health.
     */
    public function health(): JsonResponse
    {
        try {
            $startTime = microtime(true);

            $serviceStatus = $this->getServiceStatus();
            $connectionTest = $this->oneSignalService->testConnection();

            $healthCheck = [
                'service' => $serviceStatus,
                'connection_test' => [
                    'success' => $connectionTest->success,
                    'message' => $connectionTest->message,
                    'response_time_ms' => $connectionTest->responseTime,
                ],
                'overall_health' => $connectionTest->success ? 'healthy' : 'unhealthy',
                'total_check_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toISOString(),
            ];

            $statusCode = $connectionTest->success ? 200 : 503;

            return response()->json([
                'success' => $connectionTest->success,
                'data' => $healthCheck,
            ], $statusCode);
        } catch (\Exception $e) {
            Log::error('Notification health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Health check failed',
                'error_code' => 'HEALTH_CHECK_ERROR',
                'data' => [
                    'overall_health' => 'critical',
                    'timestamp' => now()->toISOString(),
                ],
            ], 503);
        }
    }

    /**
     * Get queue metrics and status.
     */
    public function queue(): JsonResponse
    {
        try {
            $queueStatus = $this->getQueueStatus();

            return response()->json([
                'success' => true,
                'data' => $queueStatus,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve queue status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue status',
                'error_code' => 'QUEUE_STATUS_ERROR',
            ], 500);
        }
    }

    /**
     * Get notification statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->getNotificationStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve notification statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification statistics',
                'error_code' => 'STATISTICS_ERROR',
            ], 500);
        }
    }

    /**
     * Get paginated notification logs with filtering.
     */
    public function logs(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'status' => 'string|in:pending,sent,failed,skipped',
                'email' => 'string|email',
                'date_from' => 'date',
                'date_to' => 'date|after_or_equal:date_from',
                'search' => 'string|max:255',
            ]);

            $query = \App\Models\NotificationLog::with('lead')
                ->orderBy('attempted_at', 'desc');

            // Apply filters
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['email'])) {
                $query->where('lead_email', 'like', '%' . $validated['email'] . '%');
            }

            if (!empty($validated['date_from'])) {
                $query->whereDate('attempted_at', '>=', $validated['date_from']);
            }

            if (!empty($validated['date_to'])) {
                $query->whereDate('attempted_at', '<=', $validated['date_to']);
            }

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('lead_email', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('message', 'like', '%' . $search . '%')
                        ->orWhere('notification_id', 'like', '%' . $search . '%');
                });
            }

            $perPage = $validated['per_page'] ?? 20;
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs->items(),
                    'pagination' => [
                        'current_page' => $logs->currentPage(),
                        'per_page' => $logs->perPage(),
                        'total' => $logs->total(),
                        'last_page' => $logs->lastPage(),
                        'has_more' => $logs->hasMorePages(),
                    ],
                ],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve notification logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification logs',
                'error_code' => 'LOGS_ERROR',
            ], 500);
        }
    }

    /**
     * Retry a failed notification.
     */
    public function retryNotification(Request $request, int $logId): JsonResponse
    {
        try {
            $notificationLog = \App\Models\NotificationLog::findOrFail($logId);

            if (!in_array($notificationLog->status, ['failed', 'skipped'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed or skipped notifications can be retried',
                    'error_code' => 'INVALID_STATUS',
                ], 400);
            }

            // Create a new notification attempt
            $retryLog = \App\Models\NotificationLog::create([
                'lead_id' => $notificationLog->lead_id,
                'lead_email' => $notificationLog->lead_email,
                'notification_type' => $notificationLog->notification_type,
                'title' => $notificationLog->title,
                'message' => $notificationLog->message,
                'additional_data' => $notificationLog->additional_data,
                'status' => 'pending',
                'attempt_number' => $notificationLog->attempt_number + 1,
                'user_agent' => 'Admin-Retry/' . $request->userAgent(),
                'ip_address' => $request->ip(),
                'metadata' => array_merge($notificationLog->metadata ?? [], [
                    'retried_from_log_id' => $logId,
                    'retried_at' => now()->toISOString(),
                    'retried_by' => 'admin',
                ]),
                'attempted_at' => now(),
            ]);

            // Send the notification immediately
            $startTime = microtime(true);
            $result = $this->oneSignalService->sendLeadSubmissionNotification();
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result->success) {
                $retryLog->markAsSent(
                    notificationId: $result->notificationId ?? 'retry-' . $retryLog->id,
                    recipients: $result->recipients,
                    responseTime: $result->responseTime,
                    processingTime: $processingTime,
                    rawResponse: $result->rawResponse
                );

                Log::info('Admin notification retry successful', [
                    'original_log_id' => $logId,
                    'retry_log_id' => $retryLog->id,
                    'lead_email' => $notificationLog->lead_email,
                    'notification_id' => $result->notificationId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Notification retry sent successfully',
                    'data' => [
                        'original_log_id' => $logId,
                        'retry_log_id' => $retryLog->id,
                        'notification_id' => $result->notificationId,
                        'response_time_ms' => $result->responseTime,
                        'processing_time_ms' => $processingTime,
                    ],
                ]);
            } else {
                $retryLog->markAsFailed(
                    errorCode: $result->errorCode ?? 'retry_failed',
                    errorMessage: $result->message ?? 'Retry attempt failed',
                    errorDetails: $result->errorDetails,
                    responseTime: $result->responseTime,
                    processingTime: $processingTime,
                    rawResponse: $result->rawResponse
                );

                Log::warning('Admin notification retry failed', [
                    'original_log_id' => $logId,
                    'retry_log_id' => $retryLog->id,
                    'lead_email' => $notificationLog->lead_email,
                    'error_message' => $result->message,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Notification retry failed: ' . $result->message,
                    'error_code' => 'RETRY_FAILED',
                    'data' => [
                        'original_log_id' => $logId,
                        'retry_log_id' => $retryLog->id,
                        'error_details' => $result->errorDetails,
                    ],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Failed to retry notification', [
                'log_id' => $logId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry notification',
                'error_code' => 'RETRY_ERROR',
            ], 500);
        }
    }

    /**
     * Send a manual test notification.
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'additional_data' => 'array',
            ]);

            $startTime = microtime(true);

            // Create a test notification log
            $testLog = \App\Models\NotificationLog::create([
                'lead_id' => null,
                'lead_email' => 'admin-test@' . request()->getHost(),
                'notification_type' => 'admin_test',
                'title' => $validated['title'],
                'message' => $validated['message'],
                'additional_data' => $validated['additional_data'] ?? [],
                'status' => 'pending',
                'attempt_number' => 1,
                'user_agent' => 'Admin-Test/' . $request->userAgent(),
                'ip_address' => $request->ip(),
                'metadata' => [
                    'test_type' => 'admin_manual',
                    'sent_by' => 'admin',
                    'test_timestamp' => now()->toISOString(),
                ],
                'attempted_at' => now(),
            ]);

            // Send via OneSignal service
            $result = $this->oneSignalService->sendLeadSubmissionNotification();

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result->success) {
                $testLog->markAsSent(
                    notificationId: $result->notificationId ?? 'test-' . $testLog->id,
                    recipients: $result->recipients,
                    responseTime: $result->responseTime,
                    processingTime: $processingTime,
                    rawResponse: $result->rawResponse
                );

                Log::info('Admin test notification sent successfully', [
                    'test_log_id' => $testLog->id,
                    'title' => $validated['title'],
                    'notification_id' => $result->notificationId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Test notification sent successfully',
                    'data' => [
                        'test_log_id' => $testLog->id,
                        'notification_id' => $result->notificationId,
                        'recipients' => $result->recipients,
                        'response_time_ms' => $result->responseTime,
                        'processing_time_ms' => $processingTime,
                    ],
                ]);
            } else {
                $testLog->markAsFailed(
                    errorCode: $result->errorCode ?? 'test_failed',
                    errorMessage: $result->message ?? 'Test notification failed',
                    errorDetails: $result->errorDetails,
                    responseTime: $result->responseTime,
                    processingTime: $processingTime,
                    rawResponse: $result->rawResponse
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Test notification failed: ' . $result->message,
                    'error_code' => 'TEST_FAILED',
                    'data' => [
                        'test_log_id' => $testLog->id,
                        'error_details' => $result->errorDetails,
                    ],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error_code' => 'TEST_ERROR',
            ], 500);
        }
    }

    /**
     * Get notification analytics and insights.
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'period' => 'string|in:24h,7d,30d,90d',
            ]);

            $period = $validated['period'] ?? '24h';
            $startDate = match ($period) {
                '24h' => now()->subDay(),
                '7d' => now()->subWeek(),
                '30d' => now()->subMonth(),
                '90d' => now()->subMonths(3),
                default => now()->subDay(),
            };

            $analytics = $this->getNotificationAnalytics($startDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'start_date' => $startDate->toISOString(),
                    'end_date' => now()->toISOString(),
                    'analytics' => $analytics,
                ],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve notification analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification analytics',
                'error_code' => 'ANALYTICS_ERROR',
            ], 500);
        }
    }

    /**
     * Get OneSignal service status and configuration.
     */
    private function getServiceStatus(): array
    {
        $config = $this->oneSignalService->getConfiguration();

        return [
            'enabled' => $this->oneSignalService->isEnabled(),
            'configured' => $this->oneSignalService->isConfigured(),
            'app_id' => $config['app_id'] ?? null,
            'has_api_key' => $config['has_api_key'] ?? false,
            'timeout' => $config['timeout'] ?? null,
        ];
    }

    /**
     * Get queue status and metrics.
     */
    private function getQueueStatus(): array
    {
        // Get total pending jobs
        $totalPending = DB::table('jobs')->count();

        // Get notifications queue specific jobs
        $notificationsPending = DB::table('jobs')
            ->where('queue', 'notifications')
            ->count();

        // Get failed jobs
        $totalFailed = DB::table('failed_jobs')->count();
        $notificationsFailed = DB::table('failed_jobs')
            ->where('payload', 'like', '%SendNotificationListener%')
            ->count();

        // Get oldest pending job
        $oldestJob = DB::table('jobs')
            ->orderBy('created_at', 'asc')
            ->first();

        return [
            'pending_jobs' => [
                'total' => $totalPending,
                'notifications' => $notificationsPending,
            ],
            'failed_jobs' => [
                'total' => $totalFailed,
                'notifications' => $notificationsFailed,
            ],
            'oldest_pending_job' => $oldestJob ? [
                'age_seconds' => time() - $oldestJob->created_at,
                'queue' => $oldestJob->queue,
                'created_at' => date('Y-m-d H:i:s', $oldestJob->created_at),
            ] : null,
        ];
    }

    /**
     * Get notification statistics from database.
     */
    private function getNotificationStatistics(): array
    {
        // Get lead counts
        $totalLeads = DB::table('leads')->count();
        $leadsToday = DB::table('leads')
            ->whereDate('created_at', today())
            ->count();
        $leadsThisWeek = DB::table('leads')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        return [
            'leads' => [
                'total' => $totalLeads,
                'today' => $leadsToday,
                'this_week' => $leadsThisWeek,
            ],
            'notifications' => [
                // Note: In a production system, you might want to store
                // notification history in a dedicated table for better tracking
                'estimated_sent' => $totalLeads, // Simplified for now
                'note' => 'Detailed notification tracking would require dedicated storage',
            ],
        ];
    }

    /**
     * Get recent notification activity from logs.
     */
    private function getRecentActivity(): array
    {
        // In a production system, you might want to store this in a database
        // For now, we'll return a simplified version

        $recentLeads = DB::table('leads')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'email', 'created_at'])
            ->map(function ($lead) {
                return [
                    'lead_id' => $lead->id,
                    'email' => $lead->email,
                    'submitted_at' => $lead->created_at,
                    'notification_status' => 'processed', // Simplified
                ];
            });

        return [
            'recent_leads' => $recentLeads->toArray(),
            'note' => 'Activity tracking is simplified - production systems should use dedicated audit tables',
        ];
    }

    /**
     * Get notification analytics for a given time period.
     */
    private function getNotificationAnalytics(\Carbon\Carbon $startDate): array
    {
        $endDate = now();

        // Get basic counts
        $totalNotifications = \App\Models\NotificationLog::whereBetween('attempted_at', [$startDate, $endDate])->count();
        $sentCount = \App\Models\NotificationLog::successful()->whereBetween('attempted_at', [$startDate, $endDate])->count();
        $failedCount = \App\Models\NotificationLog::failed()->whereBetween('attempted_at', [$startDate, $endDate])->count();
        $skippedCount = \App\Models\NotificationLog::skipped()->whereBetween('attempted_at', [$startDate, $endDate])->count();

        // Calculate success rate
        $successRate = $totalNotifications > 0 ? round(($sentCount / $totalNotifications) * 100, 2) : 0;

        // Get average response times
        $avgResponseTime = \App\Models\NotificationLog::successful()
            ->whereBetween('attempted_at', [$startDate, $endDate])
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        $avgProcessingTime = \App\Models\NotificationLog::successful()
            ->whereBetween('attempted_at', [$startDate, $endDate])
            ->whereNotNull('processing_time_ms')
            ->avg('processing_time_ms');

        // Get hourly breakdown (last 24 hours for detailed view)
        $hourlyBreakdown = [];
        if ($startDate->diffInHours($endDate) <= 24) {
            $hourlyBreakdown = $this->getHourlyNotificationBreakdown($startDate, $endDate);
        }

        // Get top error codes
        $topErrors = \App\Models\NotificationLog::failed()
            ->whereBetween('attempted_at', [$startDate, $endDate])
            ->selectRaw('error_code, COUNT(*) as count')
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($error) {
                return [
                    'error_code' => $error->error_code,
                    'count' => $error->count,
                ];
            });

        return [
            'summary' => [
                'total_notifications' => $totalNotifications,
                'sent' => $sentCount,
                'failed' => $failedCount,
                'skipped' => $skippedCount,
                'success_rate_percentage' => $successRate,
            ],
            'performance' => [
                'avg_response_time_ms' => $avgResponseTime ? round((float) $avgResponseTime, 2) : null,
                'avg_processing_time_ms' => $avgProcessingTime ? round((float) $avgProcessingTime, 2) : null,
            ],
            'hourly_breakdown' => $hourlyBreakdown,
            'top_error_codes' => $topErrors->toArray(),
            'trends' => [
                'note' => 'Trend analysis would require longer historical data collection',
            ],
        ];
    }

    /**
     * Get hourly notification breakdown for detailed analysis.
     */
    private function getHourlyNotificationBreakdown(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $breakdown = [];
        $current = $startDate->copy()->startOfHour();

        while ($current->lte($endDate)) {
            $nextHour = $current->copy()->addHour();

            $hourData = \App\Models\NotificationLog::whereBetween('attempted_at', [$current, $nextHour])
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = "sent" THEN 1 END) as sent,
                    COUNT(CASE WHEN status = "failed" THEN 1 END) as failed,
                    COUNT(CASE WHEN status = "skipped" THEN 1 END) as skipped
                ')
                ->first();

            $breakdown[] = [
                'hour' => $current->format('Y-m-d H:00'),
                'total' => $hourData->total ?? 0,
                'sent' => $hourData->sent ?? 0,
                'failed' => $hourData->failed ?? 0,
                'skipped' => $hourData->skipped ?? 0,
            ];

            $current = $nextHour;
        }

        return $breakdown;
    }
}

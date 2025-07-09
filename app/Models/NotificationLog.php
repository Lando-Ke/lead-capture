<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Notification Log Model
 * 
 * Tracks all notification attempts, outcomes, and performance metrics
 * for comprehensive monitoring and debugging capabilities.
 * 
 * @property int $id
 * @property int|null $lead_id
 * @property string $lead_email
 * @property string $notification_type
 * @property string $title
 * @property string $message
 * @property array|null $additional_data
 * @property string $status
 * @property string|null $notification_id
 * @property array|null $recipients
 * @property string|null $error_code
 * @property string|null $error_message
 * @property array|null $error_details
 * @property float|null $response_time_ms
 * @property float|null $processing_time_ms
 * @property int $attempt_number
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property array|null $metadata
 * @property array|null $raw_response
 * @property \Carbon\Carbon $attempted_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class NotificationLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lead_id',
        'lead_email',
        'notification_type',
        'title',
        'message',
        'additional_data',
        'status',
        'notification_id',
        'recipients',
        'error_code',
        'error_message',
        'error_details',
        'response_time_ms',
        'processing_time_ms',
        'attempt_number',
        'user_agent',
        'ip_address',
        'metadata',
        'raw_response',
        'attempted_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'additional_data' => 'array',
        'recipients' => 'array',
        'error_details' => 'array',
        'metadata' => 'array',
        'raw_response' => 'array',
        'response_time_ms' => 'decimal:2',
        'processing_time_ms' => 'decimal:2',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'raw_response', // Hide raw response by default for cleaner API responses
    ];

    /**
     * Notification status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Get the lead that this notification was sent for.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Scope a query to only include successful notifications.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope a query to only include failed notifications.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to only include skipped notifications.
     */
    public function scopeSkipped(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SKIPPED);
    }

    /**
     * Scope a query to only include pending notifications.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to notifications within a date range.
     */
    public function scopeDateRange(Builder $query, \Carbon\Carbon $start, \Carbon\Carbon $end): Builder
    {
        return $query->whereBetween('attempted_at', [$start, $end]);
    }

    /**
     * Scope a query to notifications for a specific lead email.
     */
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('lead_email', $email);
    }

    /**
     * Scope a query to notifications of a specific type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Check if the notification was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if the notification failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the notification was skipped.
     */
    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    /**
     * Check if the notification is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get the total recipients count.
     */
    public function getTotalRecipientsAttribute(): int
    {
        return $this->recipients['total'] ?? 0;
    }

    /**
     * Get the successful recipients count.
     */
    public function getSuccessfulRecipientsAttribute(): int
    {
        return $this->recipients['successful'] ?? 0;
    }

    /**
     * Get the failed recipients count.
     */
    public function getFailedRecipientsAttribute(): int
    {
        return $this->recipients['failed'] ?? 0;
    }

    /**
     * Get a human-readable duration since the notification was attempted.
     */
    public function getAttemptedAgoAttribute(): string
    {
        return $this->attempted_at->diffForHumans();
    }

    /**
     * Get a human-readable completion time.
     */
    public function getCompletedAgoAttribute(): ?string
    {
        return $this->completed_at?->diffForHumans();
    }

    /**
     * Create a new notification log entry.
     */
    public static function createForAttempt(
        ?int $leadId,
        string $leadEmail,
        string $title,
        string $message,
        array $additionalData = [],
        array $metadata = []
    ): self {
        return static::create([
            'lead_id' => $leadId,
            'lead_email' => $leadEmail,
            'notification_type' => 'lead_submission',
            'title' => $title,
            'message' => $message,
            'additional_data' => $additionalData,
            'status' => self::STATUS_PENDING,
            'attempt_number' => 1,
            'user_agent' => $metadata['user_agent'] ?? null,
            'ip_address' => $metadata['ip_address'] ?? null,
            'metadata' => $metadata,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Mark the notification as sent successfully.
     */
    public function markAsSent(
        string $notificationId,
        ?array $recipients = null,
        ?float $responseTime = null,
        ?float $processingTime = null,
        ?array $rawResponse = null
    ): self {
        $this->update([
            'status' => self::STATUS_SENT,
            'notification_id' => $notificationId,
            'recipients' => $recipients,
            'response_time_ms' => $responseTime,
            'processing_time_ms' => $processingTime,
            'raw_response' => $rawResponse,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the notification as failed.
     */
    public function markAsFailed(
        string $errorCode,
        string $errorMessage,
        ?array $errorDetails = null,
        ?float $responseTime = null,
        ?float $processingTime = null,
        ?array $rawResponse = null
    ): self {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'response_time_ms' => $responseTime,
            'processing_time_ms' => $processingTime,
            'raw_response' => $rawResponse,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the notification as skipped.
     */
    public function markAsSkipped(string $reason, ?array $metadata = null): self
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'error_message' => $reason,
            'metadata' => array_merge($this->metadata ?? [], $metadata ?? []),
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Update attempt number for retries.
     */
    public function incrementAttempt(): self
    {
        $this->increment('attempt_number');
        $this->update(['attempted_at' => now()]);

        return $this;
    }
}

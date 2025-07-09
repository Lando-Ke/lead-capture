<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            
            // Lead reference
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('set null');
            $table->string('lead_email')->index(); // Store email for reference even if lead is deleted
            
            // Notification details
            $table->string('notification_type')->default('lead_submission'); // Type of notification
            $table->string('title'); // Notification title
            $table->text('message'); // Notification message
            $table->json('additional_data')->nullable(); // Extra data sent with notification
            
            // Outcome tracking
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped'])->index();
            $table->string('notification_id')->nullable(); // OneSignal notification ID
            $table->json('recipients')->nullable(); // Recipient information from OneSignal
            $table->string('error_code')->nullable(); // Error code if failed
            $table->text('error_message')->nullable(); // Error message if failed
            $table->json('error_details')->nullable(); // Detailed error information
            
            // Performance metrics
            $table->decimal('response_time_ms', 8, 2)->nullable(); // OneSignal API response time
            $table->decimal('processing_time_ms', 8, 2)->nullable(); // Total processing time
            $table->integer('attempt_number')->default(1); // Retry attempt number
            
            // Context information
            $table->string('user_agent')->nullable(); // User agent from original request
            $table->string('ip_address')->nullable(); // IP address from original request
            $table->json('metadata')->nullable(); // Additional context data
            
            // OneSignal specific
            $table->json('raw_response')->nullable(); // Raw OneSignal API response
            
            // Audit fields
            $table->timestamp('attempted_at'); // When notification was attempted
            $table->timestamp('completed_at')->nullable(); // When notification completed (success or final failure)
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['notification_type', 'created_at']);
            $table->index(['attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

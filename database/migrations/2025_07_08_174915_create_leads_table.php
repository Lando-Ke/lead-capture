<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->string('website_url')->nullable();
            $table->string('website_type');
            $table->foreignId('platform_id')->nullable()->constrained('platforms')->onDelete('set null'); // Foreign key to platforms table
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['email', 'submitted_at']);
            $table->index('website_type');
            $table->index('platform_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_name', 64)->index();
            $table->string('event_source', 16)->default('browser')->index(); // 'browser' or 'server'
            $table->string('anonymous_id', 64)->index();
            $table->string('user_id', 64)->nullable()->index();
            $table->string('session_id', 64)->index();
            $table->text('url')->nullable();
            $table->string('path', 255)->nullable()->index();
            $table->text('referrer')->nullable();
            $table->json('properties')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent();

            // Compound indexes for analytics aggregation performance
            $table->index(['event_name', 'occurred_at']);
            $table->index(['occurred_at', 'anonymous_id']);
            $table->index(['event_source', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};


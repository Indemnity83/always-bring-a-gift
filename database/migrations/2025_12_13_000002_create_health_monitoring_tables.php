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
        Schema::create('notification_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('check_type');
            $table->string('status');
            $table->text('details')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index('checked_at');
        });

        Schema::create('notification_outages', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('outage_type');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['channel', 'is_resolved']);
            $table->index('started_at');
        });

        Schema::create('notification_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->date('date');
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0.00);
            $table->decimal('avg_response_time', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['channel', 'date'], 'channel_date_unique');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_metrics');
        Schema::dropIfExists('notification_outages');
        Schema::dropIfExists('notification_health_checks');
    }
};

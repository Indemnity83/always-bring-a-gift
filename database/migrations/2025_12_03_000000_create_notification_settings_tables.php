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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('lead_time_days')->default(7);
            $table->time('remind_at')->default('09:00:00');
            $table->json('channels')->default(json_encode(['mail']));
            $table->string('slack_webhook_url')->nullable();
            $table->string('discord_webhook_url')->nullable();
            $table->string('push_endpoint')->nullable();
            $table->string('push_token')->nullable();
            $table->timestamps();
        });

        Schema::create('event_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->date('remind_for_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'user_id', 'channel', 'remind_for_date'], 'event_user_channel_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_notification_logs');
        Schema::dropIfExists('notification_settings');
    }
};

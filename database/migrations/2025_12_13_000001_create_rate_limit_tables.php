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
        Schema::create('notification_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('action');
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('reset_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'channel', 'action']);
            $table->index('reset_at');
            $table->index('is_blocked');
        });

        Schema::create('notification_rate_limit_configs', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('action');
            $table->integer('max_attempts');
            $table->integer('window_minutes');
            $table->integer('block_duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['channel', 'action']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rate_limit_configs');
        Schema::dropIfExists('notification_rate_limits');
    }
};

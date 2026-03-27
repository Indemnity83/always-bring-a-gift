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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type');
            $table->boolean('enabled')->default(true);
            $table->json('channels')->default(json_encode(['mail']));
            $table->string('channel')->nullable();
            $table->integer('lead_time_minutes')->default(0);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->boolean('respect_quiet_hours')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'notification_type'], 'user_notification_type_unique');
            $table->index('notification_type');
        });

        Schema::create('notification_preference_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type');
            $table->boolean('enabled')->default(true);
            $table->json('channels')->default(json_encode(['mail']));
            $table->integer('lead_time_minutes')->default(0);
            $table->boolean('respect_quiet_hours')->default(true);
            $table->timestamps();

            $table->unique('notification_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preference_defaults');
        Schema::dropIfExists('notification_preferences');
    }
};

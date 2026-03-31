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
        Schema::create('notification_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('notification_type');
            $table->date('date');
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->decimal('delivery_rate', 5, 2)->default(0.00);
            $table->decimal('open_rate', 5, 2)->default(0.00);
            $table->decimal('click_rate', 5, 2)->default(0.00);
            $table->decimal('avg_delivery_time', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['channel', 'date']);
            $table->index(['notification_type', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_analytics');
    }
};

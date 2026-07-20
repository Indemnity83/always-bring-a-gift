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
        Schema::table('event_notification_logs', function (Blueprint $table) {
            $table->string('notification_type')->nullable()->after('channel');
            $table->string('status')->default('pending')->after('remind_for_date');
            $table->text('error_message')->nullable()->after('status');
            $table->integer('attempts')->default(0)->after('error_message');
            $table->timestamp('last_attempt_at')->nullable()->after('attempts');
            $table->string('template_id')->nullable()->after('last_attempt_at');
            $table->json('template_data')->nullable()->after('template_id');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->string('message_id')->nullable()->after('read_at');

            $table->index('status');
            $table->index('sent_at');
            $table->index('delivered_at');
            $table->index('attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_notification_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['delivered_at']);
            $table->dropIndex(['attempts']);

            $table->dropColumn([
                'notification_type',
                'status',
                'error_message',
                'attempts',
                'last_attempt_at',
                'template_id',
                'template_data',
                'delivered_at',
                'read_at',
                'message_id',
            ]);
        });
    }
};

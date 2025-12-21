<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicates = DB::table('event_notification_logs')
            ->select(
                'event_id',
                'user_id',
                'channel',
                'remind_for_date',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('event_id', 'user_id', 'channel', 'remind_for_date')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $ids = DB::table('event_notification_logs')
                ->where('event_id', $duplicate->event_id)
                ->where('user_id', $duplicate->user_id)
                ->where('channel', $duplicate->channel)
                ->where('remind_for_date', $duplicate->remind_for_date)
                ->orderByDesc('id')
                ->pluck('id')
                ->all();

            $idsToDelete = array_slice($ids, 1);
            if ($idsToDelete !== []) {
                DB::table('event_notification_logs')
                    ->whereIn('id', $idsToDelete)
                    ->delete();
            }
        }

        Schema::table('event_notification_logs', function (Blueprint $table) {
            $table->dropIndex('event_user_channel_date_index');
            $table->unique(
                ['event_id', 'user_id', 'channel', 'remind_for_date'],
                'unique_notification_log'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_notification_logs', function (Blueprint $table) {
            $table->dropUnique('unique_notification_log');
            $table->index(
                ['event_id', 'user_id', 'channel', 'remind_for_date'],
                'event_user_channel_date_index'
            );
        });
    }
};

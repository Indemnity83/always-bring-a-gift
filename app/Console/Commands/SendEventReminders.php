<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send {--days= : Override the lead time in days for this run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for upcoming events';

    /**
     * Execute the console command.
     */
    public function handle(ReminderService $reminderService): int
    {
        $overrideDays = $this->option('days');
        $overrideDays = $overrideDays !== null ? (int) $overrideDays : null;

        $this->info('Dispatching upcoming event reminders...');

        $count = $reminderService->sendUpcomingReminders($overrideDays);

        $this->info("{$count} reminders queued/sent.");

        return Command::SUCCESS;
    }
}

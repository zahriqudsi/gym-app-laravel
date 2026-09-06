<?php

namespace App\Console\Commands;

use App\Domain\Notifications\ReminderRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendReminders extends Command
{
    protected $signature = 'gym:send-reminders {--date= : Run as if today were this Y-m-d date}';

    protected $description = 'Evaluate reminder rules and queue member SMS/notifications';

    public function handle(ReminderRunner $runner): int
    {
        $on = $this->option('date') ? Carbon::parse($this->option('date')) : today();

        $this->info("Running reminders for {$on->toDateString()}...");
        $summary = $runner->run($on);

        $this->table(
            ['Rules evaluated', 'Queued', 'Skipped'],
            [[$summary['rules'], $summary['queued'], $summary['skipped']]]
        );

        return self::SUCCESS;
    }
}

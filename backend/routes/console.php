<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly: recompute statuses, then queue the day's reminders.
Schedule::command('gym:sync-statuses')->dailyAt('05:30');
Schedule::command('gym:send-reminders')->dailyAt('09:00')->withoutOverlapping();

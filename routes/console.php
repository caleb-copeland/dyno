<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily push reminders for users training today (needs `schedule:run` via cron).
Schedule::command('app:send-reminders')->dailyAt('08:00')->timezone('America/Detroit');

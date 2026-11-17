<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly "Time to check your car" push notification: every Monday at 10:00
// Lebanon time (follows summer / winter time).
Schedule::command('notifications:weekly-reminder')
    ->weeklyOn(1, '10:00')
    ->timezone('Asia/Beirut')
    ->withoutOverlapping();

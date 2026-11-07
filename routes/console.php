<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly "Time to check your car" push notification (every Saturday, 10:00 UTC).
Schedule::command('notifications:weekly-reminder')
    ->weeklyOn(6, '10:00')
    ->withoutOverlapping();

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('monitoring:poll')->everyMinute()->withoutOverlapping();
Schedule::command('network:health-check')->everyMinute()->withoutOverlapping();
Schedule::command('network:sync-ipam')->everyFiveMinutes()->withoutOverlapping();

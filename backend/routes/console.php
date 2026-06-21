<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('withdraw:auto-audit')->everyTenMinutes()->name('withdraw-auto-audit');
Schedule::command('withdraw:batch-process')->dailyAt('10:00')->name('withdraw-batch-process');
Schedule::command('withdraw:daily-reconcile')->dailyAt('02:00')->name('withdraw-daily-reconcile');

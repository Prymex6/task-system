<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:cycle')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Repeating tasks appear on the day they are due, so this runs early enough to
// be there before anyone opens their board.
Schedule::command('tasks:recurring')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();

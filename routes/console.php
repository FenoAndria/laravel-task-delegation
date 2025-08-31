<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The public demo starts fresh every night.
Schedule::command('demo:reset')->dailyAt('03:00')->when(fn () => config('delego.demo'));

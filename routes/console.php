<?php

use App\Console\Commands\CheckPaidPayments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckPaidPayments::class)->everyMinute();
Schedule::command('lhp:remind-whatsapp')->dailyAt(config('fonnte.lhp_reminder_at', '12:00'));
Schedule::command('absen:remind-whatsapp')->dailyAt(config('fonnte.absen_reminder_at', '17:00'));

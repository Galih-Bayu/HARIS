<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Models\User;

// Cron job untuk reset sisa cuti menjadi 12 hari setiap tanggal 1 Januari jam 00:00
Schedule::call(function () {
    User::query()->update(['sisa_cuti' => 12]);
})->yearly()->name('reset-sisa-cuti')->description('Reset sisa cuti tahunan karyawan ke default 12 hari');

<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/export/attendance', function () {
    return Maatwebsite\Excel\Facades\Excel::download(new App\Exports\AttendanceExport, 'Laporan_Absensi_HRIS.xlsx');
})->middleware(['auth'])->name('export.attendance');

require __DIR__.'/auth.php';

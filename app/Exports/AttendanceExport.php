<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Attendance::with('user')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Karyawan',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Keterlambatan (Menit)',
            'Status'
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->id,
            $attendance->user->name,
            $attendance->date,
            $attendance->clock_in,
            $attendance->clock_out,
            $attendance->late_minutes,
            $attendance->status,
        ];
    }
}

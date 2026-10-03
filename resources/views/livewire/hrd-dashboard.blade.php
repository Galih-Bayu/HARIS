<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;

new class extends Component {
    public function with(): array
    {
        $today = Carbon::today();
        
        $totalKaryawan = User::role('Karyawan')->count();
        $hadirHariIni = Attendance::where('date', $today)->count();
        $telatHariIni = Attendance::where('date', $today)->where('status', 'Telat')->count();
        $cutiHariIni = Leave::where('status', 'Approved')
                            ->where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today)
                            ->count();
                            
        return [
            'stats' => [
                'totalKaryawan' => $totalKaryawan,
                'hadirHariIni' => $hadirHariIni,
                'telatHariIni' => $telatHariIni,
                'cutiHariIni' => $cutiHariIni,
                'belumAbsen' => max(0, $totalKaryawan - $hadirHariIni - $cutiHariIni),
            ]
        ];
    }
}; ?>

<div class="mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-800">Ringkasan Hari Ini ({{ \Carbon\Carbon::now()->format('d M Y') }})</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-blue-500">
            <div class="text-sm font-medium text-gray-500 truncate">Total Karyawan</div>
            <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['totalKaryawan'] }}</div>
        </div>
        
        <!-- Card 2 -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-green-500">
            <div class="text-sm font-medium text-gray-500 truncate">Hadir / Clock-In</div>
            <div class="mt-1 text-3xl font-semibold text-green-600">{{ $stats['hadirHariIni'] }}</div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-red-500">
            <div class="text-sm font-medium text-gray-500 truncate">Terlambat</div>
            <div class="mt-1 text-3xl font-semibold text-red-600">{{ $stats['telatHariIni'] }}</div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-yellow-500">
            <div class="text-sm font-medium text-gray-500 truncate">Cuti / Izin</div>
            <div class="mt-1 text-3xl font-semibold text-yellow-600">{{ $stats['cutiHariIni'] }}</div>
        </div>
    </div>
    
    <!-- Chart Section using Alpine & Chart.js -->
    <div class="mt-8 bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-bold text-gray-700 mb-4">Grafik Kehadiran Harian</h3>
        
        <!-- Kita inject Chart.js via CDN khusus untuk komponen ini -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
        <div x-data="{
            init() {
                const ctx = document.getElementById('attendanceChart');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Tepat Waktu', 'Terlambat', 'Cuti', 'Belum Absen'],
                        datasets: [{
                            data: [
                                {{ $stats['hadirHariIni'] - $stats['telatHariIni'] }},
                                {{ $stats['telatHariIni'] }},
                                {{ $stats['cutiHariIni'] }},
                                {{ $stats['belumAbsen'] }}
                            ],
                            backgroundColor: ['#10B981', '#EF4444', '#F59E0B', '#9CA3AF'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        }" class="relative h-64 w-full">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <!-- Calendar Section using Alpine & FullCalendar -->
    <div class="mt-8 bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-bold text-gray-700 mb-4">Kalender Cuti Perusahaan</h3>
        
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
        
        <div x-data="{
            init() {
                const calendarEl = document.getElementById('calendar');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 500,
                    events: [
                        @foreach(App\Models\Leave::with('user')->where('status', 'Approved')->get() as $leave)
                        {
                            title: '{{ $leave->user->name }} ({{ $leave->type }})',
                            start: '{{ $leave->start_date }}',
                            end: '{{ \Carbon\Carbon::parse($leave->end_date)->addDay()->toDateString() }}',
                            color: '{{ $leave->type === 'Tahunan' ? '#3B82F6' : '#EF4444' }}'
                        },
                        @endforeach
                    ]
                });
                calendar.render();
            }
        }">
            <div id='calendar'></div>
        </div>
    </div>
</div>

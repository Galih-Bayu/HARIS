<?php

use Livewire\Volt\Component;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    
    public function with(): array
    {
        return [
            'pendingLeaves' => Leave::with('user')->where('status', 'Pending')->oldest()->get(),
            'historyLeaves' => Leave::with('user')->where('status', '!=', 'Pending')->latest()->take(10)->get(),
        ];
    }

    private function getWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = 0;
        
        // Contoh daftar libur nasional. (Idealnya disimpan di database)
        $nationalHolidays = [
            '2026-01-01', '2026-08-17', '2026-12-25'
        ];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isSunday() && !in_array($date->format('Y-m-d'), $nationalHolidays)) {
                $days++;
            }
        }
        return $days;
    }

    public function approve(int $leaveId): void
    {
        $leave = Leave::with('user')->findOrFail($leaveId);
        $days = $this->getWorkingDays($leave->start_date, $leave->end_date);

        if ($leave->type === 'Tahunan' && $leave->user->sisa_cuti < $days) {
            $this->dispatch('notify', message: 'Gagal! Kuota cuti karyawan tidak mencukupi.');
            return;
        }

        // Database Transaction untuk keamanan potong saldo
        DB::transaction(function () use ($leave, $days) {
            $leave->update(['status' => 'Approved']);
            
            if ($leave->type === 'Tahunan') {
                $leave->user->decrement('sisa_cuti', $days);
            }
        });

        $this->dispatch('notify', message: 'Cuti disetujui. Kuota karyawan otomatis terpotong.');
    }

    public function reject(int $leaveId): void
    {
        $leave = Leave::findOrFail($leaveId);
        $leave->update(['status' => 'Rejected']);
        
        $this->dispatch('notify', message: 'Pengajuan cuti ditolak.');
    }
}; ?>

<div class="mt-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
        <div class="p-6 text-gray-900">
            <h2 class="text-xl font-semibold mb-4 text-indigo-700">Approval Cuti (HRD)</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Karyawan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis & Alasan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($pendingLeaves as $leave)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $leave->user->name }}</div>
                                    <div class="text-sm text-gray-500">Sisa: {{ $leave->user->sisa_cuti }} hari</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $this->getWorkingDays($leave->start_date, $leave->end_date) }} Hari Kerja</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $leave->type }}
                                    </span>
                                    <div class="text-sm text-gray-500 mt-1">"{{ $leave->reason }}"</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button wire:click="approve({{ $leave->id }})" class="text-white bg-green-600 hover:bg-green-900 px-3 py-1 rounded">Approve</button>
                                    <button wire:click="reject({{ $leave->id }})" class="text-white bg-red-600 hover:bg-red-900 px-3 py-1 rounded">Reject</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Tidak ada pengajuan cuti pending.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Riwayat Approval -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Riwayat Approval Cuti</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Karyawan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Cuti</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ditinjau Pada</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($historyLeaves as $history)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $history->user->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($history->start_date)->format('d M y') }} - {{ \Carbon\Carbon::parse($history->end_date)->format('d M y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $history->type }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $history->status === 'Approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $history->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $history->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    Belum ada riwayat persetujuan cuti.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

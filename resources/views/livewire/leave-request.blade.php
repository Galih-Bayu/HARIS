<?php

use Livewire\Volt\Component;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

new class extends Component {
    public string $type = 'Tahunan';
    public string $start_date = '';
    public string $end_date = '';
    public string $reason = '';

    public function with(): array
    {
        $minDate = $this->type === 'Tahunan' 
            ? Carbon::now()->addDays(7)->toDateString() 
            : ''; // Bebas jika sakit
            
        return [
            'minDate' => $minDate,
            'myLeaves' => Leave::where('user_id', auth()->id())->latest()->get(),
            'sisaCuti' => auth()->user()->sisa_cuti,
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

    public function submit(): void
    {
        // Simulasi Form Request strict validation rules
        $rules = [
            'type' => ['required', 'string', Rule::in(['Tahunan', 'Sakit'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5'],
        ];

        // Validasi ekstra: Cuti tahunan minimal H-7
        if ($this->type === 'Tahunan') {
            $rules['start_date'][] = 'after_or_equal:' . Carbon::now()->addDays(7)->toDateString();
        }

        $this->validate($rules);

        // Hitung durasi hari kerja (tidak termasuk minggu & libur nasional)
        $days = $this->getWorkingDays($this->start_date, $this->end_date);
        
        if ($this->type === 'Tahunan' && $days > auth()->user()->sisa_cuti) {
            $this->addError('start_date', 'Durasi cuti melebihi sisa kuota cuti Anda!');
            return;
        }

        Leave::create([
            'user_id' => auth()->id(),
            'type' => $this->type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'reason' => $this->reason,
            'status' => 'Pending',
        ]);

        $this->reset(['start_date', 'end_date', 'reason']);
        $this->dispatch('notify', message: 'Pengajuan cuti berhasil dikirim!');
    }
}; ?>

<div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900 grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Form Pengajuan -->
        <div>
            <h2 class="text-xl font-semibold mb-4 text-blue-700">Form Pengajuan Cuti</h2>
            <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded-lg border border-blue-100 flex justify-between items-center">
                <span>Sisa Kuota Cuti Tahunan:</span>
                <span class="text-2xl font-bold">{{ $sisaCuti }} Hari</span>
            </div>

            <form wire:submit="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jenis Cuti</label>
                    <select wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="Tahunan">Cuti Tahunan (Min. H-7)</option>
                        <option value="Sakit">Cuti Sakit / Darurat</option>
                    </select>
                    @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                        <input type="date" wire:model="start_date" min="{{ $minDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('start_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                        <input type="date" wire:model="end_date" min="{{ $start_date ?: $minDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('end_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Alasan</label>
                    <textarea wire:model="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Kirim Pengajuan</span>
                    <span wire:loading wire:target="submit">Memproses...</span>
                </button>
            </form>
        </div>

        <!-- Riwayat Cuti -->
        <div>
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Riwayat Pengajuan</h2>
            <div class="space-y-3 max-h-[400px] overflow-y-auto">
                @forelse($myLeaves as $leave)
                    <div class="p-4 border rounded-lg {{ $leave->status === 'Pending' ? 'bg-yellow-50 border-yellow-200' : ($leave->status === 'Approved' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') }}">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-bold text-gray-800">{{ $leave->type }}</span>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full {{ $leave->status === 'Pending' ? 'bg-yellow-200 text-yellow-800' : ($leave->status === 'Approved' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800') }}">
                                {{ $leave->status }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-2 line-clamp-2">"{{ $leave->reason }}"</p>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Belum ada riwayat cuti.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

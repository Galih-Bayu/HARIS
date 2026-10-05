<?php

use Livewire\Volt\Component;
use App\Models\Attendance;
use Carbon\Carbon;

new class extends Component {
    public $todayAttendance;
    
    // Koordinat Kantor (Contoh: Monas Jakarta)
    public float $officeLat = -6.1753924;
    public float $officeLng = 106.8271528;
    public int $maxRadius = 100000; // 100km agar Anda tidak error saat testing di rumah
    
    public function mount()
    {
        $this->loadAttendance();
    }
    
    public function loadAttendance()
    {
        $this->todayAttendance = Attendance::where('user_id', auth()->id())
            ->where('date', Carbon::today())
            ->first();
    }

    // Fungsi Matematika Haversine untuk hitung jarak dalam meter
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    public function clockIn($lat = null, $lng = null, $photoData = null)
    {
        if ($this->todayAttendance) return;
        
        if (!$lat || !$lng) {
            $this->dispatch('notify', message: 'Gagal! Harap izinkan akses lokasi GPS Anda.');
            return;
        }

        $distance = $this->calculateDistance($lat, $lng, $this->officeLat, $this->officeLng);
        
        if ($distance > $this->maxRadius) {
            $this->dispatch('notify', message: 'Gagal! Anda di luar area kantor (' . round($distance) . 'm). Radius maks: ' . $this->maxRadius . 'm.');
            return;
        }

        // Simpan Foto Webcam
        $photoPath = null;
        if ($photoData) {
            $photoParts = explode(';base64,', $photoData);
            $imageTypeAux = explode('image/', $photoParts[0]);
            $imageType = $imageTypeAux[1] ?? 'jpg';
            $imageBase64 = base64_decode($photoParts[1]);
            $fileName = 'foto_absen_' . auth()->id() . '_' . time() . '.' . $imageType;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put('attendances/' . $fileName, $imageBase64);
            $photoPath = 'attendances/' . $fileName;
        }

        $now = Carbon::now();
        $standardTime = Carbon::today()->setHour(8)->setMinute(0)->setSecond(0);
        
        $lateMinutes = 0;
        $status = 'Hadir';
        
        if ($now->greaterThan($standardTime)) {
            $lateMinutes = $standardTime->diffInMinutes($now);
            $status = 'Telat';
        }

        Attendance::create([
            'user_id' => auth()->id(),
            'date' => $now->toDateString(),
            'clock_in' => $now->toTimeString(),
            'latitude' => $lat,
            'longitude' => $lng,
            'photo_path' => $photoPath,
            'late_minutes' => $lateMinutes,
            'status' => $status,
        ]);

        $this->loadAttendance();
        $this->dispatch('notify', message: 'Clock In berhasil! Jarak Anda: ' . round($distance) . ' meter.');
    }

    public function clockOut()
    {
        if (!$this->todayAttendance || $this->todayAttendance->clock_out) return;

        $this->todayAttendance->update([
            'clock_out' => Carbon::now()->toTimeString(),
        ]);

        $this->loadAttendance();
        $this->dispatch('notify', message: 'Berhasil Clock Out!');
    }
}; ?>

<div>
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-xl font-semibold mb-4 text-blue-700">Smart Attendance <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded ml-2">📍 GPS Enabled</span></h2>
        
        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-lg bg-slate-100">
            <div class="text-3xl font-mono font-bold text-gray-700 mb-6" x-data="{ time: new Date().toLocaleTimeString('id-ID') }" x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID'), 1000)" x-text="time">
            </div>

            @if(!$todayAttendance)
                <div x-data="{
                        isLocating: false,
                        cameraOpen: false,
                        stream: null,
                        openCamera() {
                            this.cameraOpen = true;
                            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                                navigator.mediaDevices.getUserMedia({ video: true })
                                    .then(stream => {
                                        this.stream = stream;
                                        this.$refs.video.srcObject = stream;
                                    })
                                    .catch(err => {
                                        this.handleCameraFail();
                                    });
                            } else {
                                this.handleCameraFail();
                            }
                        },
                        handleCameraFail() {
                            alert('GAGAL: Kamera tidak terdeteksi atau izin ditolak. Anda WAJIB menggunakan Webcam untuk melakukan absensi (Selfie).');
                            this.cameraOpen = false;
                        },
                        takeSnapshotAndLocate() {
                            if (!this.stream) return;
                            
                            // Ambil foto dari video
                            const video = this.$refs.video;
                            const canvas = document.createElement('canvas');
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            canvas.getContext('2d').drawImage(video, 0, 0);
                            const photoDataUrl = canvas.toDataURL('image/jpeg');
                            
                            // Matikan kamera
                            this.stream.getTracks().forEach(track => track.stop());
                            this.cameraOpen = false;

                            this.proceedToLocation(photoDataUrl);
                        },
                        proceedToLocation(photoDataUrl) {
                            this.isLocating = true;
                            // Lanjutkan dengan Geolocation
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        $wire.clockIn(position.coords.latitude, position.coords.longitude, photoDataUrl);
                                        this.isLocating = false;
                                    },
                                    (error) => {
                                        alert('Gagal mendapatkan lokasi GPS. Pastikan GPS diizinkan.');
                                        this.isLocating = false;
                                    },
                                    { enableHighAccuracy: true }
                                );
                            } else {
                                alert('Browser Anda tidak mendukung Geolocation.');
                                this.isLocating = false;
                            }
                        }
                    }" class="w-full max-w-sm mx-auto flex flex-col items-center">
                    
                    <!-- Area Kamera -->
                    <div x-show="cameraOpen" class="w-full mb-4 rounded-lg overflow-hidden border-4 border-blue-500 shadow-lg bg-black relative">
                        <video x-ref="video" autoplay playsinline class="w-full h-auto"></video>
                        <div class="absolute bottom-2 inset-x-0 flex justify-center">
                            <button x-on:click="takeSnapshotAndLocate" class="bg-white text-blue-600 px-6 py-2 rounded-full font-bold shadow-lg hover:bg-gray-100 flex items-center">
                                <span x-show="!isLocating" class="flex items-center">📸 AMBIL FOTO & CLOCK IN</span>
                                <span x-show="isLocating">Memproses Data...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Buka Kamera -->
                    <button x-show="!cameraOpen && !isLocating" x-on:click="openCamera" class="w-full px-8 py-3 bg-blue-600 text-white rounded-full font-bold shadow-lg hover:bg-blue-700 transition transform hover:scale-105 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> 
                        BUKA KAMERA UNTUK ABSEN
                    </button>
                </div>
                <p class="mt-4 text-sm text-gray-500">Jam Masuk Standar: 08:00</p>
                <p class="mt-1 text-xs text-blue-500">Izin Kamera & Lokasi dibutuhkan untuk validasi kehadiran.</p>
            @elseif(!$todayAttendance->clock_out)
                <div class="text-center mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $todayAttendance->status === 'Telat' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        Status: {{ $todayAttendance->status }} 
                        @if($todayAttendance->status === 'Telat')
                            ({{ $todayAttendance->late_minutes }} menit)
                        @endif
                    </span>
                    <p class="text-gray-600 mt-2">Waktu masuk: {{ $todayAttendance->clock_in }}</p>
                    <p class="text-xs text-gray-400 mt-1">Titik Absen: {{ substr($todayAttendance->latitude, 0, 7) }}, {{ substr($todayAttendance->longitude, 0, 7) }}</p>
                </div>
                <button wire:click="clockOut" class="px-8 py-3 bg-red-600 text-white rounded-full font-bold shadow-lg hover:bg-red-700 transition transform hover:scale-105">
                    <span wire:loading.remove wire:target="clockOut">CLOCK OUT</span>
                    <span wire:loading wire:target="clockOut">Processing...</span>
                </button>
            @else
                <div class="text-center text-green-600">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-lg font-bold">Terima kasih!</h3>
                    <p>Anda sudah menyelesaikan absensi hari ini.</p>
                    <p class="text-sm mt-2 text-gray-500">Masuk: {{ $todayAttendance->clock_in }} | Keluar: {{ $todayAttendance->clock_out }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

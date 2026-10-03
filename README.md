# HRIS Lite: Smart Attendance & Leave Management System

Aplikasi web HRIS (Human Resource Information System) modern yang dibangun menggunakan ekosistem **Laravel 13** dan **TALL Stack (Tailwind, Alpine.js, Laravel, Livewire)**. Proyek ini tidak hanya berfokus pada operasi CRUD standar, melainkan menekankan logika bisnis dunia nyata, interaktivitas ala *Single Page Application* (SPA), dan validasi backend yang sangat ketat.

## 🚀 Fitur Utama & Logika Bisnis

### 1. Smart Clock-In / Clock-Out (Kehadiran)
*   **Anti-Manipulasi Waktu:** Sistem menggunakan `Carbon::now()` di sisi server, mengabaikan jam dari perangkat pengguna.
*   **Deteksi Keterlambatan Otomatis:** Sistem mendeteksi jam 08:00 WIB sebagai standar. Jika absen lebih dari jam tersebut, fungsi `diffInMinutes()` dari Carbon otomatis menghitung total menit keterlambatan.
*   **Reactive UI (Volt & Alpine.js):** Tombol absensi menggunakan komponen *single-file* **Livewire Volt** yang memberikan pengalaman SPA (berubah instan dari *Clock In* ke *Clock Out* tanpa *reload*). **Alpine.js** menangani *Toast Notification* saat berhasil absen.

### 2. Dynamic Leave Request (Manajemen Cuti)
*   **Validasi Aturan H-7:** Jika karyawan memilih "Cuti Tahunan", tanggal (`min-date`) otomatis terbatasi menjadi 7 hari ke depan. Jika memilih "Cuti Sakit", batasan ini otomatis ter-bypass.
*   **Keamanan Transaksi:** Fitur *Approval* oleh HRD dibungkus dengan **Database Transaction**. Jika HRD menyetujui, status berubah menjadi 'Approved' dan kuota saldo `sisa_cuti` karyawan terpotong secara serentak. Jika satu proses gagal, seluruhnya dibatalkan (*Rollback*) mencegah ketidaksesuaian data.
*   **Role-Based Access Control:** Fitur ini diamankan dengan *Spatie Laravel Permission*.

### 3. HRD Dashboard & Export (Pelaporan)
*   **File-Based Routing:** Menggunakan **Laravel Folio**, halaman laporan dirender murni berdasarkan struktur file tanpa perlu konfigurasi di `web.php`.
*   **Export Data Payroll:** Terintegrasi dengan **Maatwebsite Excel**. HRD dapat mengunduh rekap `.xlsx` yang berisi riwayat absensi harian dan rekap menit keterlambatan yang siap diserahkan ke bagian Keuangan (Payroll).

### 4. Automated Task Scheduling
*   **Otomatisasi Tahun Baru:** Menggunakan *Laravel Scheduler*, terdapat logika otomatis (`yearly()`) yang akan berjalan setiap tanggal 1 Januari jam 00:00 untuk me-reset sisa cuti semua karyawan kembali menjadi 12 hari.

## 💻 Tech Stack
*   **Core:** PHP 8.4, Laravel 13, MySQL
*   **Frontend:** Tailwind CSS, Alpine.js, Laravel Livewire 3 (Volt)
*   **Routing:** Laravel Folio
*   **Packages:** Spatie Permission, Maatwebsite Excel, Carbon

## 🔑 Demo Akun (Testing)
Jalankan `php artisan serve` dan gunakan akun berikut:
*   **HRD:** `hrd@hris.com` / `password`
*   **Karyawan:** `karyawan@hris.com` / `password`

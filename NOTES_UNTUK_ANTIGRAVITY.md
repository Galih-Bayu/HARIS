# 🤖 AI HANDOFF & PROJECT CONTEXT (Untuk Antigravity Selanjutnya)

Halo Antigravity! Jika Anda membaca file ini, berarti USER baru saja memindahkan proyek ini ke komputer baru dan meminta Anda untuk melanjutkan pengembangannya. 

Harap baca seluruh dokumen ini untuk memahami konteks penuh aplikasi ini sebelum menyentuh kode apa pun.

---

## 🎯 Identitas Proyek
*   **Nama:** HRIS Lite (Smart Attendance & Leave Management)
*   **Tujuan:** Proyek Portofolio kelas *Enterprise* untuk memukau Recruiter/Senior Developer.
*   **Status Terakhir:** 100% Fitur Utama Selesai. (Sedang dalam Mode Testing Warnet).

## 💻 Tech Stack yang Digunakan
1.  **Backend:** Laravel 12 (PHP 8.2+) dengan Database MySQL.
2.  **Frontend / UI:** TALL Stack (Tailwind CSS, Alpine.js, Livewire 3).
    *   *Perhatian Khusus:* Sebagian besar logika Livewire ditulis menggunakan **Laravel Volt** (Class API / Single-File Component) yang terletak di folder `resources/views/livewire/`.
3.  **Routing:** Menggunakan **Laravel Folio** (File-based routing) di folder `resources/views/pages/`.
4.  **Packages:**
    *   `spatie/laravel-permission` (Role: HRD & Karyawan).
    *   `maatwebsite/excel` (Untuk Export Excel di halaman HRD).
    *   `FullCalendar.js` (Via CDN untuk Kalender Cuti).
    *   `Chart.js` (Via CDN untuk Dashboard Analytics).

## 🚀 Fitur yang Sudah Berjalan (JANGAN DIRUSAK)
1.  **Smart Attendance (Geolocation + Webcam):**
    *   Lokasi komponen: `resources/views/livewire/attendance.blade.php`.
    *   Validasi Keterlambatan: Otomatis via `Carbon` (jam masuk standar: 08:00).
    *   Validasi Jarak: Menggunakan **Rumus Haversine** di PHP (Saat ini radius 100km untuk testing, koordinat pusat di Monas).
    *   Webcam: Menyimpan *Base64* dari `canvas` HTML menjadi file `.jpg` via Laravel Storage.
2.  **Leave Management (Pengajuan Cuti):**
    *   Validasi H-7 untuk cuti Tahunan (Livewire validation). Cuti Sakit bypass H-7.
    *   Approval HRD (`livewire/leave-approvals.blade.php`) dibungkus **Database Transaction**. Saat disetujui, otomatis memotong kolom `sisa_cuti` milik Karyawan.
3.  **HRD Dashboard & Analytics:**
    *   Berada di `resources/views/livewire/hrd-dashboard.blade.php`. Terdapat metrik jumlah karyawan, donut chart (Chart.js), dan FullCalendar status cuti.
4.  **Folio Pages & Excel Export:**
    *   `/karyawan` (HRD membuat akun karyawan baru tanpa register form).
    *   `/laporan` (Rekap absensi & tombol download Excel via rute `export.attendance`).
5.  **Task Scheduler:**
    *   Di `routes/console.php`, terdapat cron job `yearly()` yang mereset `sisa_cuti` karyawan menjadi 12 hari setiap 1 Januari.

---

## 🛠️ CARA MENJALANKAN PROYEK DI KOMPUTER BARU
Jika USER baru memindahkan proyek ini, pandu USER untuk melakukan ini di terminal:
1. `composer install` & `npm install`
2. Atur file `.env` (Buat database MySQL baru misal `db_hris`).
3. `php artisan key:generate`
4. `php artisan migrate:fresh --seed` (Seeder akan membuat role dan akun dummy).
5. `php artisan storage:link` (SANGAT PENTING untuk menampilkan foto absen).
6. `npm run build` & `php artisan serve`.

**Akun Testing Default:**
*   HRD: `hrd@hris.com` / `password`
*   Karyawan: `karyawan@hris.com` / `password`

---

## 🔴 TODO UTAMA SAAT INI: Kembalikan Kewajiban Absen Pakai Kamera

Saat ini aplikasi berada dalam **Mode Testing (Bypass Kamera)** karena saat awal dibangun, USER sedang berada di PC (Warnet) yang tidak memiliki Webcam.

📂 **File yang Harus Diedit:** `resources/views/livewire/attendance.blade.php`

**Instruksi:**
Cari fungsi `handleCameraFail()` di dalam script JS Alpine (`x-data`), dan ubah fungsinya menjadi Mode Ketat (Strict) seperti berikut:

```javascript
handleCameraFail() {
    alert('GAGAL: Kamera tidak terdeteksi atau izin ditolak. Anda WAJIB menggunakan Webcam untuk melakukan absensi (Selfie).');
    this.cameraOpen = false;
}
```
Dengan ini, karyawan tidak akan bisa mem-bypass absen tanpa foto.

---
*End of Context. Selamat membantu USER!* 🚀

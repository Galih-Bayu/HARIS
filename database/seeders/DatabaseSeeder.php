<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Roles
        $hrdRole = Role::create(['name' => 'HRD']);
        $karyawanRole = Role::create(['name' => 'Karyawan']);

        // 2. Buat User HRD
        $hrd = User::create([
            'name' => 'Pak HRD',
            'email' => 'hrd@hris.com',
            'password' => Hash::make('password'),
            'sisa_cuti' => 12,
        ]);
        $hrd->assignRole($hrdRole);

        // 3. Buat User Karyawan
        $karyawan = User::create([
            'name' => 'Budi Karyawan',
            'email' => 'karyawan@hris.com',
            'password' => Hash::make('password'),
            'sisa_cuti' => 12,
        ]);
        $karyawan->assignRole($karyawanRole);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin123'),
            'nip' => '123456789',
            'employee_class' => 'IV/a',
            'job_title' => 'Kepala Bagian SDM',
            'unit_id' => Unit::firstOrCreate(['name' => 'Manajemen'])->id,
        ]);

        // Dokter
        User::create([
            'name' => 'Dr. Budi Santoso',
            'email' => 'dokter@example.com',
            'password' => Hash::make('dokter123'),
            'nip' => '987654321',
            'employee_class' => 'III/d',
            'job_title' => 'Dokter Spesialis Penyakit Dalam',
            'unit_id' => Unit::firstOrCreate(['name' => 'Dokter'])->id,
        ]);
        User::create([
            'name' => 'Daffa',
            'email' => 'daffa@example.com',
            'password' => Hash::make('daffa123'),
            'nip' => '2312412',
            'employee_class' => 'III/d',
            'job_title' => 'Dokter Penyakit',
            'unit_id' => 2,
        ]);
        User::create([
            'name' => 'Rani',
            'email' => 'rani@example.com',
            'password' => Hash::make('rani123'),
            'nip' => '673334345',
            'employee_class' => 'III/d',
            'job_title' => 'Dokter Gigi',
            'unit_id' => 3,
        ]);
    }
} 
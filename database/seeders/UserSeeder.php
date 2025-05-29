<?php

namespace Database\Seeders;

use App\Models\JobTitle;
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
            'role' => 'admin',
            'nip' => '123456789',
            'employee_class' => 'IV/a',
            'title_complete' => 'Kepala Bagian SDM',
            'job_title_id' => JobTitle::firstOrCreate(['name' => 'Manajemen'])->id,
        ]);

        // Dokter
        User::create([
            'name' => 'Dr. Budi Santoso',
            'email' => 'dokter@example.com',
            'password' => Hash::make('dokter123'),
            'role' => 'user',
            'nip' => '987654321',
            'employee_class' => 'III/d',
            'title_complete' => 'Dokter Spesialis Penyakit Dalam',
            'job_title_id' => JobTitle::firstOrCreate(['name' => 'Dokter'])->id,
        ]);
        User::create([
            'name' => 'Daffa',
            'email' => 'daffa@example.com',
            'password' => Hash::make('daffa123'),
            'role' => 'admin',
            'nip' => '2312412',
            'employee_class' => 'III/d',
            'title_complete' => 'Dokter Penyakit',
            'job_title_id' => 2,
        ]);
        User::create([
            'name' => 'Rani',
            'email' => 'Rani@example.com',
            'password' => Hash::make('rani123'),
            'role' => 'user',
            'nip' => '673334345',
            'employee_class' => 'III/d',
            'title_complete' => 'Dokter Gigi',
            'job_title_id' => 3,
        ]);
    }
} 
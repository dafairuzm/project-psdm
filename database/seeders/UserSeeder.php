<?php

namespace Database\Seeders;

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
            'job_title' => 'manajemen',
            'title_complete' => 'Kepala Bagian SDM'
        ]);

        // Dokter
        User::create([
            'name' => 'Dr. Budi Santoso',
            'email' => 'dokter@example.com',
            'password' => Hash::make('dokter123'),
            'role' => 'user',
            'nip' => '987654321',
            'employee_class' => 'III/d',
            'job_title' => 'dokter',
            'title_complete' => 'Dokter Spesialis Penyakit Dalam'
        ]);
    }
} 
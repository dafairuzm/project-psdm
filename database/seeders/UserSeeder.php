<?php

namespace Database\Seeders;

use App\Models\Profession;
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
            'gender' => 'laki-laki',
            'employee_class' => 'IV/a',
            'employee_type' => 'ASN PNS',
            'job_title' => 'Kepala Bagian SDM',
            'profession_id' => Profession::firstOrCreate(['name' => 'Manajemen'])->id,
            'Unit_id' => 1,
        ]);

        // Dokter
        User::create([
            'name' => 'Dr. Budi Santoso',
            'email' => 'dokter@example.com',
            'password' => Hash::make('dokter123'),
            'nip' => '987654321',
            'gender' => 'laki-laki',
            'employee_class' => 'III/a',
            'employee_type' => 'ASN PNS',
            'job_title' => 'Dokter Spesialis Penyakit Dalam',
            'profession_id' => Profession::firstOrCreate(['name' => 'Dokter'])->id,
            'Unit_id' => 1,
        ]);
        User::create([
            'name' => 'Daffa',
            'email' => 'daffa@example.com',
            'password' => Hash::make('daffa123'),
            'nip' => '2312412',
            'gender' => 'laki-laki',
            'employee_class' => 'IV/a',
            'employee_type' => 'ASN PNS',
            'job_title' => 'Dokter Penyakit',
            'profession_id' => 2,
            'Unit_id' => 2,
        ]);
        User::create([
            'name' => 'Rani',
            'email' => 'rani@example.com',
            'password' => Hash::make('rani123'),
            'nip' => '673334345',
            'gender' => 'laki-laki',
            'employee_class' => 'IV/d',
            'employee_type' => 'ASN PPPK',
            'job_title' => 'Dokter Gigi',
            'profession_id' => 3,
            'Unit_id' => 3,
        ]);
    }
} 
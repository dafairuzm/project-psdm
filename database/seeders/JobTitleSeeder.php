<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        JobTitle::create([
            'name' => 'Dokter',
            'description' => 'Tenaga medis profesional yang bertanggung jawab dalam mendiagnosis dan menangani kondisi kesehatan pasien.',
        ]);
        JobTitle::create([
            'name' => 'Perawat',
            'description' => 'Tenaga kesehatan yang memberikan asuhan keperawatan dan perawatan pasien di berbagai unit layanan medis.',
        ]);
        JobTitle::create([
            'name' => 'Bidan',
            'description' => 'Tenaga kesehatan yang fokus pada pelayanan kehamilan, persalinan, nifas, dan kesehatan reproduksi wanita.',
        ]);
        JobTitle::create([
            'name' => 'Manajemen',
            'description' => 'Pegawai yang terlibat dalam pengelolaan, perencanaan, dan pengambilan keputusan di lingkungan rumah sakit.',
        ]);
        JobTitle::create([
            'name' => 'T.Kes Lain',
            'description' => 'Tenaga kesehatan lain di luar dokter, perawat, dan bidan seperti apoteker, analis laboratorium, fisioterapis, dll.',
        ]);
    }
} 
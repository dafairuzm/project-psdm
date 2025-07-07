<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionsSeeder extends Seeder
{
    public function run(): void
    {
        Profession::create([
            'name' => 'Dokter',
            'description' => 'Tenaga medis profesional yang bertanggung jawab dalam mendiagnosis dan menangani kondisi kesehatan pasien.',
        ]);
        Profession::create([
            'name' => 'Perawat',
            'description' => 'Tenaga kesehatan yang memberikan asuhan keperawatan dan perawatan pasien di berbagai unit layanan medis.',
        ]);
        Profession::create([
            'name' => 'Bidan',
            'description' => 'Tenaga kesehatan yang fokus pada pelayanan kehamilan, persalinan, nifas, dan kesehatan reproduksi wanita.',
        ]);
        Profession::create([
            'name' => 'Manajemen',
            'description' => 'Pegawai yang terlibat dalam pengelolaan, perencanaan, dan pengambilan keputusan di lingkungan rumah sakit.',
        ]);
        Profession::create([
            'name' => 'T.Kes Lain',
            'description' => 'Tenaga kesehatan lain di luar dokter, perawat, dan bidan seperti apoteker, analis laboratorium, fisioterapis, dll.',
        ]);
        Profession::create([
            'name' => 'Lain-lain',
            'description' => 'Profesi pegawai belum ter identifikasi dengan jelas',
        ]);
    }
} 
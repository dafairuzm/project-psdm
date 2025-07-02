<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        Unit::create([
            'name' => 'Dokter',
            'description' => 'Tenaga medis profesional yang bertanggung jawab dalam mendiagnosis dan menangani kondisi kesehatan pasien.',
        ]);
        Unit::create([
            'name' => 'Perawat',
            'description' => 'Tenaga kesehatan yang memberikan asuhan keperawatan dan perawatan pasien di berbagai unit layanan medis.',
        ]);
        Unit::create([
            'name' => 'Bidan',
            'description' => 'Tenaga kesehatan yang fokus pada pelayanan kehamilan, persalinan, nifas, dan kesehatan reproduksi wanita.',
        ]);
        Unit::create([
            'name' => 'Manajemen',
            'description' => 'Pegawai yang terlibat dalam pengelolaan, perencanaan, dan pengambilan keputusan di lingkungan rumah sakit.',
        ]);
        Unit::create([
            'name' => 'T.Kes Lain',
            'description' => 'Tenaga kesehatan lain di luar dokter, perawat, dan bidan seperti apoteker, analis laboratorium, fisioterapis, dll.',
        ]);
    }
} 
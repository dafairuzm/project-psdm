<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::create([
            'name' => 'Manager Pelayanan Pasien',
        ]);
        Unit::create([
            'name' => 'Instalasi Bedah Sentral',
        ]);
        Unit::create([
            'name' => 'Instalasi Gawat Darurat',
        ]);
    }
}

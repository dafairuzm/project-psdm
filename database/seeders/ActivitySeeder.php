<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Activity::create([
            'title' => 'Seminar Dokter Jaya',
            'type' => 'Dinas',
            'organizer' => 'Divisi Pelatihan',
            'location' => 'Ruang 401, Gedung A',
            'start_date' => Carbon::parse('2025-05-15'),
            'finish_date' => Carbon::parse('2025-05-15'),
            'duration' => 3
        ]);
        Activity::create([
            'title' => 'Workshop Digital',
            'type' => 'Dinas',
            'organizer' => 'PMI',
            'location' => 'Ruang 401, Gedung A',
            'start_date' => Carbon::parse('2025-05-15'),
            'finish_date' => Carbon::parse('2025-05-16'),
            'duration' => 3
        ]);
        Activity::create([
            'title' => 'Diklat Dokter Gigi',
            'type' => 'Mandiri',
            'organizer' => 'Kemenkes',
            'location' => 'Ruang 401, Gedung A',
            'start_date' => Carbon::parse('2025-05-15'),
            'finish_date' => Carbon::parse('2025-05-18'),
            'duration' => 3
        ]);
    }
}

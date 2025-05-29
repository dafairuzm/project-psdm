<?php

namespace Database\Seeders;

use App\Models\ActivityCategory;
use Illuminate\Database\Seeder;

class ActivityCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ActivityCategory::create([
            'name' => 'Diklat',
            'description' => 'Pendidikan dan Pelatihan (Diklat) adalah kegiatan yang dirancang untuk meningkatkan kompetensi, keterampilan, dan pengetahuan pegawai melalui proses belajar yang terstruktur dalam jangka waktu tertentu. Diklat biasanya bersifat formal dan dapat bersertifikat.',
        ]);
        ActivityCategory::create([
            'name' => 'Bimtek',
            'description' => 'Bimbingan Teknis (Bimtek) adalah kegiatan pelatihan singkat yang bersifat praktis dan teknis untuk meningkatkan pemahaman pegawai terhadap suatu bidang atau tugas tertentu. Bimtek bertujuan memberikan pemahaman aplikatif dalam waktu yang relatif singkat.',
        ]);
        ActivityCategory::create([
            'name' => 'Workshop',
            'description' => 'Workshop adalah kegiatan pelatihan interaktif yang fokus pada pemecahan masalah atau pengembangan keterampilan tertentu. Dalam workshop, peserta aktif berdiskusi, melakukan praktik langsung, dan menghasilkan output atau solusi tertentu.',
        ]);
        ActivityCategory::create([
            'name' => 'Seminar',
            'description' => 'Seminar adalah forum ilmiah atau diskusi yang membahas topik tertentu secara mendalam, biasanya menghadirkan narasumber atau pakar di bidangnya. Tujuan seminar adalah memberikan wawasan baru, pertukaran ilmu, dan diskusi terbuka antar peserta.',
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_activity_id')->constrained('user_activity')->onDelete('cascade'); // relasi ke peserta kegiatan
                $table->date('date');
                $table->enum('status', ['Hadir', 'Tidak Hadir', 'Belum Diisi'])->default('Belum Diisi'); // default "Belum Diisi"
                $table->timestamps();
            
                // $table->unique(['user_activity_id', 'date']); // Supaya 1 peserta cuma 1 absensi per hari        
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

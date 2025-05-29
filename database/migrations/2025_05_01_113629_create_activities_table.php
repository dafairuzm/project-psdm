<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['exhouse', 'inhouse']);
            $table->string('speaker')->nullable();
            $table->string('organizer')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('finish_date');
            $table->integer('duration')->nullable(); // dalam jam pelajaran
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

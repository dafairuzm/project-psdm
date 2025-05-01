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
            $table->string('type');
            $table->foreignId('category_id')->constrained('activity_categories')->onDelete('cascade');
            $table->string('speaker')->nullable();
            $table->string('organizer');
            $table->string('location');
            $table->dateTime('start_date');
            $table->dateTime('finish_date');
            $table->integer('duration'); // dalam jam pelajaran
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

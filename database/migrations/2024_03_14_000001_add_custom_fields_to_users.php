<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user');
            $table->string('nip')->unique();
            $table->string('employee_class');
            $table->enum('job_title', ['dokter', 'perawat', 't.kes lain', 'manajemen']);
            $table->string('title_complete');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'nip', 'employee_class', 'job_title', 'title_complete']);
        });
    }
}; 
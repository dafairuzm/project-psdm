<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable();
            $table->enum('gender', ['laki-laki', 'perempuan']);
            $table->enum('employee_type', ['ASN PNS', 'ASN PPPK','BLUD PHL', 'BLUD PTT', 'BLUD TETAP', 'KSO']);
            $table->string('employee_class')->nullable();
            $table->string('job_title')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'employee_class', 'job_title']);
        });
    }
}; 
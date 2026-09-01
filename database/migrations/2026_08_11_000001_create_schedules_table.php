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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('hari')->nullable()->index(); // senin, selasa, rabu, kamis, jumat, sabtu, minggu
            $table->date('tanggal')->nullable()->index();
            $table->time('jam_masuk')->default('08:00:00');
            $table->time('jam_istirahat')->default('12:00:00');
            $table->time('jam_masuk_istirahat')->default('13:00:00');
            $table->time('jam_pulang')->default('17:00:00');
            $table->boolean('is_libur')->default(false);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};

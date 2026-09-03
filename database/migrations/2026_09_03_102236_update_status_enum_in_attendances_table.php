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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `attendances` MODIFY COLUMN `status` ENUM('tepat_waktu', 'terlambat', 'lebih_awal', 'izin', 'sakit') NOT NULL DEFAULT 'tepat_waktu'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `attendances` MODIFY COLUMN `status` ENUM('tepat_waktu', 'terlambat', 'lebih_awal') NOT NULL DEFAULT 'tepat_waktu'");
    }
};

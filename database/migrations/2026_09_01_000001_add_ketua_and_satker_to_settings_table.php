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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('nama_ketua')->nullable()->default('Isnurul Syamsyul Arif')->after('nama_kantor');
            $table->string('jabatan_ketua')->nullable()->default('Ketua Pengadilan Tinggi Pontianak')->after('nama_ketua');
            $table->string('nip_ketua')->nullable()->after('jabatan_ketua');
            $table->string('satker_name')->nullable()->default('PENGADILAN TINGGI PONTIANAK')->after('nip_ketua');
            $table->string('kota_surat')->nullable()->default('Pontianak')->after('satker_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['nama_ketua', 'jabatan_ketua', 'nip_ketua', 'satker_name', 'kota_surat']);
        });
    }
};

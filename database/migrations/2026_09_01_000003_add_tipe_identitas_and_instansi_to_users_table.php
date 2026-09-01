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
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipe_identitas', 10)->default('nip')->after('nip'); // 'nip', 'nim', 'nisn'
            $table->string('jenis_pegawai', 30)->default('pegawai')->after('tipe_identitas'); // 'pegawai', 'mahasiswa_magang', 'siswa_magang'
            $table->string('asal_instansi')->nullable()->after('jabatan'); // Kampus / Sekolah / Instansi asal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tipe_identitas', 'jenis_pegawai', 'asal_instansi']);
        });
    }
};

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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('jenis_cuti', ['cuti_tahunan', 'cuti_sakit', 'cuti_luar_negeri', 'cuti_alasan_penting', 'cuti_lainnya'])->default('cuti_tahunan');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('jumlah_hari')->default(1);
            $table->text('alasan')->nullable();
            $table->string('dokumen_pendukung')->nullable(); // Foto / Surat Keterangan / Tiket / Paspor
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('disetujui');
            $table->string('catatan_operator')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};

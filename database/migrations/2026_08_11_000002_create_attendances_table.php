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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('tipe', ['masuk', 'istirahat', 'masuk_istirahat', 'pulang']);
            $table->timestamp('waktu');
            $table->string('foto');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('alamat')->nullable();
            $table->enum('status', ['tepat_waktu', 'terlambat', 'lebih_awal'])->default('tepat_waktu');
            $table->timestamps();

            $table->unique(['user_id', 'tanggal', 'tipe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

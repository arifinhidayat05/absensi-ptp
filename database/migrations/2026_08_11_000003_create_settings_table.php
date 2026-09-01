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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kantor')->default('Kantor Pusat');
            $table->decimal('latitude_kantor', 10, 7)->default(-6.2088000);
            $table->decimal('longitude_kantor', 10, 7)->default(106.8456000);
            $table->integer('radius_meter')->default(200); // Default 200 meter
            $table->boolean('enforce_radius')->default(true); // Validasi radius aktif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

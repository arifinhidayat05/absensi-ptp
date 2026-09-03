<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Pengaturan Instansi & Radius Geofencing GPS
 *
 * Mengelola konfigurasi pusat Pengadilan Tinggi Pontianak meliputi:
 * 1. Titik koordinat GPS kantor (Latitude & Longitude) serta radius toleransi (meter).
 * 2. Status penegakan radius (enforce radius: wajib di dalam area kantor atau bebas).
 * 3. Identitas resmi pimpinan penandatangan laporan (Ketua Pengadilan, Jabatan, NIP, Satker, Kota Surat).
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * Kolom tabel yang dapat diisi secara massal.
     */
    protected $fillable = [
        'nama_kantor',
        'nama_ketua',
        'jabatan_ketua',
        'nip_ketua',
        'satker_name',
        'kota_surat',
        'tampilkan_mengetahui',
        'latitude_kantor',
        'longitude_kantor',
        'radius_meter',
        'enforce_radius',
    ];

    /**
     * Tipe konversi atribut otomatis oleh Eloquent.
     */
    protected $casts = [
        'latitude_kantor' => 'float',
        'longitude_kantor' => 'float',
        'radius_meter' => 'integer',
        'enforce_radius' => 'boolean',
        'tampilkan_mengetahui' => 'boolean',
    ];

    /**
     * Default nilai tampilkan_mengetahui ke true jika belum diset
     */
    public function getTampilkanMengetahuiAttribute($value)
    {
        if ($value !== null) {
            return (bool)$value;
        }

        // Cek fallback cache jika kolom belum ada di database server
        $cached = cache()->get('setting_tampilkan_mengetahui');
        if ($cached !== null) {
            return $cached === '1' || $cached === true || $cached === 1;
        }

        return true;
    }

    /**
     * Mengambil baris pengaturan instansi pertama (singleton), atau membuat pengaturan default jika belum ada.
     *
     * @return Setting Objek pengaturan kantor
     */
    public static function getOfficeSetting()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('settings', 'tampilkan_mengetahui')) {
                \Illuminate\Support\Facades\Schema::table('settings', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('tampilkan_mengetahui')->default(true)->after('kota_surat');
                });
            }
        } catch (\Throwable $e) {
            // Fallback aman jika database tidak mengizinkan alter table langsung
        }

        $setting = self::first();
        if (!$setting) {
            $setting = self::create([
                'nama_kantor' => 'Kantor Utama / Pusat',
                'nama_ketua' => 'Isnurul Syamsyul Arif',
                'jabatan_ketua' => 'Ketua Pengadilan Tinggi Pontianak',
                'nip_ketua' => null,
                'satker_name' => 'PENGADILAN TINGGI PONTIANAK',
                'kota_surat' => 'Pontianak',
                'tampilkan_mengetahui' => true,
                'latitude_kantor' => -0.0576339,
                'longitude_kantor' => 109.3516038,
                'radius_meter' => 200,
                'enforce_radius' => true,
            ]);
        }
        return $setting;
    }

    /**
     * Menghitung jarak lurus (orthodromic distance) antara dua titik koordinat GPS dalam satuan meter
     * menggunakan rumus Haversine (Haversine Formula).
     *
     * @param float $lat1 Latitude titik 1 (posisi pegawai)
     * @param float $lon1 Longitude titik 1 (posisi pegawai)
     * @param float $lat2 Latitude titik 2 (posisi kantor)
     * @param float $lon2 Longitude titik 2 (posisi kantor)
     * @return float Jarak dalam meter (dibulatkan ke 2 desimal)
     */
    public static function calculateDistanceInMeters($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Jari-jari rata-rata bumi dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model Daftar Hari Libur Nasional & Cuti Bersama
 *
 * Mengelola tanggal merah kalender resmi Republik Indonesia sehingga sistem dapat
 * secara otomatis meniadakan / memblokir presensi pada hari-hari libur tersebut.
 */
class Holiday extends Model
{
    use HasFactory;

    /**
     * Kolom tabel yang dapat diisi secara massal.
     */
    protected $fillable = [
        'tanggal',
        'nama',
        'keterangan',
        'is_libur_nasional',
    ];

    /**
     * Tipe konversi atribut otomatis oleh Eloquent.
     */
    protected $casts = [
        'tanggal' => 'date',
        'is_libur_nasional' => 'boolean',
    ];

    /**
     * Memeriksa apakah tanggal tertentu terdaftar sebagai hari libur di sistem.
     *
     * @param string|Carbon $date Tanggal yang diperiksa
     * @return bool True jika merupakan hari libur, False jika hari kerja biasa
     */
    public static function isHoliday($date): bool
    {
        $dateStr = Carbon::parse($date)->format('Y-m-d');
        return self::where('tanggal', $dateStr)->exists();
    }

    /**
     * Mengambil rekaman data hari libur untuk tanggal tertentu jika ada.
     *
     * @param string|Carbon $date Tanggal yang dicari
     * @return Holiday|null Objek model Holiday atau null jika bukan hari libur
     */
    public static function getHoliday($date): ?self
    {
        $dateStr = Carbon::parse($date)->format('Y-m-d');
        return self::where('tanggal', $dateStr)->first();
    }

    /**
     * Daftar bawaan Hari Libur Nasional & Cuti Bersama resmi Indonesia (Tahun 2026).
     * Digunakan untuk fitur 1-Klik Otomatisasi Kalender Libur pada menu Operator.
     *
     * @param int $year Tahun kalender (default 2026)
     * @return array Koleksi daftar hari libur nasional
     */
    public static function getDefaultNationalHolidays(int $year = 2026): array
    {
        return [
            ['tanggal' => "{$year}-01-01", 'nama' => 'Tahun Baru Masehi', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-01-16", 'nama' => 'Isra Mi\'raj Nabi Muhammad SAW', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-02-17", 'nama' => 'Tahun Baru Imlek 2577 Kongzili', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-03-19", 'nama' => 'Hari Suci Nyepi Tahun Baru Saka 1948', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-03-20", 'nama' => 'Hari Raya Idul Fitri 1447 H (Hari Ke-1)', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-03-21", 'nama' => 'Hari Raya Idul Fitri 1447 H (Hari Ke-2)', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-03-23", 'nama' => 'Cuti Bersama Idul Fitri 1447 H', 'keterangan' => 'Cuti Bersama'],
            ['tanggal' => "{$year}-03-24", 'nama' => 'Cuti Bersama Idul Fitri 1447 H', 'keterangan' => 'Cuti Bersama'],
            ['tanggal' => "{$year}-04-03", 'nama' => 'Wafat Yesus Kristus (Jumat Agung)', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-05-01", 'nama' => 'Hari Buruh Internasional', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-05-14", 'nama' => 'Kenaikan Yesus Kristus', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-05-27", 'nama' => 'Hari Raya Idul Adha 1447 H', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-05-31", 'nama' => 'Hari Raya Waisak 2570 BE', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-06-01", 'nama' => 'Hari Lahir Pancasila', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-06-16", 'nama' => 'Tahun Baru Islam 1448 H', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-08-17", 'nama' => 'Hari Proklamasi Kemerdekaan RI Ke-81', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-08-25", 'nama' => 'Maulid Nabi Muhammad SAW', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-12-25", 'nama' => 'Hari Raya Natal', 'keterangan' => 'Hari Libur Nasional'],
            ['tanggal' => "{$year}-12-26", 'nama' => 'Cuti Bersama Hari Raya Natal', 'keterangan' => 'Cuti Bersama'],
        ];
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model Pengaturan Jadwal Jam Kerja & Jendela Presensi
 *
 * Mengelola jam target presensi harian (Senin - Jumat dan akhir pekan),
 * serta menghitung pembukaan jendela waktu presensi (15 menit sebelum hingga 15 menit setelah jam target).
 */
class Schedule extends Model
{
    use HasFactory;

    /**
     * Kolom-kolom tabel yang dapat diisi secara massal.
     */
    protected $fillable = [
        'hari',
        'tanggal',
        'jam_masuk',
        'jam_istirahat',
        'jam_masuk_istirahat',
        'jam_pulang',
        'is_libur',
        'keterangan',
    ];

    /**
     * Konversi tipe data atribut model secara otomatis.
     */
    protected $casts = [
        'tanggal' => 'date',
        'is_libur' => 'boolean',
    ];

    /**
     * Mengonversi nomor urut hari ISO (1 = Senin .. 7 = Minggu) menjadi kunci teks nama hari bahasa Indonesia.
     *
     * @param int|string $dayOfWeekNumber Nomor hari ISO (1-7)
     * @return string Kunci hari ('senin', 'selasa', dst.)
     */
    public static function getHariNameIndonesian($dayOfWeekNumber): string
    {
        return match ((int)$dayOfWeekNumber) {
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            7 => 'minggu',
            default => 'senin',
        };
    }

    /**
     * Mendapatkan label nama hari resmi dengan huruf kapital untuk tampilan antarmuka.
     *
     * @param string $hariKey Kunci hari ('senin', 'selasa', dst.)
     * @return string Nama hari berawalan kapital ('Senin', 'Selasa', dst.)
     */
    public static function getHariLabel($hariKey): string
    {
        return match (strtolower($hariKey)) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
            default => ucfirst($hariKey),
        };
    }

    /**
     * Mengambil jadwal kerja yang berlaku untuk tanggal tertentu dengan urutan prioritas:
     * 1. Pengecekan daftar Hari Libur Nasional / Tanggal Merah (Holiday::getHoliday)
     * 2. Penggantian tanggal spesifik pada tabel schedules (tanggal override)
     * 3. Jadwal mingguan berdasarkan hari (Senin s/d Jumat)
     * 4. Nilai standar akhir pekan (Sabtu & Minggu = Hari Libur)
     *
     * @param string $dateString Tanggal dalam format Y-m-d
     * @return Schedule Objek model jadwal yang berlaku
     */
    public static function getScheduleForDate($dateString)
    {
        $dt = Carbon::parse($dateString);
        $dayName = self::getHariNameIndonesian($dt->dayOfWeekIso);

        // 0. Periksa apakah tanggal terdaftar sebagai Hari Libur Nasional / Tanggal Merah
        $holiday = Holiday::getHoliday($dateString);
        if ($holiday) {
            return new self([
                'tanggal' => $dateString,
                'hari' => $dayName,
                'jam_masuk' => '08:00:00',
                'jam_istirahat' => '12:00:00',
                'jam_masuk_istirahat' => '13:00:00',
                'jam_pulang' => '17:00:00',
                'is_libur' => true,
                'keterangan' => 'Libur: ' . $holiday->nama . ($holiday->keterangan ? ' (' . $holiday->keterangan . ')' : ''),
            ]);
        }

        // 1. Periksa apakah ada pengaturan khusus per tanggal di database
        $schedule = self::where('tanggal', $dateString)->first();
        if ($schedule) {
            return $schedule;
        }

        // 2. Periksa jadwal harian reguler (Senin - Jumat)
        $schedule = self::where('hari', $dayName)->first();
        if ($schedule) {
            return $schedule;
        }

        // 3. Cadangan standar: Sabtu & Minggu ditetapkan sebagai hari libur akhir pekan
        $isWeekend = ($dayName === 'sabtu' || $dayName === 'minggu');
        return new self([
            'tanggal' => $dateString,
            'hari' => $dayName,
            'jam_masuk' => '08:00:00',
            'jam_istirahat' => ($dayName === 'jumat') ? '11:30:00' : '12:00:00',
            'jam_masuk_istirahat' => '13:00:00',
            'jam_pulang' => ($dayName === 'jumat') ? '16:30:00' : '17:00:00',
            'is_libur' => $isWeekend,
            'keterangan' => $isWeekend ? 'Akhir Pekan (Hari Libur)' : 'Hari Kerja ' . self::getHariLabel($dayName),
        ]);
    }

    /**
     * Menghitung status jendela presensi untuk sesi tertentu pada waktu sekarang:
     * - Jendela presensi dibuka 15 menit sebelum jam target
     * - Jendela presensi ditutup 15 menit setelah jam target
     *
     * @param string $tipe Jenis sesi ('masuk', 'istirahat', 'masuk_istirahat', 'pulang')
     * @param Carbon|null $now Waktu acuan saat ini (default: waktu sekarang)
     * @return array Data status jendela presensi (jam target, jam buka, jam tutup, apakah sedang buka)
     */
    public function getWindowStatus(string $tipe, Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();
        $dateStr = $this->tanggal ? Carbon::parse($this->tanggal)->format('Y-m-d') : $now->format('Y-m-d');

        // Jika hari ini libur, seluruh sesi otomatis ditutup
        if ($this->is_libur) {
            return [
                'tipe' => $tipe,
                'target_time' => '--:--',
                'open_time' => '--:--',
                'close_time' => '--:--',
                'status' => 'ditutup',
                'is_open' => false,
                'is_before' => false,
                'is_after' => true,
                'is_libur' => true,
            ];
        }

        $timeMap = [
            'masuk' => $this->jam_masuk,
            'istirahat' => $this->jam_istirahat,
            'masuk_istirahat' => $this->jam_masuk_istirahat,
            'pulang' => $this->jam_pulang,
        ];

        $targetTimeString = $timeMap[$tipe] ?? '08:00:00';
        $targetDateTime = Carbon::parse($dateStr . ' ' . $targetTimeString);

        // Perhitungan batas buka dan tutup (rentang toleransi 15 menit)
        $openDateTime = (clone $targetDateTime)->subMinutes(15);
        $closeDateTime = (clone $targetDateTime)->addMinutes(15);

        $isBeforeWindow = $now->lt($openDateTime);
        $isAfterWindow = $now->gt($closeDateTime);
        $isOpen = $now->gte($openDateTime) && $now->lte($closeDateTime);

        $status = 'ditutup';
        if ($isBeforeWindow) {
            $status = 'belumbuka';
        } elseif ($isOpen) {
            $status = 'buka';
        }

        return [
            'tipe' => $tipe,
            'target_time' => $targetDateTime->format('H:i'),
            'open_time' => $openDateTime->format('H:i'),
            'close_time' => $closeDateTime->format('H:i'),
            'status' => $status,
            'is_open' => $isOpen,
            'is_before' => $isBeforeWindow,
            'is_after' => $isAfterWindow,
            'is_libur' => false,
            'target_datetime' => $targetDateTime,
            'open_datetime' => $openDateTime,
            'close_datetime' => $closeDateTime,
        ];
    }
}


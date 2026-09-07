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
     * Lokasi berkas konfigurasi aturan jam buka, jam tutup, dan batas toleransi presensi.
     */
    public static function getRulesFilePath(): string
    {
        return storage_path('app/attendance_rules.json');
    }

    /**
     * Konfigurasi standar untuk jam buka, toleransi tepat waktu, dan jam tutup per sesi.
     * Contoh: Jam masuk target 08:00, dibuka 06:30, toleransi s/d 08:59 (tepat waktu), ditutup 11:00.
     *
     * @param string|null $hari
     * @return array
     */
    public static function getDefaultRules(?string $hari = null): array
    {
        $isJumat = (strtolower($hari ?? '') === 'jumat');
        return [
            'masuk' => [
                'jam_buka' => '06:30',
                'jam_toleransi' => '08:59',
                'jam_tutup' => '11:00',
            ],
            'istirahat' => [
                'jam_buka' => $isJumat ? '11:00' : '11:30',
                'jam_toleransi' => $isJumat ? '11:30' : '12:00',
                'jam_tutup' => '13:00',
            ],
            'masuk_istirahat' => [
                'jam_buka' => '12:30',
                'jam_toleransi' => '13:15',
                'jam_tutup' => '14:30',
            ],
            'pulang' => [
                'jam_buka' => $isJumat ? '16:00' : '16:30',
                'jam_toleransi' => $isJumat ? '16:30' : '17:00',
                'jam_tutup' => '23:59',
            ],
        ];
    }

    /**
     * Mengambil seluruh aturan jam buka, tutup, dan toleransi dari berkas konfigurasi.
     *
     * @return array
     */
    public static function getAllRules(): array
    {
        $path = self::getRulesFilePath();
        if (file_exists($path)) {
            $content = @file_get_contents($path);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Mengambil aturan jam buka, tutup, dan toleransi untuk hari tertentu.
     *
     * @param string $hari
     * @return array
     */
    public static function getRulesForDay(string $hari): array
    {
        $hari = strtolower($hari);
        $allRules = self::getAllRules();
        $default = self::getDefaultRules($hari);

        if (isset($allRules[$hari]) && is_array($allRules[$hari])) {
            $merged = [];
            foreach (['masuk', 'istirahat', 'masuk_istirahat', 'pulang'] as $s) {
                $merged[$s] = array_merge($default[$s] ?? [], $allRules[$hari][$s] ?? []);
            }
            return $merged;
        }

        return $default;
    }

    /**
     * Menyimpan aturan jam buka, tutup, dan toleransi untuk hari tertentu ke berkas JSON.
     * Tidak mengubah atau memodifikasi tabel database apa pun.
     *
     * @param string $hari
     * @param array $rules
     * @param bool $applyToAllWeekdays
     * @return void
     */
    public static function saveRulesForDay(string $hari, array $rules, bool $applyToAllWeekdays = false): void
    {
        $hari = strtolower($hari);
        $allRules = self::getAllRules();
        $allRules[$hari] = $rules;

        if ($applyToAllWeekdays) {
            foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat'] as $wDay) {
                $allRules[$wDay] = $rules;
            }
        }

        $path = self::getRulesFilePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($path, json_encode($allRules, JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Menghitung status jendela presensi untuk sesi tertentu pada waktu sekarang:
     * - Mendukung jam buka dan jam tutup kustom yang dapat diatur oleh Operator.
     * - Mendukung batas toleransi keterlambatan (contoh: target 08:00, toleransi 08:59 tetap Tepat Waktu).
     *
     * @param string $tipe Jenis sesi ('masuk', 'istirahat', 'masuk_istirahat', 'pulang')
     * @param Carbon|null $now Waktu acuan saat ini (default: waktu sekarang)
     * @return array Data status jendela presensi (jam target, jam buka, jam tutup, jam toleransi, status buka)
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
                'tolerance_time' => '--:--',
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

        // Ambil aturan jam buka, jam tutup, dan toleransi untuk hari ini
        $dayName = $this->hari ?: self::getHariNameIndonesian(Carbon::parse($dateStr)->dayOfWeekIso);
        $rulesForDay = self::getRulesForDay($dayName);
        $sessionRule = $rulesForDay[$tipe] ?? [];

        // Penanganan fallback khusus jika model diinstansiasi tanpa hari dan tanpa file aturan
        $openStr = $sessionRule['jam_buka'] ?? null;
        $closeStr = $sessionRule['jam_tutup'] ?? null;
        $tolStr = $sessionRule['jam_toleransi'] ?? null;

        // Jika ada konfigurasi kustom, hitung batas waktu dengan presisi
        if ($openStr && $closeStr) {
            $openDateTime = Carbon::parse($dateStr . ' ' . $openStr . ':00');
            $closeDateTime = Carbon::parse($dateStr . ' ' . $closeStr . ':59');
            if ($tipe === 'masuk' || $tipe === 'masuk_istirahat') {
                // Untuk sesi masuk: batas toleransi berlaku hingga detik :59 (contoh 08:59:59)
                $toleranceDateTime = $tolStr ? Carbon::parse($dateStr . ' ' . $tolStr . ':59') : (clone $targetDateTime);
            } else {
                // Untuk sesi keluar (istirahat & pulang): batas tepat waktu adalah tepat di detik :00
                // sehingga jika absen pulang tepat di jam target/toleransi (17:00:00 ke atas) dihitung Tepat Waktu
                $toleranceDateTime = $tolStr ? Carbon::parse($dateStr . ' ' . $tolStr . ':00') : (clone $targetDateTime);
            }
        } else {
            $openDateTime = (clone $targetDateTime)->subMinutes(15);
            $closeDateTime = (clone $targetDateTime)->addMinutes(15);
            $toleranceDateTime = clone $targetDateTime;
        }

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
            'tolerance_time' => $toleranceDateTime->format('H:i'),
            'status' => $status,
            'is_open' => $isOpen,
            'is_before' => $isBeforeWindow,
            'is_after' => $isAfterWindow,
            'is_libur' => false,
            'target_datetime' => $targetDateTime,
            'open_datetime' => $openDateTime,
            'close_datetime' => $closeDateTime,
            'tolerance_datetime' => $toleranceDateTime,
        ];
    }
}


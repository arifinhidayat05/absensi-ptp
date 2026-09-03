<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model Data Kehadiran / Presensi Pegawai
 *
 * Mengelola pencatatan riwayat presensi harian (jam masuk, istirahat, masuk istirahat, dan pulang),
 * koordinat lokasi GPS, foto bukti kehadiran (liveness webcam), alamat, IP address,
 * status ketepatan waktu (tepat waktu, terlambat, lebih awal), serta status persetujuan operator.
 */
class Attendance extends Model
{
    use HasFactory;

    /**
     * Kolom-kolom tabel yang dapat diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'user_id',
        'tanggal',
        'tipe',
        'waktu',
        'foto',
        'latitude',
        'longitude',
        'alamat',
        'ip_address',
        'status',
        'approval_status',
        'catatan_operator',
    ];

    /**
     * Tipe data konversi atribut otomatis oleh Eloquent.
     */
    protected $casts = [
        'tanggal' => 'date',
        'waktu' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Relasi ke data pengguna / pegawai pemilik rekaman presensi ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Memeriksa apakah rekaman presensi ini telah disetujui (diterima) oleh operator.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'diterima';
    }

    /**
     * Memeriksa apakah rekaman presensi ini ditolak oleh operator (misal foto buram / tidak sesuai).
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'ditolak';
    }

    /**
     * Memeriksa apakah presensi merupakan hasil input manual oleh operator.
     */
    public function isManual(): bool
    {
        return str_contains(strtolower($this->alamat ?? ''), 'manual')
            || str_contains(strtolower($this->ip_address ?? ''), 'manual')
            || str_contains(strtolower($this->foto ?? ''), 'manual');
    }

    /**
     * Mendapatkan URL foto presensi atau foto fallback stempel presensi manual.
     */
    public function getFotoUrlAttribute(): string
    {
        if (!empty($this->foto) && file_exists(public_path($this->foto))) {
            return asset($this->foto);
        }
        return asset('images/manual_attendance.png');
    }

    /**
     * Mendapatkan label teks bahasa Indonesia untuk jenis sesi presensi.
     *
     * @param string $tipe Kunci sesi ('masuk', 'istirahat', 'masuk_istirahat', 'pulang')
     * @return string Label nama sesi yang ramah pengguna
     */
    public static function getTipeLabel(string $tipe): string
    {
        return match ($tipe) {
            'masuk' => 'Jam Masuk',
            'istirahat' => 'Jam Istirahat',
            'masuk_istirahat' => 'Jam Masuk Istirahat',
            'pulang' => 'Jam Pulang',
            default => ucfirst($tipe),
        };
    }

    /**
     * Menentukan status ketepatan waktu presensi berdasarkan aturan resmi instansi:
     * 1. Jam Masuk Pagi: Datang lebih awal / pas jam target dihitung TEPAT WAKTU. Lewat jam target dihitung TERLAMBAT.
     * 2. Jam Istirahat: Keluar istirahat terlambat dihitung TEPAT WAKTU. Keluar mendahului jam istirahat dihitung LEBIH AWAL.
     * 3. Masuk Istirahat: Kembali lebih awal / pas jam target dihitung TEPAT WAKTU. Lewat jam target dihitung TERLAMBAT.
     * 4. Jam Pulang: Pulang terlambat (lembur) dihitung TEPAT WAKTU. Pulang mendahului jam kantor dihitung LEBIH AWAL.
     *
     * @param string $tipe Jenis sesi presensi ('masuk', 'istirahat', 'masuk_istirahat', 'pulang')
     * @param Carbon $attendanceTime Waktu aktual saat pegawai melakukan presensi
     * @param Carbon $targetDateTime Jam target resmi yang ditetapkan pada jadwal kerja
     * @return string 'tepat_waktu', 'terlambat', atau 'lebih_awal'
     */
    public static function determineStatus(string $tipe, Carbon $attendanceTime, Carbon $targetDateTime): string
    {
        if ($tipe === 'masuk' || $tipe === 'masuk_istirahat') {
            // Datang lebih cepat (kecepatan masuk pagi atau kecepatan masuk istirahat) = Tepat Waktu
            // Datang setelah melewati jam target = Terlambat
            return $attendanceTime->gt($targetDateTime) ? 'terlambat' : 'tepat_waktu';
        }

        if ($tipe === 'istirahat' || $tipe === 'pulang') {
            // Keluar lebih lambat (telat absen istirahat atau telat absen pulang) = Tepat Waktu
            // Keluar mendahului jam target resmi = Lebih Awal
            return $attendanceTime->lt($targetDateTime) ? 'lebih_awal' : 'tepat_waktu';
        }

        return 'tepat_waktu';
    }

    /**
     * Mendapatkan label teks status presensi untuk antarmuka pengguna dan laporan.
     *
     * @param string $status Kode status ('tepat_waktu', 'terlambat', 'lebih_awal')
     * @return string Teks tampilan status
     */
    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'tepat_waktu' => 'Tepat Waktu',
            'terlambat' => 'Terlambat',
            'lebih_awal' => 'Lebih Awal',
            default => ucfirst($status),
        };
    }

    /**
     * Mendapatkan keterangan status persetujuan (approval) oleh operator.
     *
     * @param string $approval_status ('diterima', 'ditolak')
     * @return string Keterangan status persetujuan
     */
    public static function getApprovalLabel(string $approval_status): string
    {
        return match ($approval_status) {
            'diterima' => 'Diterima',
            'ditolak' => 'Ditolak (Dianggap ALFA / Belum Absen)',
            default => ucfirst($approval_status),
        };
    }
}


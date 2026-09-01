<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model Pengajuan & Pengelolaan Cuti Pegawai
 *
 * Mengelola permohonan cuti (cuti tahunan, sakit, luar negeri, alasan penting),
 * lampiran dokumen bukti medis / surat izin, serta persetujuan resmi dari pihak operator presensi.
 */
class Leave extends Model
{
    use HasFactory;

    /**
     * Kolom tabel yang dapat diisi secara massal.
     */
    protected $fillable = [
        'user_id',
        'jenis_cuti',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
        'alasan',
        'dokumen_pendukung',
        'status',
        'catatan_operator',
    ];

    /**
     * Tipe konversi atribut otomatis oleh Eloquent.
     */
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jumlah_hari' => 'integer',
    ];

    /**
     * Relasi ke data pegawai yang mengajukan cuti.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan nama teks bahasa Indonesia untuk jenis cuti.
     *
     * @param string $jenis Kunci jenis cuti
     * @return string Label jenis cuti
     */
    public static function getJenisCutiLabel(string $jenis): string
    {
        return match ($jenis) {
            'cuti_tahunan' => 'Cuti Tahunan',
            'cuti_sakit' => 'Cuti Sakit',
            'cuti_luar_negeri' => 'Cuti Luar Negeri',
            'cuti_alasan_penting' => 'Cuti Alasan Penting',
            default => 'Cuti Lainnya',
        };
    }

    /**
     * Mendapatkan atribut tampilan lencana (badge) jenis cuti untuk antarmuka tabel.
     *
     * @param string $jenis Kunci jenis cuti
     * @return array Konfigurasi lencana (label teks, warna latar/border, ikon FontAwesome)
     */
    public static function getJenisCutiBadge(string $jenis): array
    {
        return match ($jenis) {
            'cuti_tahunan' => [
                'label' => 'Cuti Tahunan',
                'bg' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                'icon' => 'fa-solid fa-calendar-check text-emerald-600',
            ],
            'cuti_sakit' => [
                'label' => 'Cuti Sakit',
                'bg' => 'bg-amber-50 text-amber-800 border-amber-300',
                'icon' => 'fa-solid fa-notes-medical text-amber-600',
            ],
            'cuti_luar_negeri' => [
                'label' => 'Cuti Luar Negeri',
                'bg' => 'bg-sky-50 text-sky-800 border-sky-300',
                'icon' => 'fa-solid fa-plane-departure text-sky-600',
            ],
            'cuti_alasan_penting' => [
                'label' => 'Cuti Alasan Penting',
                'bg' => 'bg-purple-50 text-purple-800 border-purple-300',
                'icon' => 'fa-solid fa-circle-exclamation text-purple-600',
            ],
            default => [
                'label' => 'Cuti Lainnya',
                'bg' => 'bg-slate-50 text-slate-800 border-slate-300',
                'icon' => 'fa-solid fa-calendar-day text-slate-600',
            ],
        };
    }

    /**
     * Mendapatkan label status permohonan cuti dalam bahasa Indonesia.
     *
     * @param string $status ('menunggu', 'disetujui', 'ditolak')
     * @return string Label tampilan status
     */
    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'disetujui' => 'Disetujui',
            'menunggu' => 'Menunggu Konfirmasi',
            'ditolak' => 'Ditolak',
            default => ucfirst($status),
        };
    }

    /**
     * Memeriksa apakah pegawai tertentu memiliki izin cuti yang telah disetujui pada tanggal tertentu.
     * Digunakan untuk otomatisasi laporan presensi dan pembebasan absensi pada tanggal tersebut.
     *
     * @param int $userId ID pegawai yang diperiksa
     * @param string $dateString Tanggal dalam format Y-m-d
     * @return Leave|null Objek cuti jika sedang dalam masa cuti disetujui, null jika tidak
     */
    public static function getUserLeaveOnDate(int $userId, string $dateString): ?self
    {
        return self::where('user_id', $userId)
            ->where('status', 'disetujui')
            ->where('tanggal_mulai', '<=', $dateString)
            ->where('tanggal_selesai', '>=', $dateString)
            ->first();
    }
}


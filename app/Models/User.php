<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model Data Pengguna Sistem Presensi
 *
 * Mengelola identitas pengguna (Operator Presensi, Pegawai Tetap/Honorer, Mahasiswa Magang, dan Siswa Magang SMA/SMK).
 * Mendukung jenis nomor identitas dinamis (NIP, NIM, atau NISN) serta asal instansi/sekolah.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kolom tabel yang dapat diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'nip',
        'tipe_identitas',
        'jenis_pegawai',
        'name',
        'email',
        'no_hp',
        'role',
        'jabatan',
        'asal_instansi',
        'password',
        'foto',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi data (keamanan kredensial).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atribut virtual yang otomatis disertakan saat serialisasi ke array/JSON.
     */
    protected $appends = [
        'foto_url',
        'inisial',
        'identitas_lengkap',
        'tipe_identitas_label',
    ];

    /**
     * Konversi tipe data otomatis oleh Eloquent.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi satu-ke-banyak ke riwayat presensi yang dimiliki pengguna.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relasi satu-ke-banyak ke riwayat pengajuan cuti yang diajukan oleh pengguna.
     */
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Memeriksa apakah pengguna memiliki hak akses sebagai Operator Presensi.
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Memeriksa apakah pengguna memiliki hak akses sebagai Karyawan / Pegawai / Peserta Magang.
     */
    public function isKaryawan(): bool
    {
        return $this->role === 'karyawan';
    }

    /**
     * Mendapatkan singkatan label nomor identitas (NIP, NIM, atau NISN).
     *
     * @return string 'NIP', 'NIM', atau 'NISN'
     */
    public function getTipeIdentitasLabelAttribute(): string
    {
        return match ($this->tipe_identitas) {
            'nim' => 'NIM',
            'nisn' => 'NISN',
            default => 'NIP',
        };
    }

    /**
     * Mendapatkan label jenis pegawai yang ramah pengguna dalam bahasa Indonesia.
     *
     * @return string Label jenis pegawai
     */
    public function getJenisPegawaiLabelAttribute(): string
    {
        return match ($this->jenis_pegawai) {
            'mahasiswa_magang' => 'Mahasiswa Magang (Kuliah)',
            'siswa_magang' => 'Siswa Magang (SMA/SMK)',
            default => 'Pegawai',
        };
    }

    /**
     * Mendapatkan format teks identitas lengkap beserta nomornya, misal: "NIP. 19850101..." atau "NIM. 1221001".
     *
     * @return string Teks identitas terformat
     */
    public function getIdentitasLengkapAttribute(): string
    {
        $label = $this->tipe_identitas_label;
        return "{$label}. {$this->nip}";
    }

    /**
     * Memeriksa apakah pengguna berstatus peserta magang (Mahasiswa Kuliah atau Siswa SMA/SMK).
     *
     * @return bool True jika peserta magang, False jika pegawai reguler
     */
    public function isMagang(): bool
    {
        return in_array($this->jenis_pegawai, ['mahasiswa_magang', 'siswa_magang']);
    }

    /**
     * Memeriksa apakah pengguna memiliki foto profil yang valid di penyimpanan.
     *
     * @return bool
     */
    public function hasFoto(): bool
    {
        return !empty($this->foto) && file_exists(public_path($this->foto));
    }

    /**
     * Mendapatkan URL foto profil pengguna atau null jika belum diatur.
     *
     * @return string|null
     */
    public function getFotoUrlAttribute(): ?string
    {
        if ($this->hasFoto()) {
            return asset($this->foto);
        }
        return null;
    }

    /**
     * Mendapatkan 2 huruf inisial dari nama pengguna untuk avatar placeholder.
     *
     * @return string
     */
    public function getInisialAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }
}


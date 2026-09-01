<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * Controller Autentikasi Pengguna
 *
 * Mengelola proses masuk (login) bagi Operator Presensi dan Pegawai/Peserta Magang,
 * pengalihan hak akses secara otomatis ke portal masing-masing, serta pengakhiran sesi (logout).
 * Dilengkapi proteksi anti brute-force attack dengan pembatasan percobaan gagal.
 */
class AuthController extends Controller
{
    /**
     * Menampilkan halaman login sistem.
     * Jika pengguna telah memiliki sesi aktif, otomatis dialihkan ke dashboard sesuai perannya.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLogin()
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'operator') {
                return redirect()->route('operator.dashboard');
            }
            return redirect()->route('karyawan.dashboard');
        }
        return view('auth.login');
    }

    /**
     * Memproses verifikasi kredensial login pengguna.
     * Menggunakan Nomor Identitas (NIP / NIM / NISN) dan Kata Sandi.
     * Dilengkapi perlindungan terhadap serangan Brute Force (Rate Limiting).
     *
     * @param Request $request Data formulir masuk dari pengguna
     * @return \Illuminate\Http\RedirectResponse Pengalihan ke dashboard atau kembali dengan pesan error
     */
    public function login(Request $request)
    {
        // 1. Kunci throttle per kombinasi NIP & IP klien
        $throttleKey = Str::transliterate(Str::lower((string) $request->input('nip', '')) . '|' . $request->ip());

        // 2. Cek apakah IP / NIP ini telah melampaui batas maksimal percobaan (5 kali gagal)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'nip' => 'Terlalu banyak percobaan masuk yang salah (Proteksi Brute Force Aktif). Akun dibekukan sementara. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ])->onlyInput('nip');
        }

        // 3. Validasi format masukan kredensial
        $credentials = $request->validate([
            'nip' => 'required|string|max:50',
            'password' => 'required|string',
        ], [
            'nip.required' => 'Nomor Identitas wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // 4. Percobaan autentikasi dengan pencocokan kredensial
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Berhasil login: bersihkan catatan percobaan gagal
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            // 5. Pengalihan berbasis peran pengguna
            if ($user->role === 'operator') {
                return redirect()->intended(route('operator.dashboard'))
                    ->with('success', 'Selamat datang kembali, ' . $user->name);
            }

            return redirect()->intended(route('karyawan.dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name);
        }

        // 6. Jika kredensial salah, catat sebagai kegagalan (terkunci 60 detik jika capai 5x)
        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'nip' => 'Nomor Identitas atau password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('nip');
    }

    /**
     * Mengakhiri sesi pengguna (logout) dan menghapus token sesi demi keamanan.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem presensi.');
    }
}

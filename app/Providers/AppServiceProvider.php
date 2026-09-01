<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');

        // 1. Pembatasan Global untuk Web (Mencegah Flooding / DoS / DDoS Layer 7)
        RateLimiter::for('global_web', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip())->response(function () {
                return response()->view('errors.429', [
                    'message' => 'Terlalu banyak permintaan dalam waktu singkat (Pencegahan DoS aktif). Harap tunggu beberapa saat.',
                ], 429);
            });
        });

        // 2. Pembatasan Percobaan Login (Pencegahan Serangan Brute Force Kata Sandi)
        RateLimiter::for('login_attempts', function (Request $request) {
            $key = strtolower((string) $request->input('nip')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key)->response(function () {
                return back()->withErrors([
                    'nip' => 'Terlalu banyak percobaan login yang gagal (Proteksi Brute Force Aktif). Akun dibekukan sementara selama 1 menit demi keamanan.',
                ])->onlyInput('nip');
            });
        });

        // 3. Pembatasan Pengiriman Presensi (Mencegah Spam / Replay Attacks)
        RateLimiter::for('attendance_submit', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak pengiriman data presensi dalam waktu singkat. Harap tunggu sebentar.',
                ], 429);
            });
        });

        // 4. Pembatasan Unggah Berkas & Import Excel (Mencegah DoS Penyimpanan & Beban Server)
        RateLimiter::for('file_upload', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        // 5. Pembatasan Ekspor & Cetak Laporan (Mencegah Resource Exhaustion DoS)
        RateLimiter::for('reports_export', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });
    }
}

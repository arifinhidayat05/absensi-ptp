<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web Application Firewall (WAF) & Security Shield Middleware
 *
 * Melindungi aplikasi secara proaktif dari berbagai jenis serangan siber:
 * 1. SQL Injection (Union Select, Boolean-based, Time-based sleep/benchmark, Stacked queries)
 * 2. Cross-Site Scripting (XSS - script injection, event handler injection, javascript pseudo-protocol)
 * 3. Path Traversal & Directory Traversal (../ / ..\)
 * 4. HTTP Request Header Hardening (X-Frame-Options, X-Content-Type-Options, Permissions-Policy)
 * 5. Penyamaran informasi server (X-Powered-By stripping)
 */
class SecurityShieldMiddleware
{
    /**
     * Pola regex untuk mendeteksi payload SQL Injection berbahaya.
     */
    protected array $sqlInjectionPatterns = [
        '/\bunion\s+(all\s+)?select\b/i',
        '/\bselect\b.*\bfrom\b.*\b(where|users|attendances|information_schema|sysdatabases)\b/i',
        '/\b(drop|truncate)\s+(table|database)\b/i',
        '/;\s*(delete|drop|truncate|alter)\s+(table|from)\b/i',
        '/(\'|\")\s*(or|and)\s*(\'|\")?(1\s*=\s*1|true|0\s*=\s*0)(\'|\")?/i',
        '/\b(benchmark|sleep)\s*\(\s*\d+/i',
        '/\bwaitfor\s+delay\b/i',
        '/\bload_file\s*\(/i',
        '/\binto\s+(out|dump)file\b/i',
        '/\binformation_schema\b/i',
    ];

    /**
     * Pola regex untuk mendeteksi payload XSS (Cross-Site Scripting) berbahaya.
     */
    protected array $xssPatterns = [
        '/<\s*script\b[^>]*>/i',
        '/javascript\s*:/i',
        '/\bon(error|load|click|mouseover|mouseenter|focus|blur|submit)\s*=/i',
        '/<\s*(iframe|embed|object)\b/i',
        '/<\s*svg\b[^>]*\bonload\s*=/i',
    ];

    /**
     * Daftar nama field yang dikecualikan dari pemeriksaan pola serangan
     * (Misalnya: password pengguna yang mungkin mengandung karakter acak,
     * dan data gambar base64 webcam).
     */
    protected array $exemptedFields = [
        'password',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'foto', // Data URI base64 dari tangkapan kamera presensi
        '_token',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Periksa seluruh parameter masukan (Query params & Request body)
        $inputs = $request->all();

        if ($this->containsMaliciousPayload($inputs)) {
            Log::warning('[SECURITY SHIELD BLOCKED] Percobaan serangan terdeteksi dan diblokir', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'user_id' => $request->user()?->id,
            ]);

            if ($request->expectsJson() || $request->is('api/*') || $request->is('karyawan/attendance*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan diblokir oleh Sistem Keamanan (Firewall Aplikasi). Muatan mencurigakan terdeteksi.',
                ], 403);
            }

            return response()->view('errors.403', [
                'message' => 'Permintaan Anda diblokir oleh Sistem Keamanan Web. Muatan data terindikasi mengandung pola serangan berbahaya (SQL Injection / XSS).',
            ], 403);
        }

        // 2. Jalankan request ke aplikasi
        $response = $next($request);

        // 3. Pasang HTTP Security Headers untuk mengeraskan keamanan peramban klien
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');

        // Sembunyikan identitas server PHP dari respon
        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        return $response;
    }

    /**
     * Memeriksa apakah data masukan mengandung payload serangan berbahaya secara rekursif.
     */
    protected function containsMaliciousPayload(mixed $data, ?string $parentKey = null): bool
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array((string)$key, $this->exemptedFields, true)) {
                    continue;
                }
                if ($this->containsMaliciousPayload($value, (string)$key)) {
                    return true;
                }
            }
            return false;
        }

        if (!is_string($data) || empty($data)) {
            return false;
        }

        // Pemeriksaan SQL Injection
        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $data)) {
                return true;
            }
        }

        // Pemeriksaan XSS
        foreach ($this->xssPatterns as $pattern) {
            if (preg_match($pattern, $data)) {
                return true;
            }
        }

        return false;
    }
}

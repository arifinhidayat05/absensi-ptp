<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class WebSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('198501012010011001|127.0.0.1');
    }

    public function test_security_headers_are_present_on_responses()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');
    }

    public function test_sql_injection_attempt_is_blocked_with_403()
    {
        $operator = User::create([
            'nip' => '198001012005011001',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Operator Test',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        // Kirim query search yang mengandung pola SQL Injection UNION SELECT
        $response = $this->actingAs($operator)->get('/operator/employees?search=1%27+UNION+SELECT+1%2C2%2C3--');

        $response->assertStatus(403);
    }

    public function test_xss_script_injection_attempt_is_blocked_with_403()
    {
        $operator = User::create([
            'nip' => '198001012005011002',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Operator Test 2',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        // Kirim payload XSS <script>alert(1)</script>
        $response = $this->actingAs($operator)->post('/operator/employees', [
            'nip' => '199901012024011001',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => '<script>alert("XSS")</script>',
        ]);

        $response->assertStatus(403);
    }

    public function test_brute_force_login_protection_triggers_after_multiple_failures()
    {
        User::create([
            'nip' => '198501012010011001',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'User Brute Force Test',
            'role' => 'karyawan',
            'password' => Hash::make('correct_password'),
        ]);

        // Coba 5 kali salah password
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'nip' => '198501012010011001',
                'password' => 'wrong_password_' . $i,
            ]);
        }

        // Percobaan ke-6 harus diblokir oleh anti brute-force
        $response = $this->post('/login', [
            'nip' => '198501012010011001',
            'password' => 'wrong_password_6',
        ]);

        $response->assertSessionHasErrors('nip');
        $errors = session('errors')->get('nip');
        $this->assertTrue(str_contains($errors[0], 'Brute Force') || str_contains($errors[0], 'Terlalu banyak'));
    }

    public function test_uploads_htaccess_exists_and_blocks_script_execution()
    {
        $htaccessPath = public_path('uploads/.htaccess');
        $this->assertTrue(File::exists($htaccessPath));

        $content = File::get($htaccessPath);
        $this->assertTrue(str_contains($content, 'Deny from all'));
        $this->assertTrue(str_contains($content, 'php_flag engine off'));
        $this->assertTrue(str_contains($content, 'Options -ExecCGI -Indexes'));
    }

    public function test_fake_image_payload_is_rejected_on_profile_upload()
    {
        $user = User::create([
            'nip' => '198501012010011003',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'User Fake Image Test',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        // Buat file teks palsu dengan ekstensi .jpg (polyglot / fake image)
        $fakeFile = UploadedFile::fake()->createWithContent('shell.jpg', '<?php phpinfo(); ?>');

        $response = $this->actingAs($user)->post('/karyawan/profile/foto', [
            'foto' => $fakeFile,
        ]);

        $response->assertSessionHas('error');
        $user->refresh();
        $this->assertNull($user->foto);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_karyawan_can_login_with_nip_and_password()
    {
        $user = User::create([
            'nip' => '1001',
            'name' => 'Ahmad Karyawan',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'nip' => '1001',
            'password' => 'password',
        ]);

        $response->assertRedirect('/karyawan/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_operator_can_login_and_access_dashboard()
    {
        $operator = User::create([
            'nip' => '12345678',
            'name' => 'Operator System',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'nip' => '12345678',
            'password' => 'password',
        ]);

        $response->assertRedirect('/operator/dashboard');
        $this->assertAuthenticatedAs($operator);
    }

    public function test_operator_can_create_new_employee_with_default_password()
    {
        $operator = User::create([
            'nip' => '12345678',
            'name' => 'Operator',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($operator)->post('/operator/employees', [
            'nip' => '2001',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Budi Baru',
            'jabatan' => 'Staff IT',
            'email' => 'budi@test.com',
        ]);

        $response->assertRedirect('/operator/employees');
        $this->assertDatabaseHas('users', [
            'nip' => '2001',
            'name' => 'Budi Baru',
            'role' => 'karyawan',
        ]);

        $newEmp = User::where('nip', '2001')->first();
        $this->assertTrue(Hash::check('password', $newEmp->password));
    }

    public function test_schedule_window_logic_15_minutes_before_and_after()
    {
        $today = Carbon::today()->format('Y-m-d');
        $schedule = Schedule::create([
            'tanggal' => $today,
            'jam_masuk' => '08:00:00',
            'jam_istirahat' => '12:00:00',
            'jam_masuk_istirahat' => '13:00:00',
            'jam_pulang' => '17:00:00',
        ]);

        // At 07:30 (before window 07:45) -> belumbuka
        $statusBefore = $schedule->getWindowStatus('masuk', Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 07:30:00'));
        $this->assertTrue($statusBefore['is_before']);
        $this->assertFalse($statusBefore['is_open']);

        // At 07:50 (inside window 07:45 - 08:15) -> buka
        $statusOpen = $schedule->getWindowStatus('masuk', Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 07:50:00'));
        $this->assertTrue($statusOpen['is_open']);

        // At 08:20 (after window 08:15) -> ditutup
        $statusAfter = $schedule->getWindowStatus('masuk', Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 08:20:00'));
        $this->assertTrue($statusAfter['is_after']);
        $this->assertFalse($statusAfter['is_open']);
    }

    public function test_karyawan_can_record_attendance_within_open_window()
    {
        $karyawan = User::create([
            'nip' => '1001',
            'name' => 'Test Karyawan',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        $today = Carbon::today()->format('Y-m-d');
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 08:00:00'));

        Setting::create([
            'nama_satker' => 'PT Pontianak',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 500,
        ]);

        Schedule::create([
            'tanggal' => $today,
            'jam_masuk' => '08:00:00',
            'jam_istirahat' => '12:00:00',
            'jam_masuk_istirahat' => '13:00:00',
            'jam_pulang' => '17:00:00',
        ]);

        // Standard sample base64 image (1x1 transparent GIF)
        $fakeBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($karyawan)->postJson('/karyawan/attendance', [
            'tipe' => 'masuk',
            'foto' => $fakeBase64,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'alamat' => 'Jakarta Center',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $karyawan->id,
            'tipe' => 'masuk',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);
    }

    public function test_morning_attendance_followed_by_approved_leave_marks_remaining_sessions_as_on_leave()
    {
        $karyawan = User::create([
            'nip' => '1002',
            'name' => 'Siti Nurhaliza',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        $today = Carbon::today()->format('Y-m-d');
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 08:00:00'));

        Schedule::create([
            'tanggal' => $today,
            'jam_masuk' => '08:00:00',
            'jam_istirahat' => '12:00:00',
            'jam_masuk_istirahat' => '13:00:00',
            'jam_pulang' => '17:00:00',
        ]);

        // 1. Absen masuk pagi saja
        Attendance::create([
            'user_id' => $karyawan->id,
            'tanggal' => $today,
            'tipe' => 'masuk',
            'waktu' => $today . ' 07:55:00',
            'foto' => 'uploads/test.jpg',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'alamat' => 'Kantor PT Pontianak',
            'status' => 'tepat_waktu',
            'approval_status' => 'diterima',
        ]);

        // 2. Ajukan cuti/izin dan langsung disetujui (misal cuti alasan penting / sakit)
        \App\Models\Leave::create([
            'user_id' => $karyawan->id,
            'jenis_cuti' => 'cuti_alasan_penting',
            'tanggal_mulai' => $today,
            'tanggal_selesai' => $today,
            'jumlah_hari' => 1,
            'alasan' => 'Izin keperluan keluarga mendesak setelah jam 09.00 WIB',
            'status' => 'disetujui',
        ]);

        // 3. Akses dashboard karyawan
        $response = $this->actingAs($karyawan)->get('/karyawan/dashboard');
        $response->assertStatus(200);

        $cards = $response->viewData('cards');
        $this->assertTrue($cards['masuk']['has_attended']);
        $this->assertFalse($cards['masuk']['is_on_leave']);

        // Sesi sisanya otomatis is_on_leave = true
        $this->assertTrue($cards['istirahat']['is_on_leave']);
        $this->assertFalse($cards['istirahat']['has_attended']);

        $this->assertTrue($cards['masuk_istirahat']['is_on_leave']);
        $this->assertFalse($cards['masuk_istirahat']['has_attended']);

        $this->assertTrue($cards['pulang']['is_on_leave']);
        $this->assertFalse($cards['pulang']['has_attended']);

        // 4. Pastikan di lembar laporan cetak (print), ketiga sesi sisa menampilkan Cuti / Izin
        $operator = User::create([
            'nip' => '9999',
            'name' => 'Operator',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $printResponse = $this->actingAs($operator)->get('/operator/reports/print?user_id=' . $karyawan->id . '&tanggal_mulai=' . $today . '&tanggal_selesai=' . $today);
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Cuti / Izin');
        $printResponse->assertSee('Cuti Alasan Penting');
    }
}


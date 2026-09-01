<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Bersihkan berkas uji jika tersisa di uploads/profiles
        $testFiles = glob(public_path('uploads/profiles/*test*'));
        if ($testFiles) {
            foreach ($testFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        parent::tearDown();
    }

    public function test_karyawan_can_upload_profile_photo()
    {
        $user = User::create([
            'nip' => '198501012010011001',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Budi Pegawai Test',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        $fakePhoto = UploadedFile::fake()->image('test_avatar.jpg', 200, 200);

        $response = $this->actingAs($user)->post('/karyawan/profile/foto', [
            'foto' => $fakePhoto,
        ]);

        $response->assertSessionHas('success');
        $user->refresh();

        $this->assertNotNull($user->foto);
        $this->assertTrue(File::exists(public_path($user->foto)));
        $this->assertTrue($user->hasFoto());
        $this->assertNotNull($user->foto_url);

        // Hapus file setelah test
        if (File::exists(public_path($user->foto))) {
            File::delete(public_path($user->foto));
        }
    }

    public function test_karyawan_can_delete_profile_photo()
    {
        $folder = 'uploads/profiles';
        $fullFolder = public_path($folder);
        if (!File::exists($fullFolder)) {
            File::makeDirectory($fullFolder, 0755, true, true);
        }

        $dummyPath = $folder . '/test_dummy_photo.jpg';
        file_put_contents(public_path($dummyPath), 'fake image data');

        $user = User::create([
            'nip' => '198501012010011002',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Siti Karyawan Test',
            'role' => 'karyawan',
            'foto' => $dummyPath,
            'password' => Hash::make('password'),
        ]);

        $this->assertTrue(File::exists(public_path($dummyPath)));

        $response = $this->actingAs($user)->delete('/karyawan/profile/foto');

        $response->assertSessionHas('success');
        $user->refresh();

        $this->assertNull($user->foto);
        $this->assertFalse(File::exists(public_path($dummyPath)));
        $this->assertFalse($user->hasFoto());
        $this->assertNull($user->foto_url);
    }

    public function test_operator_can_view_employee_with_profile_photo()
    {
        $operator = User::create([
            'nip' => '198001012005011001',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Admin Operator Test',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $folder = 'uploads/profiles';
        $dummyPath = $folder . '/test_employee_photo.jpg';
        if (!File::exists(public_path($folder))) {
            File::makeDirectory(public_path($folder), 0755, true, true);
        }
        file_put_contents(public_path($dummyPath), 'fake image data');

        $employee = User::create([
            'nip' => '199001012015011003',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Doni Pegawai Lihat Test',
            'role' => 'karyawan',
            'foto' => $dummyPath,
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($operator)->get('/operator/employees');

        $response->assertStatus(200);
        $response->assertSee('Doni Pegawai Lihat Test');
        $response->assertSee(asset($dummyPath));
        $response->assertSee('Lihat Foto');

        // Bersihkan
        if (File::exists(public_path($dummyPath))) {
            File::delete(public_path($dummyPath));
        }
    }

    public function test_operator_can_create_employee_with_profile_photo()
    {
        $operator = User::create([
            'nip' => '198001012005011002',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Admin Operator Test 2',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $fakePhoto = UploadedFile::fake()->image('test_new_emp.png', 150, 150);

        $response = $this->actingAs($operator)->post('/operator/employees', [
            'nip' => '199202022020011004',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Rina Baru Test',
            'jabatan' => 'Panitera Pengganti',
            'foto' => $fakePhoto,
        ]);

        $response->assertRedirect('/operator/employees');

        $emp = User::where('nip', '199202022020011004')->first();
        $this->assertNotNull($emp);
        $this->assertNotNull($emp->foto);
        $this->assertTrue(File::exists(public_path($emp->foto)));

        // Bersihkan
        if (File::exists(public_path($emp->foto))) {
            File::delete(public_path($emp->foto));
        }
    }

    public function test_operator_can_delete_employee_profile_photo()
    {
        $operator = User::create([
            'nip' => '198001012005011003',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Admin Operator Test 3',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $folder = 'uploads/profiles';
        $dummyPath = $folder . '/test_emp_to_delete.jpg';
        if (!File::exists(public_path($folder))) {
            File::makeDirectory(public_path($folder), 0755, true, true);
        }
        file_put_contents(public_path($dummyPath), 'dummy bytes');

        $employee = User::create([
            'nip' => '199303032021011005',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Eko Hapus Foto Test',
            'role' => 'karyawan',
            'foto' => $dummyPath,
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($operator)->put('/operator/employees/' . $employee->id, [
            'nip' => '199303032021011005',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Eko Hapus Foto Test',
            'hapus_foto' => '1',
        ]);

        $response->assertRedirect('/operator/employees');
        $employee->refresh();

        $this->assertNull($employee->foto);
        $this->assertFalse(File::exists(public_path($dummyPath)));
    }

    public function test_photo_upload_validation_rejects_invalid_extension()
    {
        $user = User::create([
            'nip' => '198501012010011005',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Validation Test User',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        $fakePdf = UploadedFile::fake()->create('document.pdf', 500);

        $response = $this->actingAs($user)->post('/karyawan/profile/foto', [
            'foto' => $fakePdf,
        ]);

        $response->assertSessionHasErrors('foto');
        $user->refresh();
        $this->assertNull($user->foto);
    }

    public function test_operator_can_upload_and_delete_own_profile_photo()
    {
        $operator = User::create([
            'nip' => '198001012005011999',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Operator Mandiri Test',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $fakePhoto = UploadedFile::fake()->image('operator_avatar.jpg', 200, 200);

        $response = $this->actingAs($operator)->post('/operator/profile/foto', [
            'foto' => $fakePhoto,
        ]);

        $response->assertSessionHas('success');
        $operator->refresh();

        $this->assertNotNull($operator->foto);
        $this->assertTrue(File::exists(public_path($operator->foto)));
        $this->assertTrue($operator->hasFoto());

        // Test delete
        $delResponse = $this->actingAs($operator)->delete('/operator/profile/foto');
        $delResponse->assertSessionHas('success');
        $operator->refresh();

        $this->assertNull($operator->foto);
        $this->assertFalse($operator->hasFoto());
    }

    public function test_operator_can_download_employee_profile_photo()
    {
        $operator = User::create([
            'nip' => '198001012005011888',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Operator Download Test',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $folder = 'uploads/profiles';
        $dummyFile = $folder . '/test_download_emp.jpg';
        if (!File::exists(public_path($folder))) {
            File::makeDirectory(public_path($folder), 0755, true, true);
        }
        file_put_contents(public_path($dummyFile), 'sample image content');

        $employee = User::create([
            'nip' => '199505052022011001',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Bambang Sudirman',
            'role' => 'karyawan',
            'foto' => $dummyFile,
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($operator)->get('/operator/employees/' . $employee->id . '/download-photo');

        $response->assertStatus(200);
        $response->assertDownload('Foto_Profil_bambang_sudirman_199505052022011001.jpg');

        if (File::exists(public_path($dummyFile))) {
            File::delete(public_path($dummyFile));
        }
    }

    public function test_operator_download_fails_gracefully_when_employee_has_no_photo()
    {
        $operator = User::create([
            'nip' => '198001012005011777',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Operator No Photo Test',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);

        $employee = User::create([
            'nip' => '199505052022011002',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Pegawai Tanpa Foto',
            'role' => 'karyawan',
            'foto' => null,
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($operator)->get('/operator/employees/' . $employee->id . '/download-photo');

        $response->assertSessionHas('error');
    }
}

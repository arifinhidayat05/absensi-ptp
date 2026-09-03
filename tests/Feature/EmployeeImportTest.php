<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    private function createOperator(): User
    {
        return User::create([
            'nip' => '198001012005011099',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Admin Operator Test',
            'role' => 'operator',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_operator_can_add_employee_with_no_hp()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->post('/operator/employees', [
            'nip' => '199501012022011005',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Dimas Prayoga',
            'jabatan' => 'Staff Kepegawaian',
            'no_hp' => '081234567890',
            'email' => 'dimas@pt-pontianak.go.id',
        ]);

        $response->assertRedirect('/operator/employees');
        $this->assertDatabaseHas('users', [
            'nip' => '199501012022011005',
            'name' => 'Dimas Prayoga',
            'no_hp' => '081234567890',
        ]);
    }

    public function test_operator_can_update_employee_no_hp()
    {
        $operator = $this->createOperator();

        $emp = User::create([
            'nip' => '199602022023011006',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Fajar Pratama',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($operator)->put('/operator/employees/' . $emp->id, [
            'nip' => '199602022023011006',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'name' => 'Fajar Pratama Updated',
            'no_hp' => '089876543210',
        ]);

        $response->assertRedirect('/operator/employees');
        $emp->refresh();
        $this->assertEquals('089876543210', $emp->no_hp);
    }

    public function test_operator_can_download_excel_template()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get('/operator/employees/template-excel');

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains(
                $response->headers->get('content-disposition') ?? '',
                'Template_Import_Pegawai_PT_Pontianak.xlsx'
            )
        );
    }

    public function test_operator_can_import_employees_from_excel()
    {
        $operator = $this->createOperator();

        // Siapkan spreadsheet uji
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'NIP');
        $sheet->setCellValue('B1', 'NAMA');
        $sheet->setCellValue('C1', 'KATEGORI');
        $sheet->setCellValue('D1', 'JABATAN');
        $sheet->setCellValue('E1', 'ASAL KAMPUS');
        $sheet->setCellValue('F1', 'NO HP');
        $sheet->setCellValue('G1', 'EMAIL');

        // Baris 1: Pegawai baru
        $sheet->setCellValue('A2', '198706052011011008');
        $sheet->setCellValue('B2', 'Hendra Kusuma');
        $sheet->setCellValue('C2', 'pegawai');
        $sheet->setCellValue('D2', 'Panitera Pengganti');
        $sheet->setCellValue('E2', '');
        $sheet->setCellValue('F2', '081122334455');
        $sheet->setCellValue('G2', 'hendra@test.com');

        // Baris 2: Mahasiswa Magang baru
        $sheet->setCellValue('A3', 'F109998881');
        $sheet->setCellValue('B3', 'Dewi Lestari');
        $sheet->setCellValue('C3', 'mahasiswa_magang');
        $sheet->setCellValue('D3', 'IT Support Magang');
        $sheet->setCellValue('E3', 'Universitas Tanjungpura');
        $sheet->setCellValue('F3', '082233445566');
        $sheet->setCellValue('G3', 'dewi@untan.ac.id');

        // Simpan ke berkas sementara
        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'import_pegawai.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($operator)->post('/operator/employees/import-excel', [
            'excel_file' => $uploadedFile,
        ]);

        $response->assertRedirect('/operator/employees');
        $response->assertSessionHas('success');

        // Pastikan kedua data masuk ke basis data
        $this->assertDatabaseHas('users', [
            'nip' => '198706052011011008',
            'name' => 'Hendra Kusuma',
            'role' => 'karyawan',
            'tipe_identitas' => 'nip',
            'jenis_pegawai' => 'pegawai',
            'no_hp' => '081122334455',
        ]);

        $this->assertDatabaseHas('users', [
            'nip' => 'F109998881',
            'name' => 'Dewi Lestari',
            'role' => 'karyawan',
            'tipe_identitas' => 'nim',
            'jenis_pegawai' => 'mahasiswa_magang',
            'asal_instansi' => 'Universitas Tanjungpura',
            'no_hp' => '082233445566',
        ]);

        // Cek password default
        $newEmp = User::where('nip', '198706052011011008')->first();
        $this->assertTrue(Hash::check('password', $newEmp->password));

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_import_capitalizes_student_and_school_names_and_skips_identical_data()
    {
        $operator = $this->createOperator();

        // 1. Buat spreadsheet dengan nama siswa/mahasiswa dan sekolah dalam huruf kecil
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'NIP/NIM/NISN');
        $sheet->setCellValue('B1', 'NAMA');
        $sheet->setCellValue('C1', 'KATEGORI');
        $sheet->setCellValue('D1', 'JABATAN');
        $sheet->setCellValue('E1', 'SEKOLAH/KAMPUS');
        $sheet->setCellValue('F1', 'NO HP');
        $sheet->setCellValue('G1', 'EMAIL');

        // Siswa magang dengan nama dan sekolah lowercase
        $sheet->setCellValue('A2', '0059988776');
        $sheet->setCellValue('B2', 'budi santoso');
        $sheet->setCellValue('C2', 'siswa_magang');
        $sheet->setCellValue('D2', 'Siswa Magang');
        $sheet->setCellValue('E2', 'smkn 1 pontianak');
        $sheet->setCellValue('F2', '085211223344');
        $sheet->setCellValue('G2', 'budi@test.com');

        // Mahasiswa magang dengan nama dan kampus lowercase
        $sheet->setCellValue('A3', 'F1081211099');
        $sheet->setCellValue('B3', 'siti rahmawati putri');
        $sheet->setCellValue('C3', 'mahasiswa_magang');
        $sheet->setCellValue('D3', 'Mahasiswa Magang');
        $sheet->setCellValue('E3', 'universitas tanjungpura');
        $sheet->setCellValue('F3', '089612345678');
        $sheet->setCellValue('G3', 'siti@test.com');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_cap_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'import_magang.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // Import pertama: Data baru dibuat dan nama dikapitalisasi
        $response1 = $this->actingAs($operator)->post('/operator/employees/import-excel', [
            'excel_file' => $uploadedFile,
        ]);

        $response1->assertRedirect('/operator/employees');
        $response1->assertSessionHas('success');

        // Verifikasi nama dan sekolah/kampus otomatis Capitalize Each Word
        $this->assertDatabaseHas('users', [
            'nip' => '0059988776',
            'name' => 'Budi Santoso', // Capitalized!
            'asal_instansi' => 'SMKN 1 Pontianak', // Capitalized with SMKN acronym preserved!
            'tipe_identitas' => 'nisn',
            'jenis_pegawai' => 'siswa_magang',
        ]);

        $this->assertDatabaseHas('users', [
            'nip' => 'F1081211099',
            'name' => 'Siti Rahmawati Putri', // Capitalized!
            'asal_instansi' => 'Universitas Tanjungpura', // Capitalized!
            'tipe_identitas' => 'nim',
            'jenis_pegawai' => 'mahasiswa_magang',
        ]);

        // 2. Import kedua dengan file yang SAMA PERSIS:
        // Harus dilewati (skip) dan tidak membuat duplikat atau update yang tidak perlu
        $uploadedFile2 = new UploadedFile(
            $tempPath,
            'import_magang.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response2 = $this->actingAs($operator)->post('/operator/employees/import-excel', [
            'excel_file' => $uploadedFile2,
        ]);

        $response2->assertRedirect('/operator/employees');
        $response2->assertSessionHas('info');
        $importResult = session('import_result');
        $this->assertEquals(0, $importResult['created']);
        $this->assertEquals(0, $importResult['updated']);
        $this->assertEquals(2, $importResult['skipped_same']); // Kedua data di-skip!

        // Jumlah user di database tidak bertambah
        $this->assertEquals(1, User::where('nip', '0059988776')->count());
        $this->assertEquals(1, User::where('nip', 'F1081211099')->count());

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_import_updates_when_data_is_different()
    {
        $operator = $this->createOperator();

        // Buat user awal di DB
        User::create([
            'nip' => '0051122334',
            'name' => 'Rizky Febrian',
            'tipe_identitas' => 'nisn',
            'jenis_pegawai' => 'siswa_magang',
            'jabatan' => 'Siswa Magang',
            'asal_instansi' => 'SMKN 2 Pontianak',
            'no_hp' => '081200000000',
            'role' => 'karyawan',
            'password' => Hash::make('password'),
        ]);

        // Buat Excel dengan no_hp dan jabatan yang diperbarui
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'NIP');
        $sheet->setCellValue('B1', 'NAMA');
        $sheet->setCellValue('C1', 'KATEGORI');
        $sheet->setCellValue('D1', 'JABATAN');
        $sheet->setCellValue('E1', 'ASAL');
        $sheet->setCellValue('F1', 'NO HP');
        $sheet->setCellValue('G1', 'EMAIL');

        $sheet->setCellValue('A2', '0051122334');
        $sheet->setCellValue('B2', 'Rizky Febrian');
        $sheet->setCellValue('C2', 'siswa_magang');
        $sheet->setCellValue('D2', 'Teknisi Jaringan Magang'); // Berubah
        $sheet->setCellValue('E2', 'SMKN 2 Pontianak');
        $sheet->setCellValue('F2', '081299998888'); // Berubah
        $sheet->setCellValue('G2', '');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_diff_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'import_diff.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($operator)->post('/operator/employees/import-excel', [
            'excel_file' => $uploadedFile,
        ]);

        $response->assertRedirect('/operator/employees');
        $response->assertSessionHas('success');

        $importResult = session('import_result');
        $this->assertEquals(0, $importResult['created']);
        $this->assertEquals(1, $importResult['updated']); // 1 data updated
        $this->assertEquals(0, $importResult['skipped_same']);

        $this->assertDatabaseHas('users', [
            'nip' => '0051122334',
            'jabatan' => 'Teknisi Jaringan Magang',
            'no_hp' => '081299998888',
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_import_fails_with_invalid_file_and_gives_error_notification()
    {
        $operator = $this->createOperator();

        $invalidFile = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $response = $this->actingAs($operator)->post('/operator/employees/import-excel', [
            'excel_file' => $invalidFile,
        ]);

        $response->assertRedirect('/operator/employees');
        $response->assertSessionHas('error');
        $importResult = session('import_result');
        $this->assertEquals('error', $importResult['status']);
    }

    public function test_operator_can_import_from_csv_with_semicolon_delimiter()
    {
        $operator = $this->createOperator();

        $csvContent = "NIP;NAMA;KATEGORI;JABATAN;SEKOLAH;NO HP;EMAIL\n" .
                      "199003032020011009;Agus Setiawan;pegawai;Staff TI;;081234112233;agus@pt-pontianak.go.id\n";

        // Simpan tanpa ekstensi .csv pada temp path seperti upload asli PHP
        $tempPath = tempnam(sys_get_temp_dir(), 'php_test_csv_');
        file_put_contents($tempPath, $csvContent);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'pegawai_import.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($operator)->post('/operator/employees/import-excel', [
            'excel_file' => $uploadedFile,
        ]);

        $response->assertRedirect('/operator/employees');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'nip' => '199003032020011009',
            'name' => 'Agus Setiawan',
            'role' => 'karyawan',
            'jabatan' => 'Staff TI',
            'no_hp' => '081234112233',
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_operator_can_import_excel_with_raw_temp_upload_file()
    {
        $operator = $this->createOperator();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'NIP');
        $sheet->setCellValue('B1', 'NAMA');
        $sheet->setCellValue('C1', 'KATEGORI');
        $sheet->setCellValue('D1', 'JABATAN');
        $sheet->setCellValue('E1', 'ASAL');
        $sheet->setCellValue('F1', 'NO HP');
        $sheet->setCellValue('G1', 'EMAIL');

        $sheet->setCellValue('A2', '199205052021011010');
        $sheet->setCellValue('B2', 'Citra Kirana');
        $sheet->setCellValue('C2', 'pegawai');
        $sheet->setCellValue('D2', 'Analis Hukum');
        $sheet->setCellValue('E2', '');
        $sheet->setCellValue('F2', '081399887766');
        $sheet->setCellValue('G2', 'citra@pt-pontianak.go.id');

        // Path sementara TANPA ekstensi .xlsx (mensimulasikan perilaku temp file PHP upload /tmp/phpXXXX)
        $tempPath = tempnam(sys_get_temp_dir(), 'php_upload_raw_');
        (new Xlsx($spreadsheet))->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'data_pegawai.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($operator)->post('/operator/employees/import-excel', [
            'excel_file' => $uploadedFile,
        ]);

        $response->assertRedirect('/operator/employees');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'nip' => '199205052021011010',
            'name' => 'Citra Kirana',
            'jabatan' => 'Analis Hukum',
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}


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
}

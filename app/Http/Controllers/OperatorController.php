<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Setting;
use App\Models\Holiday;
use App\Services\AttendanceExportService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller Portal Utama Operator Presensi
 *
 * Mengelola seluruh fungsi manajerial dan administrasi presensi Pengadilan Tinggi Pontianak:
 * 1. Dashboard pemantauan statistik presensi harian secara waktu nyata (real-time).
 * 2. Manajemen jam kerja mingguan (Senin - Jumat) serta pengaturan jadwal harian khusus.
 * 3. Manajemen data pegawai, peserta magang kampus (NIM), dan siswa magang sekolah (NISN).
 * 4. Rekapitulasi laporan kehadiran, ekspor berkas Excel resmi (Book1.xlsx), dan cetak PDF/A4.
 * 5. Verifikasi, persetujuan, atau penolakan bukti foto presensi webcam.
 * 6. Konfigurasi koordinat kantor GPS, radius geofencing, serta pejabat penandatangan laporan.
 * 7. Manajemen dan persetujuan pengajuan permohonan cuti pegawai.
 * 8. Pengelolaan kalender hari libur nasional & cuti bersama (tanggal merah).
 * 9. Pengaturan profil, perubahan NIP, dan kata sandi akun operator secara mandiri.
 */
class OperatorController extends Controller
{
    /**
     * Menampilkan dashboard utama operator presensi.
     * Memuat ringkasan statistik kehadiran harian, status 4 jendela presensi hari ini,
     * daftar pegawai yang sedang cuti resmi, serta presensi terbaru yang masuk.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $today = Carbon::today()->format('Y-m-d');
        $totalKaryawan = User::where('role', 'karyawan')->count();

        // Mengambil seluruh rekaman presensi hari ini diurutkan dari yang paling baru
        $todayAttendances = Attendance::where('tanggal', $today)->with('user')->latest('waktu')->get();

        // Menghitung jumlah presensi per sesi hari ini
        $countMasuk = $todayAttendances->where('tipe', 'masuk')->count();
        $countIstirahat = $todayAttendances->where('tipe', 'istirahat')->count();
        $countMasukIstirahat = $todayAttendances->where('tipe', 'masuk_istirahat')->count();
        $countPulang = $todayAttendances->where('tipe', 'pulang')->count();

        $scheduleToday = Schedule::getScheduleForDate($today);

        // Status jendela buka / tutup untuk 4 sesi presensi hari ini
        $now = Carbon::now();
        $windows = [
            'masuk' => $scheduleToday->getWindowStatus('masuk', $now),
            'istirahat' => $scheduleToday->getWindowStatus('istirahat', $now),
            'masuk_istirahat' => $scheduleToday->getWindowStatus('masuk_istirahat', $now),
            'pulang' => $scheduleToday->getWindowStatus('pulang', $now),
        ];

        // Daftar pegawai yang sedang dalam masa cuti disetujui hari ini
        $todayLeaves = Leave::with('user')
            ->where('status', 'disetujui')
            ->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->get();
        $pendingLeavesCount = Leave::where('status', 'menunggu')->count();

        return view('operator.dashboard', compact(
            'totalKaryawan',
            'todayAttendances',
            'countMasuk',
            'countIstirahat',
            'countMasukIstirahat',
            'countPulang',
            'scheduleToday',
            'windows',
            'todayLeaves',
            'pendingLeavesCount'
        ));
    }

    /**
     * Menampilkan halaman pengelolaan jam kerja mingguan (Senin s/d Jumat) dan hari libur akhir pekan.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function scheduleIndex(Request $request)
    {
        $selectedHari = strtolower($request->get('hari', Schedule::getHariNameIndonesian(Carbon::now()->dayOfWeekIso)));
        if ($selectedHari === 'sabtu' || $selectedHari === 'minggu') {
            $selectedHari = 'senin'; // Default ke hari Senin jika diakses pada akhir pekan
        }

        // Mengambil jadwal kerja untuk 7 hari dalam seminggu
        $workDays = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $daySchedules = [];

        foreach ($workDays as $day) {
            $sched = Schedule::where('hari', $day)->first();
            if (!$sched) {
                $isWk = ($day === 'sabtu' || $day === 'minggu');
                $sched = new Schedule([
                    'hari' => $day,
                    'jam_masuk' => '08:00:00',
                    'jam_istirahat' => ($day === 'jumat') ? '11:30:00' : '12:00:00',
                    'jam_masuk_istirahat' => '13:00:00',
                    'jam_pulang' => ($day === 'jumat') ? '16:30:00' : '17:00:00',
                    'is_libur' => $isWk,
                    'keterangan' => $isWk ? 'Hari Libur' : 'Hari Kerja ' . Schedule::getHariLabel($day),
                ]);
            }
            $daySchedules[$day] = $sched;
        }

        $activeSchedule = $daySchedules[$selectedHari] ?? $daySchedules['senin'];

        return view('operator.schedules.index', compact('daySchedules', 'selectedHari', 'activeSchedule'));
    }

    /**
     * Menyimpan atau memperbarui jam kerja per hari (Senin s/d Jumat).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function scheduleStore(Request $request)
    {
        $validated = $request->validate([
            'hari' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_istirahat' => 'required|date_format:H:i',
            'jam_masuk_istirahat' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i',
            'is_libur' => 'nullable|boolean',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $validated['jam_masuk'] = $validated['jam_masuk'] . ':00';
        $validated['jam_istirahat'] = $validated['jam_istirahat'] . ':00';
        $validated['jam_masuk_istirahat'] = $validated['jam_masuk_istirahat'] . ':00';
        $validated['jam_pulang'] = $validated['jam_pulang'] . ':00';
        $validated['is_libur'] = $request->has('is_libur');

        Schedule::updateOrCreate(
            ['hari' => $validated['hari']],
            $validated
        );

        return redirect()->route('operator.schedules.index', ['hari' => $validated['hari']])
            ->with('success', 'Pengaturan jam kerja Hari ' . Schedule::getHariLabel($validated['hari']) . ' berhasil disimpan!');
    }

    /**
     * Menampilkan daftar master data pegawai dan peserta magang lengkap dengan fitur pencarian dan filter kategori.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function employeeIndex(Request $request)
    {
        $search = $request->get('search');
        $jenis_pegawai = $request->get('jenis_pegawai');
        $query = User::where('role', 'karyawan');

        if ($jenis_pegawai) {
            $query->where('jenis_pegawai', $jenis_pegawai);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%")
                  ->orWhere('asal_instansi', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        $countAll = User::where('role', 'karyawan')->count();
        $countPegawai = User::where('role', 'karyawan')->where('jenis_pegawai', 'pegawai')->count();
        $countMahasiswa = User::where('role', 'karyawan')->where('jenis_pegawai', 'mahasiswa_magang')->count();
        $countSiswa = User::where('role', 'karyawan')->where('jenis_pegawai', 'siswa_magang')->count();

        return view('operator.employees.index', compact(
            'employees',
            'search',
            'jenis_pegawai',
            'countAll',
            'countPegawai',
            'countMahasiswa',
            'countSiswa'
        ));
    }

    /**
     * Menambahkan akun pegawai atau peserta magang baru ke dalam sistem.
     * Password akun baru diatur secara otomatis ke nilai default "password".
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function employeeStore(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|string|max:50|unique:users,nip',
            'tipe_identitas' => 'required|in:nip,nim,nisn',
            'jenis_pegawai' => 'required|in:pegawai,mahasiswa_magang,siswa_magang',
            'name' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:100',
            'asal_instansi' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'no_hp' => 'nullable|string|max:30',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'nip.required' => 'Nomor Identitas (NIP / NIM / NISN) wajib diisi.',
            'nip.unique' => 'Nomor Identitas sudah terdaftar pada sistem.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'foto.image' => 'Berkas foto harus berupa gambar.',
            'foto.mimes' => 'Format foto harus berupa JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran berkas foto maksimal 2MB.',
        ]);

        if (empty($validated['jabatan'])) {
            if ($validated['jenis_pegawai'] === 'mahasiswa_magang') {
                $validated['jabatan'] = 'Mahasiswa Magang';
            } elseif ($validated['jenis_pegawai'] === 'siswa_magang') {
                $validated['jabatan'] = 'Siswa Magang';
            } else {
                $validated['jabatan'] = 'Pegawai';
            }
        }

        // Format nama dan asal instansi/sekolah (Capitalize each word)
        $validated['name'] = self::formatPersonName($validated['name'], $validated['jenis_pegawai']);
        if (!empty($validated['asal_instansi'])) {
            $validated['asal_instansi'] = self::formatSchoolName($validated['asal_instansi']);
        }
        if (!empty($validated['no_hp'])) {
            $validated['no_hp'] = self::cleanPhoneNumber($validated['no_hp']);
        }

        $validated['role'] = 'karyawan';
        $validated['password'] = Hash::make('password'); // Kata sandi bawaan awal: password

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $file = $request->file('foto');
            $imgInfo = @getimagesize($file->getRealPath());
            if ($imgInfo === false || !in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                return back()->with('error', 'Berkas yang diunggah tidak valid atau bukan gambar asli.')->withInput();
            }
            $folder = 'uploads/profiles';
            $fullFolder = public_path($folder);
            if (!File::exists($fullFolder)) {
                File::makeDirectory($fullFolder, 0755, true, true);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }
            $filename = 'profile_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($fullFolder, $filename);
            $validated['foto'] = $folder . '/' . $filename;
        }

        User::create($validated);

        return redirect()->route('operator.employees.index')
            ->with('success', 'Data ' . $validated['name'] . ' berhasil ditambahkan! Password default: password');
    }

    /**
     * Memperbarui profil data pegawai atau peserta magang.
     *
     * @param Request $request
     * @param int $id ID akun pegawai yang diperbarui
     * @return \Illuminate\Http\RedirectResponse
     */
    public function employeeUpdate(Request $request, $id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        $validated = $request->validate([
            'nip' => 'required|string|max:50|unique:users,nip,' . $id,
            'tipe_identitas' => 'required|in:nip,nim,nisn',
            'jenis_pegawai' => 'required|in:pegawai,mahasiswa_magang,siswa_magang',
            'name' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:100',
            'asal_instansi' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'no_hp' => 'nullable|string|max:30',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'nip.required' => 'Nomor Identitas (NIP / NIM / NISN) wajib diisi.',
            'nip.unique' => 'Nomor Identitas sudah terdaftar pada sistem.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'foto.image' => 'Berkas foto harus berupa gambar.',
            'foto.mimes' => 'Format foto harus berupa JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran berkas foto maksimal 2MB.',
        ]);

        if ($request->input('hapus_foto') == '1') {
            if ($employee->foto && File::exists(public_path($employee->foto))) {
                File::delete(public_path($employee->foto));
            }
            $validated['foto'] = null;
        } elseif ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $folder = 'uploads/profiles';
            $fullFolder = public_path($folder);
            if (!File::exists($fullFolder)) {
                File::makeDirectory($fullFolder, 0755, true, true);
            }
            if ($employee->foto && File::exists(public_path($employee->foto))) {
                File::delete(public_path($employee->foto));
            }
            $file = $request->file('foto');
            $imgInfo = @getimagesize($file->getRealPath());
            if ($imgInfo === false || !in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                return back()->with('error', 'Berkas yang diunggah tidak valid atau bukan gambar asli.')->withInput();
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }
            $filename = 'profile_' . $employee->id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($fullFolder, $filename);
            $validated['foto'] = $folder . '/' . $filename;
        }

        // Format nama dan asal instansi/sekolah (Capitalize each word)
        $validated['name'] = self::formatPersonName($validated['name'], $validated['jenis_pegawai']);
        if (!empty($validated['asal_instansi'])) {
            $validated['asal_instansi'] = self::formatSchoolName($validated['asal_instansi']);
        }
        if (!empty($validated['no_hp'])) {
            $validated['no_hp'] = self::cleanPhoneNumber($validated['no_hp']);
        }

        $employee->update($validated);

        return redirect()->route('operator.employees.index')
            ->with('success', 'Data ' . $employee->name . ' berhasil diperbarui.');
    }

    /**
     * Melakukan reset kata sandi akun pegawai ke kata sandi default ("password").
     * Digunakan ketika pegawai lupa kata sandinya.
     *
     * @param int $id ID pegawai yang direset
     * @return \Illuminate\Http\RedirectResponse
     */
    public function employeeResetPassword($id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);
        $employee->update([
            'password' => Hash::make('password'),
        ]);

        return redirect()->route('operator.employees.index')
            ->with('success', 'Password karyawan ' . $employee->name . ' telah direset menjadi "password".');
    }

    /**
     * Menghapus akun pegawai dari sistem.
     *
     * @param int $id ID pegawai yang akan dihapus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function employeeDestroy($id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);
        $name = $employee->name;

        // Hapus berkas foto jika ada
        if ($employee->foto && File::exists(public_path($employee->foto))) {
            File::delete(public_path($employee->foto));
        }

        $employee->delete();

        return redirect()->route('operator.employees.index')
            ->with('success', 'Karyawan ' . $name . ' berhasil dihapus.');
    }

    /**
     * Mengunduh berkas foto profil (PP) pegawai untuk keperluan arsip / administrasi operator.
     *
     * @param int $id ID pegawai yang fotonya akan diunduh
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function employeeDownloadPhoto($id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        if (!$employee->hasFoto()) {
            return back()->with('error', 'Pegawai ' . $employee->name . ' belum memiliki foto profil untuk diunduh.');
        }

        $fullPath = public_path($employee->foto);

        if (!File::exists($fullPath)) {
            return back()->with('error', 'Berkas foto profil tidak ditemukan pada penyimpanan server.');
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg');
        $safeName = Str::slug($employee->name, '_');
        $downloadFilename = 'Foto_Profil_' . $safeName . '_' . $employee->nip . '.' . $ext;

        return response()->download($fullPath, $downloadFilename);
    }

    /**
     * Mengunduh berkas template spreadsheet Excel (.xlsx) resmi untuk keperluan import data pegawai.
     *
     * @return StreamedResponse
     */
    public function employeeTemplateExcel(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Pegawai');

        // Baris 1: Judul Kolom Header
        $headers = [
            'A1' => 'NOMOR IDENTITAS (NIP / NIM / NISN) *',
            'B1' => 'NAMA LENGKAP *',
            'C1' => 'KATEGORI STATUS *',
            'D1' => 'JABATAN',
            'E1' => 'ASAL KAMPUS / SEKOLAH',
            'F1' => 'NO HP / WHATSAPP',
            'G1' => 'EMAIL',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Tata Gaya Header Tabel
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '064E3B'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D97706'],
                ],
            ],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Data Contoh Pengisian
        $sampleData = [
            [
                '198507152010121002',
                'Ahmad Pratama, S.H.',
                'pegawai',
                'Panitera Muda Pidana',
                '',
                '081234567890',
                'ahmad.pratama@pt-pontianak.go.id',
            ],
            [
                'F1081211001',
                'Siti Rahmawati',
                'mahasiswa_magang',
                'Mahasiswa Magang',
                'Universitas Tanjungpura',
                '082198765432',
                'siti.rahma@student.untan.ac.id',
            ],
            [
                '0051234567',
                'Budi Santoso',
                'siswa_magang',
                'Siswa Magang',
                'SMKN 1 Pontianak',
                '085211223344',
                'budi.santoso@smk1ptk.sch.id',
            ],
        ];

        $rowIdx = 2;
        foreach ($sampleData as $row) {
            $sheet->setCellValueExplicit('A' . $rowIdx, $row[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $rowIdx, $row[1]);
            $sheet->setCellValue('C' . $rowIdx, $row[2]);
            $sheet->setCellValue('D' . $rowIdx, $row[3]);
            $sheet->setCellValue('E' . $rowIdx, $row[4]);
            $sheet->setCellValueExplicit('F' . $rowIdx, $row[5], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $rowIdx, $row[6]);
            $rowIdx++;
        }

        // Gaya Sel Data Contoh
        $sheet->getStyle('A2:G4')->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);
        $sheet->getStyle('A2:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Petunjuk Tambahan di Bawah Contoh
        $noteRow = 6;
        $sheet->setCellValue('A' . $noteRow, 'PETUNJUK PENGISIAN IMPORT EXCEL:');
        $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true)->getColor()->setRGB('064E3B');

        $notes = [
            '1. Kolom Bertanda (*) Wajib Diisi: Nomor Identitas, Nama Lengkap, dan Kategori Status.',
            '2. Kolom KATEGORI STATUS wajib memilih salah satu nilai: "pegawai", "mahasiswa_magang", atau "siswa_magang".',
            '3. NIP/NIM/NISN akan otomatis menjadi Username saat login presensi.',
            '4. Kata sandi (password) akun baru otomatis disetel ke nilai default: "password"',
            '5. Jika data NIP/NIM/NISN sudah ada di database dan semua datanya sama persis, data otomatis dilewati (skip).',
            '6. Nama siswa/mahasiswa dan sekolah/kampus otomatis diformat huruf kapital tiap kata (Capitalize each word).',
            '7. Baris 2 sampai 4 di atas adalah baris CONTOH, Anda dapat menimpanya dengan data asli.',
        ];

        $currNoteRow = $noteRow + 1;
        foreach ($notes as $note) {
            $sheet->setCellValue('A' . $currNoteRow, $note);
            $sheet->getStyle('A' . $currNoteRow)->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('475569');
            $currNoteRow++;
        }

        // Lebar Kolom Optimal
        $colWidths = [
            'A' => 34,
            'B' => 28,
            'C' => 22,
            'D' => 25,
            'E' => 28,
            'F' => 20,
            'G' => 32,
        ];
        foreach ($colWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $filename = 'Template_Import_Pegawai_PT_Pontianak.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Memformat nama orang (siswa/mahasiswa/pegawai).
     * Untuk siswa dan mahasiswa magang, otomatis diformat Title Case (Capitalize each word).
     * Untuk pegawai, jika ditulis huruf kecil semua atau kapital semua, diformat Capitalize each word.
     * Gelar pada pegawai (misal: "Ahmad Pratama, S.H.") tetap dipertahankan jika sudah bervariasi huruf besar-kecil.
     *
     * @param string $name
     * @param string $jenisPegawai
     * @return string
     */
    public static function formatPersonName(string $name, string $jenisPegawai = 'pegawai'): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        // Hapus spasi berlebih
        $name = preg_replace('/\s+/u', ' ', $name);

        if (in_array($jenisPegawai, ['mahasiswa_magang', 'siswa_magang'])) {
            return Str::title(mb_strtolower($name, 'UTF-8'));
        }

        // Untuk pegawai: jika all uppercase atau all lowercase, ubah ke Title Case
        if (mb_strtoupper($name, 'UTF-8') === $name || mb_strtolower($name, 'UTF-8') === $name) {
            return Str::title(mb_strtolower($name, 'UTF-8'));
        }

        return $name;
    }

    /**
     * Memformat nama sekolah, kampus, atau universitas ke format Capitalize each word (Title Case)
     * dengan tetap menjaga singkatan/akronim institusi pendidikan umum di Indonesia dalam huruf kapital.
     *
     * @param string|null $school
     * @return string|null
     */
    public static function formatSchoolName(?string $school): ?string
    {
        if (empty($school)) {
            return null;
        }

        $school = trim($school);
        if ($school === '' || $school === '-') {
            return null;
        }

        // Hapus spasi berlebih
        $school = preg_replace('/\s+/u', ' ', $school);

        // Ubah ke Title Case
        $formatted = Str::title(mb_strtolower($school, 'UTF-8'));

        // Daftar akronim institusi pendidikan umum yang tetap huruf kapital
        $acronyms = [
            'Smkn' => 'SMKN',
            'Smk' => 'SMK',
            'Sman' => 'SMAN',
            'Sma' => 'SMA',
            'Smpn' => 'SMPN',
            'Smp' => 'SMP',
            'Man' => 'MAN',
            'Mtsn' => 'MTSN',
            'Mts' => 'MTS',
            'Sdn' => 'SDN',
            'Sd' => 'SD',
            'Untan' => 'UNTAN',
            'Polnep' => 'POLNEP',
            'Iain' => 'IAIN',
            'Stmik' => 'STMIK',
            'Umsi' => 'UMSI',
            'Upb' => 'UPB',
            'Ugm' => 'UGM',
            'Itb' => 'ITB',
            'Ui' => 'UI',
            'Upi' => 'UPI',
            'Ptk' => 'PTK',
            'D3' => 'D3',
            'D4' => 'D4',
            'S1' => 'S1',
            'S2' => 'S2',
            'S3' => 'S3',
            'Pkl' => 'PKL',
        ];

        foreach ($acronyms as $pattern => $replacement) {
            $formatted = preg_replace('/\b' . $pattern . '\b/u', $replacement, $formatted);
        }

        return $formatted;
    }

    /**
     * Membersihkan dan menstandarisasi nomor telepon/WhatsApp Indonesia.
     *
     * @param string|null $phone
     * @return string|null
     */
    public static function cleanPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = trim($phone);
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (empty($clean)) {
            return null;
        }

        // Jika diawali 628..., ubah menjadi 08...
        if (str_starts_with($clean, '628')) {
            $clean = '0' . substr($clean, 2);
        } elseif (str_starts_with($clean, '8')) {
            // Jika Excel memotong angka 0 di depan (cth: 81234567890), tambahkan 0
            $clean = '0' . $clean;
        }

        return $clean;
    }

    /**
     * Mengambil nilai sel Excel secara aman tanpa terkena format notasi ilmiah (scientific notation)
     * atau formula yang belum terevaluasi.
     *
     * @param mixed $cell
     * @return string
     */
    public static function getSafeCellValue($cell): string
    {
        if (!$cell) {
            return '';
        }

        try {
            $val = $cell->getValue();
            // Jika formula, ambil hasil kalkulasinya
            if (is_string($val) && str_starts_with($val, '=')) {
                try {
                    $val = $cell->getCalculatedValue();
                } catch (\Throwable $e) {
                    // fallback ke nilai mentah
                }
            }

            // Jika berupa float atau angka besar seperti NIP 18 digit (1.98507E+17)
            if (is_numeric($val)) {
                $valStr = (string) $val;
                if (stripos($valStr, 'e') !== false || is_float($val)) {
                    $val = number_format((float) $val, 0, '', '');
                }
            }

            return trim((string) $val);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Memproses import data pegawai / peserta magang secara massal dari berkas Excel (.xlsx, .xls, .csv).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function employeeImportExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|max:10240',
        ], [
            'excel_file.required' => 'Silakan pilih berkas Excel yang akan diunggah.',
            'excel_file.file' => 'Berkas yang diunggah harus berupa file yang valid.',
            'excel_file.max' => 'Ukuran berkas Excel maksimal 10MB.',
        ]);

        if ($validator->fails()) {
            $errorMsg = $validator->errors()->first();
            return redirect()->route('operator.employees.index')
                ->with('error', $errorMsg)
                ->with('import_result', [
                    'status' => 'error',
                    'title' => 'Import Gagal',
                    'message' => $errorMsg,
                    'created' => 0,
                    'updated' => 0,
                    'skipped_same' => 0,
                    'skipped_incomplete' => 0,
                ]);
        }

        $file = $request->file('excel_file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            $errorMsg = 'Format berkas tidak didukung. Harap unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).';
            return redirect()->route('operator.employees.index')
                ->with('error', $errorMsg)
                ->with('import_result', [
                    'status' => 'error',
                    'title' => 'Format Berkas Tidak Sesuai',
                    'message' => $errorMsg,
                    'created' => 0,
                    'updated' => 0,
                    'skipped_same' => 0,
                    'skipped_incomplete' => 0,
                ]);
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            if ($highestRow < 2) {
                $errorMsg = 'Berkas Excel kosong atau tidak memiliki baris data.';
                return redirect()->route('operator.employees.index')
                    ->with('error', $errorMsg)
                    ->with('import_result', [
                        'status' => 'error',
                        'title' => 'Berkas Kosong',
                        'message' => $errorMsg,
                        'created' => 0,
                        'updated' => 0,
                        'skipped_same' => 0,
                        'skipped_incomplete' => 0,
                    ]);
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedIdenticalCount = 0;
            $skippedIncompleteCount = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
                $nip = self::getSafeCellValue($sheet->getCell("A{$row}"));
                $name = self::getSafeCellValue($sheet->getCell("B{$row}"));
                $kategoriRaw = strtolower(self::getSafeCellValue($sheet->getCell("C{$row}")));
                $jabatan = self::getSafeCellValue($sheet->getCell("D{$row}"));
                $asalInstansi = self::getSafeCellValue($sheet->getCell("E{$row}"));
                $noHp = self::getSafeCellValue($sheet->getCell("F{$row}"));
                $email = self::getSafeCellValue($sheet->getCell("G{$row}"));

                // Jika baris kosong total, lewati
                if (empty($nip) && empty($name) && empty($kategoriRaw) && empty($jabatan) && empty($asalInstansi)) {
                    continue;
                }

                // Jika baris berisi judul petunjuk atau catatan template
                $nipUpper = strtoupper($nip);
                if (str_starts_with($nipUpper, 'PETUNJUK') ||
                    str_starts_with($nipUpper, 'CATATAN') ||
                    $nipUpper === 'NIP' ||
                    $nipUpper === 'NOMOR IDENTITAS' ||
                    $nipUpper === 'NO') {
                    continue;
                }

                // Baris tanpa NIP atau Nama dilewati karena data tidak lengkap
                if (empty($nip) || empty($name)) {
                    $skippedIncompleteCount++;
                    continue;
                }

                // Tentukan jenis pegawai dan tipe identitas
                if (str_contains($kategoriRaw, 'mahasiswa') || str_contains($kategoriRaw, 'kuliah') || str_contains($kategoriRaw, 'nim') || str_contains($kategoriRaw, 'universitas') || str_contains($kategoriRaw, 'politeknik') || str_contains($kategoriRaw, 'kampus')) {
                    $jenisPegawai = 'mahasiswa_magang';
                    $tipeIdentitas = 'nim';
                } elseif (str_contains($kategoriRaw, 'siswa') || str_contains($kategoriRaw, 'smk') || str_contains($kategoriRaw, 'sma') || str_contains($kategoriRaw, 'nisn') || str_contains($kategoriRaw, 'sekolah') || str_contains($kategoriRaw, 'prakerin') || str_contains($kategoriRaw, 'pkl')) {
                    $jenisPegawai = 'siswa_magang';
                    $tipeIdentitas = 'nisn';
                } else {
                    $asalLower = strtolower($asalInstansi);
                    $jabatanLower = strtolower($jabatan);
                    if (str_contains($asalLower, 'smk') || str_contains($asalLower, 'sma') || str_contains($jabatanLower, 'siswa')) {
                        $jenisPegawai = 'siswa_magang';
                        $tipeIdentitas = 'nisn';
                    } elseif (str_contains($asalLower, 'universitas') || str_contains($asalLower, 'institut') || str_contains($asalLower, 'politeknik') || str_contains($jabatanLower, 'mahasiswa')) {
                        $jenisPegawai = 'mahasiswa_magang';
                        $tipeIdentitas = 'nim';
                    } else {
                        $jenisPegawai = 'pegawai';
                        $tipeIdentitas = 'nip';
                    }
                }

                // 1. Kapitalisasi Nama Siswa / Mahasiswa otomatis Capitalize each word
                $formattedName = self::formatPersonName($name, $jenisPegawai);

                // 2. Default Jabatan jika kosong
                if (empty($jabatan)) {
                    if ($jenisPegawai === 'mahasiswa_magang') {
                        $jabatan = 'Mahasiswa Magang';
                    } elseif ($jenisPegawai === 'siswa_magang') {
                        $jabatan = 'Siswa Magang';
                    } else {
                        $jabatan = 'Pegawai';
                    }
                }

                // 3. Kapitalisasi Asal Sekolah / Kampus otomatis Capitalize each word
                $formattedAsalInstansi = !empty($asalInstansi) ? self::formatSchoolName($asalInstansi) : null;

                // 4. Standarisasi Nomor Handphone
                $cleanNoHp = !empty($noHp) ? self::cleanPhoneNumber($noHp) : null;

                // 5. Periksa dan validasi email agar tidak bentrok
                $emailToSave = !empty($email) ? strtolower(trim($email)) : null;
                if ($emailToSave) {
                    $existingEmailUser = User::where('email', $emailToSave)->where('nip', '!=', $nip)->first();
                    if ($existingEmailUser) {
                        $emailToSave = null;
                    }
                }

                // 6. Cek keberadaan akun berdasarkan NIP / NIM / NISN
                $user = User::where('nip', $nip)->first();

                if ($user) {
                    // Periksa apakah semua data di database sudah ada dan sama persis
                    $dbInstansi = !empty($user->asal_instansi) ? trim($user->asal_instansi) : null;
                    $excelInstansi = !empty($formattedAsalInstansi) ? trim($formattedAsalInstansi) : null;

                    $dbNoHp = !empty($user->no_hp) ? self::cleanPhoneNumber($user->no_hp) : null;
                    $excelNoHp = !empty($cleanNoHp) ? $cleanNoHp : null;

                    $dbEmail = !empty($user->email) ? strtolower(trim($user->email)) : null;
                    $excelEmail = $emailToSave;

                    $updateData = [];

                    if ($user->name !== $formattedName) {
                        $updateData['name'] = $formattedName;
                    }
                    if ($user->tipe_identitas !== $tipeIdentitas) {
                        $updateData['tipe_identitas'] = $tipeIdentitas;
                    }
                    if ($user->jenis_pegawai !== $jenisPegawai) {
                        $updateData['jenis_pegawai'] = $jenisPegawai;
                    }
                    if ($user->jabatan !== $jabatan) {
                        $updateData['jabatan'] = $jabatan;
                    }
                    if ($excelInstansi !== null && $excelInstansi !== $dbInstansi) {
                        $updateData['asal_instansi'] = $excelInstansi;
                    }
                    if ($excelNoHp !== null && $excelNoHp !== $dbNoHp) {
                        $updateData['no_hp'] = $excelNoHp;
                    }
                    if ($excelEmail !== null && $excelEmail !== $dbEmail) {
                        $updateData['email'] = $excelEmail;
                    }

                    // Jika SEMUA data di database sudah ada dan sama persis -> SKIP SAJA
                    if (empty($updateData)) {
                        $skippedIdenticalCount++;
                        continue;
                    }

                    // Jika terdapat perbedaan data -> UPDATE data pegawai
                    $user->update($updateData);
                    $updatedCount++;
                } else {
                    // Buat akun baru jika belum terdaftar di database
                    User::create([
                        'nip' => $nip,
                        'tipe_identitas' => $tipeIdentitas,
                        'jenis_pegawai' => $jenisPegawai,
                        'name' => $formattedName,
                        'jabatan' => $jabatan,
                        'asal_instansi' => $formattedAsalInstansi,
                        'no_hp' => $cleanNoHp,
                        'email' => $emailToSave,
                        'role' => 'karyawan',
                        'password' => Hash::make('password'),
                    ]);
                    $createdCount++;
                }
            }

            $totalProcessed = $createdCount + $updatedCount + $skippedIdenticalCount;

            if ($totalProcessed === 0) {
                if ($skippedIncompleteCount > 0) {
                    $errMsg = "Tidak ada data yang berhasil diimpor. {$skippedIncompleteCount} baris dilewati karena NIP atau Nama kosong.";
                } else {
                    $errMsg = "Berkas Excel tidak memiliki data pegawai yang dapat diproses.";
                }
                return redirect()->route('operator.employees.index')
                    ->with('error', $errMsg)
                    ->with('import_result', [
                        'status' => 'error',
                        'title' => 'Import Gagal',
                        'message' => $errMsg,
                        'created' => 0,
                        'updated' => 0,
                        'skipped_same' => 0,
                        'skipped_incomplete' => $skippedIncompleteCount,
                    ]);
            }

            // Susun teks ringkasan hasil import
            $parts = [];
            if ($createdCount > 0) {
                $parts[] = "{$createdCount} data baru ditambahkan";
            }
            if ($updatedCount > 0) {
                $parts[] = "{$updatedCount} data diperbarui";
            }
            if ($skippedIdenticalCount > 0) {
                $parts[] = "{$skippedIdenticalCount} data dilewati (sudah ada & sama)";
            }

            $summaryText = implode(', ', $parts);
            if ($skippedIncompleteCount > 0) {
                $summaryText .= ". ({$skippedIncompleteCount} baris tidak lengkap dilewati)";
            }

            if ($createdCount === 0 && $updatedCount === 0 && $skippedIdenticalCount > 0) {
                $statusType = 'info';
                $titleText = 'Data Sudah Terdaftar';
                $flashMessage = "Semua data ({$skippedIdenticalCount} data) sudah ada di database dan sama persis, sehingga dilewati (skip).";
            } else {
                $statusType = 'success';
                $titleText = 'Import Berhasil';
                $flashMessage = "Import data pegawai berhasil! " . $summaryText;
            }

            return redirect()->route('operator.employees.index')
                ->with($statusType, $flashMessage)
                ->with('import_result', [
                    'status' => $statusType,
                    'title' => $titleText,
                    'message' => $flashMessage,
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'skipped_same' => $skippedIdenticalCount,
                    'skipped_incomplete' => $skippedIncompleteCount,
                ]);
        } catch (\Throwable $e) {
            return redirect()->route('operator.employees.index')
                ->with('error', 'Gagal memproses berkas Excel: ' . $e->getMessage())
                ->with('import_result', [
                    'status' => 'error',
                    'title' => 'Gagal Memproses Berkas',
                    'message' => 'Terjadi kesalahan saat memproses berkas Excel: ' . $e->getMessage(),
                    'created' => 0,
                    'updated' => 0,
                    'skipped_same' => 0,
                    'skipped_incomplete' => 0,
                ]);
        }
    }

    /**
     * Menampilkan rekapitulasi data laporan presensi dengan filter rentang tanggal, jenis sesi, status verifikasi, dan pegawai.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function attendanceReports(Request $request)
    {
        $tanggal_mulai = $request->get('tanggal_mulai');
        $tanggal_selesai = $request->get('tanggal_selesai');

        // Nilai fallback jika hanya 1 tanggal yang dikirimkan
        if (!$tanggal_mulai && $request->has('tanggal')) {
            $tanggal_mulai = $request->get('tanggal');
            $tanggal_selesai = $request->get('tanggal');
        }

        // Standar bawaan: awal bulan berjalan hingga hari ini
        if (!$tanggal_mulai) {
            $tanggal_mulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        if (!$tanggal_selesai) {
            $tanggal_selesai = Carbon::today()->format('Y-m-d');
        }

        $tipe = $request->get('tipe');
        $user_id = $request->get('user_id');
        $approval_status = $request->get('approval_status');

        $query = Attendance::with('user');

        if ($tanggal_mulai && $tanggal_selesai) {
            $query->whereBetween('tanggal', [$tanggal_mulai, $tanggal_selesai]);
        } elseif ($tanggal_mulai) {
            $query->where('tanggal', '>=', $tanggal_mulai);
        } elseif ($tanggal_selesai) {
            $query->where('tanggal', '<=', $tanggal_selesai);
        }

        if ($tipe) {
            $query->where('tipe', $tipe);
        }

        if ($user_id) {
            $query->where('user_id', $user_id);
        }

        if ($approval_status) {
            $query->where('approval_status', $approval_status);
        }

        // Akumulasi metrik ringkasan untuk periode yang difilter
        $cloneQuery = clone $query;
        $totalPresensi = $cloneQuery->count();
        $totalDiterima = (clone $query)->where('approval_status', 'diterima')->count();
        $totalDitolak = (clone $query)->where('approval_status', 'ditolak')->count();
        $totalTerlambat = (clone $query)->where('status', 'terlambat')->count();

        $attendances = $query->orderBy('tanggal', 'desc')->orderBy('waktu', 'desc')->paginate(25)->withQueryString();
        $employees = User::where('role', 'karyawan')->orderBy('name', 'asc')->get();

        return view('operator.reports.index', compact(
            'attendances',
            'employees',
            'tanggal_mulai',
            'tanggal_selesai',
            'tipe',
            'user_id',
            'approval_status',
            'totalPresensi',
            'totalDiterima',
            'totalDitolak',
            'totalTerlambat'
        ));
    }

    /**
     * Mengekspor rekapitulasi data presensi ke dalam berkas format Excel resmi (Book1.xlsx).
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportReports(Request $request)
    {
        $tanggal_mulai = $request->get('tanggal_mulai', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tanggal_selesai = $request->get('tanggal_selesai', Carbon::today()->format('Y-m-d'));
        $user_id = $request->get('user_id');

        $exportService = new AttendanceExportService();
        return $exportService->exportBook1Format(
            $tanggal_mulai,
            $tanggal_selesai,
            $user_id ? (int)$user_id : null
        );
    }

    /**
     * Menampilkan halaman cetak laporan resmi A4 lengkap dengan Kop Surat resmi Mahkamah Agung RI,
     * tabel rekapitulasi / matriks timesheet harian, serta blok tanda tangan basah pimpinan.
     * Mode tampilan otomatis menyesuaikan:
     * - Cetak Seluruh Pegawai: Rekapitulasi tabel matriks bulanan seluruh pegawai.
     * - Cetak 1 Pegawai: Lembar Timesheet harian (tanggal per tanggal) lengkap dengan tanda tangan ganda (Pimpinan & Pegawai).
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function printReports(Request $request)
    {
        $tanggal_mulai = $request->get('tanggal_mulai', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tanggal_selesai = $request->get('tanggal_selesai', Carbon::today()->format('Y-m-d'));
        $user_id = $request->get('user_id');

        $start = Carbon::parse($tanggal_mulai)->startOfDay();
        $end = Carbon::parse($tanggal_selesai)->endOfDay();
        $today = Carbon::today();
        $period = CarbonPeriod::create($start, $end);

        $singleEmployee = null;
        $dailyRecords = [];

        if ($user_id) {
            $singleEmployee = User::where('id', $user_id)->where('role', 'karyawan')->first();
            $employees = $singleEmployee ? collect([$singleEmployee]) : User::where('role', 'karyawan')->orderBy('name', 'asc')->get();
        } else {
            $employees = User::where('role', 'karyawan')->orderBy('name', 'asc')->get();
        }

        $startDateStr = $start->format('Y-m-d');
        $endDateStr = $end->format('Y-m-d');

        $employeeStats = [];

        foreach ($employees as $emp) {
            $countMasuk = 0;
            $countIstirahat = 0;
            $countMasukIstirahat = 0;
            $countPulang = 0;

            $countTepatWaktu = 0;
            $countTerlambat = 0;
            $countLebihAwal = 0;
            $countDitolak = 0;
            $countTanpaKeterangan = 0;

            $countCutiTahunan = 0;
            $countCutiSakit = 0;
            $countCutiLN = 0;
            $countCutiLainnya = 0;
            $totalHariKerja = 0;

            // Preload presensi dan izin pegawai untuk rentang tanggal agar query sangat cepat
            $empAttendances = Attendance::where('user_id', $emp->id)
                ->whereBetween('tanggal', [$startDateStr, $endDateStr])
                ->get()
                ->groupBy('tanggal');

            $empLeaves = Leave::where('user_id', $emp->id)
                ->where('status', 'disetujui')
                ->whereDate('tanggal_mulai', '<=', $endDateStr)
                ->whereDate('tanggal_selesai', '>=', $startDateStr)
                ->get();

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                if ($date->gt($today)) {
                    continue;
                }

                $schedule = Schedule::getScheduleForDate($dateStr);
                if ($schedule->is_libur) {
                    continue;
                }

                $totalHariKerja++;
                $approvedLeave = $empLeaves->first(function ($l) use ($dateStr) {
                    return $l->tanggal_mulai <= $dateStr && $l->tanggal_selesai >= $dateStr;
                });

                $allDayAttendances = $empAttendances->get($dateStr, collect());

                $approvedAttendances = $allDayAttendances->where('approval_status', 'diterima');
                $rejectedAttendances = $allDayAttendances->where('approval_status', 'ditolak');

                if ($approvedLeave) {
                    if ($approvedLeave->jenis_cuti === 'cuti_tahunan') $countCutiTahunan++;
                    elseif ($approvedLeave->jenis_cuti === 'cuti_sakit') $countCutiSakit++;
                    elseif ($approvedLeave->jenis_cuti === 'cuti_luar_negeri') $countCutiLN++;
                    else $countCutiLainnya++;
                }

                if ($allDayAttendances->isEmpty()) {
                    if (!$approvedLeave) {
                        $countTanpaKeterangan++;
                    }
                } else {
                    if ($approvedAttendances->firstWhere('tipe', 'masuk')) $countMasuk++;
                    if ($approvedAttendances->firstWhere('tipe', 'istirahat')) $countIstirahat++;
                    if ($approvedAttendances->firstWhere('tipe', 'masuk_istirahat')) $countMasukIstirahat++;
                    if ($approvedAttendances->firstWhere('tipe', 'pulang')) $countPulang++;

                    foreach ($approvedAttendances as $att) {
                        if ($att->status === 'tepat_waktu') $countTepatWaktu++;
                        elseif ($att->status === 'terlambat') $countTerlambat++;
                        elseif ($att->status === 'lebih_awal') $countLebihAwal++;
                        elseif ($att->status === 'sakit') $countCutiSakit++;
                        elseif ($att->status === 'izin') $countCutiLainnya++;
                    }

                    $countDitolak += $rejectedAttendances->count();
                }
            }

            $keteranganParts = [];
            if ($countCutiTahunan > 0) $keteranganParts[] = "Cuti Tahunan: {$countCutiTahunan} hr";
            if ($countCutiSakit > 0) $keteranganParts[] = "Cuti Sakit: {$countCutiSakit} hr";
            if ($countCutiLN > 0) $keteranganParts[] = "Cuti LN: {$countCutiLN} hr";
            if ($countCutiLainnya > 0) $keteranganParts[] = "Cuti Lainnya: {$countCutiLainnya} hr";
            $keteranganText = implode(', ', $keteranganParts);

            $cutiTotal = $countCutiTahunan + $countCutiSakit + $countCutiLN + $countCutiLainnya;

            $employeeStats[] = [
                'user' => $emp,
                'total_hari_kerja' => $totalHariKerja,
                'masuk' => $countMasuk,
                'istirahat' => $countIstirahat,
                'masuk_istirahat' => $countMasukIstirahat,
                'pulang' => $countPulang,
                'tepat_waktu' => $countTepatWaktu,
                'terlambat' => $countTerlambat,
                'lebih_awal' => $countLebihAwal,
                'ditolak' => $countDitolak,
                'tanpa_keterangan' => $countTanpaKeterangan,
                'cuti_total' => $cutiTotal,
                'cuti_tahunan' => $countCutiTahunan,
                'cuti_sakit' => $countCutiSakit,
                'cuti_luar_negeri' => $countCutiLN,
                'cuti_lainnya' => $countCutiLainnya,
                'keterangan' => $keteranganText,
            ];
        }

        // If a single employee is selected, build the day-by-day timesheet records
        if ($singleEmployee) {
            $singleAttendances = Attendance::where('user_id', $singleEmployee->id)
                ->whereBetween('tanggal', [$startDateStr, $endDateStr])
                ->get()
                ->groupBy('tanggal');

            $singleLeaves = Leave::where('user_id', $singleEmployee->id)
                ->where('status', 'disetujui')
                ->whereDate('tanggal_mulai', '<=', $endDateStr)
                ->whereDate('tanggal_selesai', '>=', $startDateStr)
                ->get();

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $schedule = Schedule::getScheduleForDate($dateStr);
                $isFuture = $date->gt($today);
                $approvedLeave = $singleLeaves->first(function ($l) use ($dateStr) {
                    return $l->tanggal_mulai <= $dateStr && $l->tanggal_selesai >= $dateStr;
                });

                $dayAttendances = $singleAttendances->get($dateStr, collect());

                $masuk = $dayAttendances->firstWhere('tipe', 'masuk');
                $istirahat = $dayAttendances->firstWhere('tipe', 'istirahat');
                $masukIst = $dayAttendances->firstWhere('tipe', 'masuk_istirahat');
                $pulang = $dayAttendances->firstWhere('tipe', 'pulang');

                // Determine day status & badge class
                $statusHarian = '';
                $statusBadgeClass = '';

                if ($schedule->is_libur) {
                    $statusHarian = 'Libur (' . ($schedule->keterangan_libur ?: 'Akhir Pekan') . ')';
                    $statusBadgeClass = 'libur';
                } elseif ($approvedLeave) {
                    $statusHarian = Leave::getJenisCutiLabel($approvedLeave->jenis_cuti);
                    $statusBadgeClass = 'cuti';
                } elseif ($dayAttendances->isEmpty()) {
                    if ($isFuture) {
                        $statusHarian = '-';
                        $statusBadgeClass = 'future';
                    } else {
                        $statusHarian = 'Tanpa Keterangan (ALFA)';
                        $statusBadgeClass = 'alfa';
                    }
                } else {
                    $rejected = $dayAttendances->where('approval_status', 'ditolak');
                    if ($rejected->isNotEmpty()) {
                        $statusHarian = 'Ditolak: ' . ($rejected->first()->catatan_operator ?: 'Foto Invalid');
                        $statusBadgeClass = 'ditolak';
                    } else {
                        $hasSakit = $dayAttendances->contains('status', 'sakit');
                        $hasIzin = $dayAttendances->contains('status', 'izin');
                        $hasLate = $dayAttendances->contains('status', 'terlambat');

                        if ($dayAttendances->every(fn($a) => $a->status === 'sakit')) {
                            $statusHarian = 'Sakit';
                            $statusBadgeClass = 'cuti';
                        } elseif ($dayAttendances->every(fn($a) => $a->status === 'izin')) {
                            $statusHarian = 'Izin';
                            $statusBadgeClass = 'cuti';
                        } elseif ($hasSakit) {
                            $statusHarian = 'Hadir Sebagian (Sakit)';
                            $statusBadgeClass = 'cuti';
                        } elseif ($hasIzin) {
                            $statusHarian = 'Hadir Sebagian (Izin)';
                            $statusBadgeClass = 'cuti';
                        } elseif ($hasLate) {
                            $statusHarian = 'Hadir (Terlambat)';
                            $statusBadgeClass = 'terlambat';
                        } else {
                            $statusHarian = 'Hadir (Tepat Waktu)';
                            $statusBadgeClass = 'hadir';
                        }
                    }
                }

                $dailyRecords[] = [
                    'date' => $date,
                    'date_str' => $dateStr,
                    'hari' => $date->translatedFormat('l'),
                    'tanggal_formatted' => $date->translatedFormat('d M Y'),
                    'is_libur' => $schedule->is_libur,
                    'is_future' => $isFuture,
                    'masuk' => $masuk,
                    'istirahat' => $istirahat,
                    'masuk_istirahat' => $masukIst,
                    'pulang' => $pulang,
                    'leave' => $approvedLeave,
                    'status_harian' => $statusHarian,
                    'status_badge_class' => $statusBadgeClass,
                ];
            }
        }

        $setting = Setting::getOfficeSetting();

        return view('operator.reports.print', compact(
            'employeeStats',
            'singleEmployee',
            'dailyRecords',
            'setting',
            'tanggal_mulai',
            'tanggal_selesai',
            'user_id'
        ));
    }

    /**
     * Menampilkan halaman konfigurasi titik koordinat GPS kantor, batas radius toleransi, dan identitas Ketua Pengadilan.
     *
     * @return \Illuminate\View\View
     */
    public function locationSettingsIndex()
    {
        $setting = Setting::getOfficeSetting();
        return view('operator.settings.location', compact('setting'));
    }

    /**
     * Memperbarui pengaturan instansi, titik koordinat kantor, radius geofencing, serta data pejabat penandatangan laporan.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function locationSettingsUpdate(Request $request)
    {
        $validated = $request->validate([
            'nama_kantor' => 'required|string|max:255',
            'nama_ketua' => 'required|string|max:255',
            'jabatan_ketua' => 'required|string|max:255',
            'nip_ketua' => 'nullable|string|max:100',
            'satker_name' => 'required|string|max:255',
            'kota_surat' => 'required|string|max:100',
            'latitude_kantor' => 'required|numeric',
            'longitude_kantor' => 'required|numeric',
            'radius_meter' => 'required|integer|min:10|max:10000',
            'enforce_radius' => 'nullable|boolean',
        ], [
            'nama_kantor.required' => 'Nama kantor wajib diisi.',
            'nama_ketua.required' => 'Nama Ketua / Pejabat Penandatangan wajib diisi.',
            'jabatan_ketua.required' => 'Jabatan Penandatangan wajib diisi.',
            'satker_name.required' => 'Nama Satker wajib diisi.',
            'kota_surat.required' => 'Kota surat dokumen wajib diisi.',
            'latitude_kantor.required' => 'Latitude kantor wajib ditentukan di peta.',
            'longitude_kantor.required' => 'Longitude kantor wajib ditentukan di peta.',
            'radius_meter.required' => 'Batas radius (meter) wajib diisi.',
        ]);

        $validated['enforce_radius'] = $request->has('enforce_radius');

        $setting = Setting::getOfficeSetting();
        $setting->update($validated);

        return redirect()->route('operator.location.index')
            ->with('success', 'Pengaturan Instansi, Nama Ketua & Lokasi Kantor berhasil disimpan!');
    }

    /**
     * Menolak rekaman presensi pegawai (misal foto wajah tidak valid, gelap, atau bukan wajah pegawai).
     * Presensi yang ditolak otomatis dianggap tidak hadir (ALFA).
     *
     * @param int $id ID rekaman presensi yang ditolak
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function rejectAttendance($id, Request $request)
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        $reason = $request->get('catatan_operator', 'Wajah foto tidak sesuai / tidak valid');

        $attendance->update([
            'approval_status' => 'ditolak',
            'catatan_operator' => $reason,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi karyawan ' . $attendance->user->name . ' telah DITOLAK (Dianggap ALFA / Belum Absen).'
            ]);
        }

        return back()->with('success', 'Presensi karyawan ' . $attendance->user->name . ' telah DITOLAK (Dianggap ALFA / Belum Absen).');
    }

    /**
     * Menyetujui atau memulihkan kembali rekaman presensi pegawai yang sebelumnya berstatus ditolak.
     *
     * @param int $id ID presensi yang disetujui
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function approveAttendance($id, Request $request)
    {
        $attendance = Attendance::with('user')->findOrFail($id);

        $attendance->update([
            'approval_status' => 'diterima',
            'catatan_operator' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi karyawan ' . $attendance->user->name . ' telah DITERIMA.'
            ]);
        }

        return back()->with('success', 'Presensi karyawan ' . $attendance->user->name . ' telah DITERIMA.');
    }

    /**
     * Menampilkan daftar seluruh pengajuan izin cuti pegawai (Cuti Tahunan, Sakit, Luar Negeri, Alasan Penting).
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function leaveIndex(Request $request)
    {
        $jenis_cuti = $request->get('jenis_cuti');
        $status = $request->get('status');
        $user_id = $request->get('user_id');
        $search = $request->get('search');

        $query = Leave::with('user');

        if ($jenis_cuti) {
            $query->where('jenis_cuti', $jenis_cuti);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($user_id) {
            $query->where('user_id', $user_id);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $leaves = $query->orderBy('tanggal_mulai', 'desc')->paginate(15)->withQueryString();
        $employees = User::where('role', 'karyawan')->orderBy('name', 'asc')->get();

        // Summary Counts
        $totalAllLeaves = Leave::count();
        $totalCutiTahunan = Leave::where('jenis_cuti', 'cuti_tahunan')->where('status', 'disetujui')->count();
        $totalCutiSakit = Leave::where('jenis_cuti', 'cuti_sakit')->where('status', 'disetujui')->count();
        $totalCutiLuarNegeri = Leave::where('jenis_cuti', 'cuti_luar_negeri')->where('status', 'disetujui')->count();
        $totalMenunggu = Leave::where('status', 'menunggu')->count();

        // Today Active Leaves Count
        $today = Carbon::today()->format('Y-m-d');
        $totalActiveToday = Leave::where('status', 'disetujui')
            ->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->count();

        return view('operator.leaves.index', compact(
            'leaves',
            'employees',
            'jenis_cuti',
            'status',
            'user_id',
            'search',
            'totalAllLeaves',
            'totalCutiTahunan',
            'totalCutiSakit',
            'totalCutiLuarNegeri',
            'totalMenunggu',
            'totalActiveToday'
        ));
    }

    /**
     * Menyimpan data pengajuan izin cuti pegawai baru yang diinputkan oleh operator.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'jenis_cuti' => 'required|in:cuti_tahunan,cuti_sakit,cuti_luar_negeri,cuti_alasan_penting,cuti_lainnya',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,menunggu,ditolak',
            'dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'catatan_operator' => 'nullable|string|max:500',
        ], [
            'user_id.required' => 'Pegawai wajib dipilih.',
            'jenis_cuti.required' => 'Jenis cuti wajib dipilih.',
            'tanggal_mulai.required' => 'Tanggal mulai cuti wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai cuti wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'dokumen.max' => 'Ukuran file dokumen pendukung maksimal 5MB.',
        ]);

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);
        $jumlahHari = $start->diffInDays($end) + 1;

        $docPath = null;
        if ($request->hasFile('dokumen') && $request->file('dokumen')->isValid()) {
            $folder = 'uploads/dokumen_cuti';
            $fullFolder = public_path($folder);
            if (!File::exists($fullFolder)) {
                File::makeDirectory($fullFolder, 0755, true, true);
            }
            $file = $request->file('dokumen');
            $filename = 'cuti_' . $validated['user_id'] . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($fullFolder, $filename);
            $docPath = $folder . '/' . $filename;
        }

        Leave::create([
            'user_id' => $validated['user_id'],
            'jenis_cuti' => $validated['jenis_cuti'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'jumlah_hari' => $jumlahHari,
            'alasan' => $validated['alasan'] ?? null,
            'dokumen_pendukung' => $docPath,
            'status' => $validated['status'],
            'catatan_operator' => $validated['catatan_operator'] ?? null,
        ]);

        return redirect()->route('operator.leaves.index')
            ->with('success', 'Data Cuti Pegawai berhasil dicatat!');
    }

    /**
     * Memperbarui rekaman data izin cuti pegawai.
     *
     * @param int $id ID izin cuti yang diperbarui
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveUpdate($id, Request $request)
    {
        $leave = Leave::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'jenis_cuti' => 'required|in:cuti_tahunan,cuti_sakit,cuti_luar_negeri,cuti_alasan_penting,cuti_lainnya',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,menunggu,ditolak',
            'dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'catatan_operator' => 'nullable|string|max:500',
        ]);

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);
        $jumlahHari = $start->diffInDays($end) + 1;

        $docPath = $leave->dokumen_pendukung;
        if ($request->hasFile('dokumen') && $request->file('dokumen')->isValid()) {
            $folder = 'uploads/dokumen_cuti';
            $fullFolder = public_path($folder);
            if (!File::exists($fullFolder)) {
                File::makeDirectory($fullFolder, 0755, true, true);
            }
            $file = $request->file('dokumen');
            $filename = 'cuti_' . $validated['user_id'] . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($fullFolder, $filename);
            $docPath = $folder . '/' . $filename;
        }

        $leave->update([
            'user_id' => $validated['user_id'],
            'jenis_cuti' => $validated['jenis_cuti'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'jumlah_hari' => $jumlahHari,
            'alasan' => $validated['alasan'] ?? null,
            'dokumen_pendukung' => $docPath,
            'status' => $validated['status'],
            'catatan_operator' => $validated['catatan_operator'] ?? null,
        ]);

        return redirect()->route('operator.leaves.index')
            ->with('success', 'Data Cuti Pegawai berhasil diperbarui!');
    }

    /**
     * Menghapus rekaman izin cuti serta berkas lampiran pendukungnya dari penyimpanan server.
     *
     * @param int $id ID izin cuti yang dihapus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveDestroy($id)
    {
        $leave = Leave::findOrFail($id);
        if ($leave->dokumen_pendukung && File::exists(public_path($leave->dokumen_pendukung))) {
            File::delete(public_path($leave->dokumen_pendukung));
        }
        $leave->delete();

        return redirect()->route('operator.leaves.index')
            ->with('success', 'Data Cuti berhasil dihapus.');
    }

    /**
     * Menyetujui (approve) permohonan izin cuti yang diajukan oleh pegawai.
     *
     * @param int $id ID izin cuti
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveApprove($id)
    {
        $leave = Leave::with('user')->findOrFail($id);
        $leave->update([
            'status' => 'disetujui',
            'catatan_operator' => null,
        ]);

        return back()->with('success', 'Pengajuan Cuti ' . Leave::getJenisCutiLabel($leave->jenis_cuti) . ' untuk ' . $leave->user->name . ' telah DISETUJUI.');
    }

    /**
     * Menolak (reject) permohonan izin cuti yang diajukan oleh pegawai disertai catatan alasan penolakan.
     *
     * @param int $id ID izin cuti
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveReject($id, Request $request)
    {
        $leave = Leave::with('user')->findOrFail($id);
        $reason = $request->get('catatan_operator', 'Pengajuan cuti ditolak operator');

        $leave->update([
            'status' => 'ditolak',
            'catatan_operator' => $reason,
        ]);

        return back()->with('success', 'Pengajuan Cuti untuk ' . $leave->user->name . ' telah DITOLAK.');
    }

    /**
     * Menampilkan daftar hari libur nasional dan cuti bersama (tanggal merah) per tahun.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function holidayIndex(Request $request)
    {
        $year = (int)$request->get('year', Carbon::now()->year);
        $search = $request->get('search');

        $query = Holiday::query()
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhere('tanggal', 'like', "%{$search}%");
            });
        }

        $holidays = $query->paginate(25)->withQueryString();

        // Count totals
        $totalHolidays = Holiday::whereYear('tanggal', $year)->count();
        $totalUpcoming = Holiday::whereYear('tanggal', $year)->whereDate('tanggal', '>=', Carbon::today())->count();

        // Available years for filter
        $availableYears = Holiday::selectRaw('YEAR(tanggal) as year')
            ->distinct()
            ->pluck('year')
            ->toArray();
        if (!in_array($year, $availableYears)) {
            $availableYears[] = $year;
        }
        if (!in_array(2026, $availableYears)) {
            $availableYears[] = 2026;
        }
        sort($availableYears);

        return view('operator.holidays.index', compact(
            'holidays',
            'year',
            'search',
            'totalHolidays',
            'totalUpcoming',
            'availableYears'
        ));
    }

    /**
     * Menyimpan data tanggal merah / hari libur baru ke dalam sistem.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function holidayStore(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date|unique:holidays,tanggal',
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string|max:255',
        ], [
            'tanggal.required' => 'Tanggal libur wajib diisi.',
            'tanggal.unique' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.',
            'nama.required' => 'Nama hari libur wajib diisi.',
        ]);

        $holiday = Holiday::create([
            'tanggal' => $validated['tanggal'],
            'nama' => $validated['nama'],
            'keterangan' => $validated['keterangan'] ?? 'Hari Libur Nasional',
            'is_libur_nasional' => true,
        ]);

        $year = Carbon::parse($holiday->tanggal)->year;

        return redirect()->route('operator.holidays.index', ['year' => $year])
            ->with('success', 'Hari libur "' . $holiday->nama . '" (' . Carbon::parse($holiday->tanggal)->translatedFormat('d F Y') . ') berhasil ditambahkan!');
    }

    /**
     * Memperbarui rincian data hari libur nasional atau cuti bersama.
     *
     * @param Request $request
     * @param int $id ID hari libur yang diperbarui
     * @return \Illuminate\Http\RedirectResponse
     */
    public function holidayUpdate(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'tanggal' => 'required|date|unique:holidays,tanggal,' . $holiday->id,
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string|max:255',
        ], [
            'tanggal.required' => 'Tanggal libur wajib diisi.',
            'tanggal.unique' => 'Tanggal tersebut sudah terdaftar pada hari libur lain.',
            'nama.required' => 'Nama hari libur wajib diisi.',
        ]);

        $holiday->update([
            'tanggal' => $validated['tanggal'],
            'nama' => $validated['nama'],
            'keterangan' => $validated['keterangan'] ?? 'Hari Libur Nasional',
        ]);

        $year = Carbon::parse($holiday->tanggal)->year;

        return redirect()->route('operator.holidays.index', ['year' => $year])
            ->with('success', 'Data hari libur "' . $holiday->nama . '" berhasil diperbarui!');
    }

    /**
     * Menghapus rekaman hari libur dari kalender sistem.
     *
     * @param int $id ID hari libur yang dihapus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function holidayDestroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $nama = $holiday->nama;
        $tgl = Carbon::parse($holiday->tanggal)->translatedFormat('d F Y');
        $year = Carbon::parse($holiday->tanggal)->year;

        $holiday->delete();

        return redirect()->route('operator.holidays.index', ['year' => $year])
            ->with('success', 'Hari libur "' . $nama . '" (' . $tgl . ') berhasil dihapus!');
    }

    /**
     * Mengisi secara otomatis daftar resmi Hari Libur Nasional & Cuti Bersama Republik Indonesia
     * untuk tahun kalender tertentu ke database (fitur 1-Klik Otomatisasi Kalender Libur).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function holidayGenerateNational(Request $request)
    {
        $year = (int)$request->get('year', 2026);
        $defaults = Holiday::getDefaultNationalHolidays($year);

        $insertedCount = 0;
        foreach ($defaults as $item) {
            $existing = Holiday::where('tanggal', $item['tanggal'])->first();
            if (!$existing) {
                Holiday::create([
                    'tanggal' => $item['tanggal'],
                    'nama' => $item['nama'],
                    'keterangan' => $item['keterangan'],
                    'is_libur_nasional' => true,
                ]);
                $insertedCount++;
            }
        }

        return redirect()->route('operator.holidays.index', ['year' => $year])
            ->with('success', "Berhasil memuat {$insertedCount} Hari Libur Nasional & Cuti Bersama untuk Tahun {$year}!");
    }

    /**
     * Menampilkan halaman profil & keamanan akun operator presensi.
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = auth()->user();
        return view('operator.profile', compact('user'));
    }

    /**
     * Memproses perubahan Nomor Identitas (NIP) dan nama lengkap operator sendiri.
     * Dilengkapi verifikasi keamanan kata sandi saat ini.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateNip(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'nip' => 'required|string|max:50|unique:users,nip,' . $user->id,
            'name' => 'required|string|max:255',
            'current_password' => 'required|string',
        ], [
            'nip.required' => 'Nomor Identitas (NIP) wajib diisi.',
            'nip.unique' => 'NIP tersebut sudah digunakan oleh akun lain.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'current_password.required' => 'Masukkan password Anda untuk konfirmasi perubahan NIP.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai. Konfirmasi gagal.'])->withInput();
        }

        $user->update([
            'nip' => $validated['nip'],
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Data Profil & NIP Operator berhasil diperbarui menjadi ' . $user->nip . '!');
    }

    /**
     * Memproses pembaruan kata sandi akun operator presensi secara mandiri.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password akun Operator Anda berhasil diperbarui!');
    }

    /**
     * Memproses pengunggahan / pembaruan foto profil (PP) operator presensi.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'foto.required' => 'Silakan pilih berkas foto terlebih dahulu.',
            'foto.image' => 'Berkas harus berupa gambar.',
            'foto.mimes' => 'Format foto harus berupa JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran berkas foto maksimal 2MB.',
        ]);

        $user = auth()->user();

        $folder = 'uploads/profiles';
        $fullFolder = public_path($folder);
        if (!File::exists($fullFolder)) {
            File::makeDirectory($fullFolder, 0755, true, true);
        }

        // Hapus foto lama jika ada
        if ($user->foto && File::exists(public_path($user->foto))) {
            File::delete(public_path($user->foto));
        }

        $file = $request->file('foto');
        $imgInfo = @getimagesize($file->getRealPath());
        if ($imgInfo === false || !in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            return back()->with('error', 'Berkas yang diunggah tidak valid atau bukan gambar asli.');
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }
        $filename = 'operator_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $ext;
        $file->move($fullFolder, $filename);

        $user->update([
            'foto' => $folder . '/' . $filename,
        ]);

        return back()->with('success', 'Foto profil Operator berhasil diperbarui!');
    }

    /**
     * Menghapus foto profil operator.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteFoto(Request $request)
    {
        $user = auth()->user();

        if ($user->foto && File::exists(public_path($user->foto))) {
            File::delete(public_path($user->foto));
        }

        $user->update([
            'foto' => null,
        ]);

        return back()->with('success', 'Foto profil berhasil dihapus.');
    }

    /**
     * Menampilkan antarmuka pengelolaan presensi: filter data, riwayat presensi harian, dan modal input/edit manual.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function attendanceManageIndex(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));
        $user_id = $request->get('user_id');
        $tipe = $request->get('tipe');
        $status = $request->get('status');
        $approval_status = $request->get('approval_status');
        $sumber = $request->get('sumber'); // 'semua', 'manual', 'karyawan'
        $search = $request->get('search');

        $query = Attendance::with('user');

        if ($tanggal) {
            $query->whereDate('tanggal', $tanggal);
        }

        if ($user_id) {
            $query->where('user_id', $user_id);
        }

        if ($tipe) {
            $query->where('tipe', $tipe);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($approval_status) {
            $query->where('approval_status', $approval_status);
        }

        if ($sumber === 'manual') {
            $query->where(function ($q) {
                $q->where('alamat', 'like', '%manual%')
                  ->orWhere('ip_address', 'like', '%manual%')
                  ->orWhere('foto', 'like', '%manual%');
            });
        } elseif ($sumber === 'karyawan') {
            $query->where(function ($q) {
                $q->where('alamat', 'not like', '%manual%')
                  ->where('ip_address', 'not like', '%manual%')
                  ->where('foto', 'not like', '%manual%');
            });
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Summary Counts untuk tanggal terpilih
        $baseCountQuery = Attendance::query();
        if ($tanggal) {
            $baseCountQuery->whereDate('tanggal', $tanggal);
        }
        $totalAll = (clone $baseCountQuery)->count();
        $totalTepatWaktu = (clone $baseCountQuery)->where('status', 'tepat_waktu')->count();
        $totalTerlambat = (clone $baseCountQuery)->where('status', 'terlambat')->count();
        $totalLebihAwal = (clone $baseCountQuery)->where('status', 'lebih_awal')->count();
        $totalIzin = (clone $baseCountQuery)->where('status', 'izin')->count();
        $totalSakit = (clone $baseCountQuery)->where('status', 'sakit')->count();
        $totalManual = (clone $baseCountQuery)->where(function ($q) {
            $q->where('alamat', 'like', '%manual%')
              ->orWhere('ip_address', 'like', '%manual%')
              ->orWhere('foto', 'like', '%manual%');
        })->count();

        $attendances = $query->orderBy('tanggal', 'desc')->orderBy('waktu', 'desc')->paginate(20)->withQueryString();
        $employees = User::where('role', 'karyawan')->orderBy('name', 'asc')->get();
        $setting = Setting::getOfficeSetting();
        $selectedDate = $tanggal ?: Carbon::today()->format('Y-m-d');
        $schedule = Schedule::getScheduleForDate($selectedDate);

        // Data Cuti / Izin Pegawai yang disetujui pada tanggal terpilih
        $leavesToday = Leave::with('user')
            ->whereDate('tanggal_mulai', '<=', $selectedDate)
            ->whereDate('tanggal_selesai', '>=', $selectedDate)
            ->where('status', 'disetujui')
            ->get();
        $totalLeavesToday = $leavesToday->count();

        return view('operator.attendances.index', compact(
            'attendances',
            'employees',
            'setting',
            'schedule',
            'leavesToday',
            'totalLeavesToday',
            'tanggal',
            'user_id',
            'tipe',
            'status',
            'approval_status',
            'sumber',
            'search',
            'totalAll',
            'totalTepatWaktu',
            'totalTerlambat',
            'totalLebihAwal',
            'totalIzin',
            'totalSakit',
            'totalManual'
        ));
    }

    /**
     * Menyimpan input presensi manual oleh operator (Mendukung kombinasi status per sesi: tepat waktu, terlambat, izin, sakit).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function attendanceManualStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'sessions' => 'required|array|min:1',
            'sessions.*' => 'in:masuk,istirahat,masuk_istirahat,pulang',
            'catatan_operator' => 'nullable|string|max:500',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'user_id.required' => 'Pegawai wajib dipilih.',
            'tanggal.required' => 'Tanggal presensi wajib diisi.',
            'sessions.required' => 'Pilih minimal satu sesi presensi yang ingin disimpan.',
            'foto.max' => 'Ukuran berkas foto maksimal 5MB.',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $tanggal = $validated['tanggal'];
        $schedule = Schedule::getScheduleForDate($tanggal);
        $setting = Setting::getOfficeSetting();
        $lat = $setting->latitude_kantor ?: -0.026330;
        $lng = $setting->longitude_kantor ?: 109.342500;
        $officeName = $setting->nama_kantor ?: 'Kantor PT Pontianak';
        $alamat = 'Input Manual oleh Operator (' . $officeName . ')';
        $catatan = $validated['catatan_operator'] ?: 'Input manual oleh operator';

        // Upload foto jika ada
        $fotoPath = 'images/manual_attendance.png';
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $folder = 'uploads/absensi';
            $fullFolder = public_path($folder);
            if (!File::exists($fullFolder)) {
                File::makeDirectory($fullFolder, 0755, true, true);
            }
            $file = $request->file('foto');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = 'manual_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($fullFolder, $filename);
            $fotoPath = $folder . '/' . $filename;
        }

        $createdCount = 0;
        $updatedCount = 0;
        $statusesUsed = [];

        foreach ($validated['sessions'] as $tipe) {
            $jamInput = $request->input("jam_{$tipe}");
            if (empty($jamInput)) {
                $jamInput = match ($tipe) {
                    'masuk' => substr($schedule->jam_masuk, 0, 5),
                    'istirahat' => substr($schedule->jam_istirahat, 0, 5),
                    'masuk_istirahat' => substr($schedule->jam_masuk_istirahat, 0, 5),
                    'pulang' => substr($schedule->jam_pulang, 0, 5),
                    default => '08:00',
                };
            }

            $waktu = Carbon::parse($tanggal . ' ' . $jamInput);

            // Ambil status sesi: tepat_waktu, terlambat, lebih_awal, izin, sakit
            $statusInput = $request->input("status_{$tipe}", 'tepat_waktu');
            if (!in_array($statusInput, ['tepat_waktu', 'terlambat', 'lebih_awal', 'izin', 'sakit'])) {
                $statusInput = 'tepat_waktu';
            }
            $statusesUsed[] = $statusInput;

            $existing = Attendance::where('user_id', $user->id)
                ->where('tanggal', $tanggal)
                ->where('tipe', $tipe)
                ->first();

            if ($existing) {
                $updateData = [
                    'waktu' => $waktu,
                    'status' => $statusInput,
                    'approval_status' => 'diterima',
                    'catatan_operator' => $catatan,
                    'alamat' => $alamat,
                    'ip_address' => 'Manual (Operator)',
                ];
                if ($fotoPath !== 'images/manual_attendance.png') {
                    $updateData['foto'] = $fotoPath;
                }
                $existing->update($updateData);
                $updatedCount++;
            } else {
                Attendance::create([
                    'user_id' => $user->id,
                    'tanggal' => $tanggal,
                    'tipe' => $tipe,
                    'waktu' => $waktu,
                    'foto' => $fotoPath,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'alamat' => $alamat,
                    'ip_address' => 'Manual (Operator)',
                    'status' => $statusInput,
                    'approval_status' => 'diterima',
                    'catatan_operator' => $catatan,
                ]);
                $createdCount++;
            }
        }

        // Sinkronisasi otomatis ke Leave jika seluruh sesi yang dicatat adalah izin atau sakit
        $uniqueStatuses = array_unique($statusesUsed);
        if (count($uniqueStatuses) === 1) {
            $singleStatus = $uniqueStatuses[0];
            if ($singleStatus === 'sakit' || $singleStatus === 'izin') {
                $jenisCuti = ($singleStatus === 'sakit') ? 'cuti_sakit' : 'cuti_alasan_penting';
                $existingLeave = Leave::where('user_id', $user->id)
                    ->whereDate('tanggal_mulai', '<=', $tanggal)
                    ->whereDate('tanggal_selesai', '>=', $tanggal)
                    ->first();

                if ($existingLeave) {
                    $existingLeave->update([
                        'jenis_cuti' => $jenisCuti,
                        'status' => 'disetujui',
                        'catatan_operator' => $catatan,
                    ]);
                } else {
                    Leave::create([
                        'user_id' => $user->id,
                        'jenis_cuti' => $jenisCuti,
                        'tanggal_mulai' => $tanggal,
                        'tanggal_selesai' => $tanggal,
                        'jumlah_hari' => 1,
                        'alasan' => ($singleStatus === 'sakit') ? 'Sakit' : 'Izin',
                        'status' => 'disetujui',
                        'catatan_operator' => $catatan,
                    ]);
                }
            }
        }

        $summary = [];
        if ($createdCount > 0) $summary[] = "{$createdCount} sesi baru";
        if ($updatedCount > 0) $summary[] = "{$updatedCount} sesi diperbarui";

        return redirect()->route('operator.attendances.index', ['tanggal' => $tanggal])
            ->with('success', 'Presensi pegawai ' . $user->name . ' tanggal ' . Carbon::parse($tanggal)->format('d/m/Y') . ' berhasil disimpan (' . implode(', ', $summary) . ')!');
    }

    /**
     * Mengambil rincian data presensi dalam format JSON untuk kebutuhan modal edit operator.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function attendanceShowJson($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'user_name' => $attendance->user->name ?? 'N/A',
                'user_nip' => $attendance->user->nip ?? '',
                'user_identitas' => $attendance->user->identitas_lengkap ?? '',
                'user_jabatan' => $attendance->user->jabatan ?? '',
                'tanggal' => Carbon::parse($attendance->tanggal)->format('Y-m-d'),
                'tanggal_formatted' => Carbon::parse($attendance->tanggal)->translatedFormat('d F Y'),
                'jam' => Carbon::parse($attendance->waktu)->format('H:i'),
                'tipe' => $attendance->tipe,
                'tipe_label' => Attendance::getTipeLabel($attendance->tipe),
                'status' => $attendance->status,
                'approval_status' => $attendance->approval_status,
                'catatan_operator' => $attendance->catatan_operator ?? '',
                'foto_url' => $attendance->foto_url,
                'alamat' => $attendance->alamat ?? '',
                'ip_address' => $attendance->ip_address ?? '',
                'is_manual' => $attendance->isManual(),
            ]
        ]);
    }

    /**
     * Memperbarui rekaman data presensi pegawai (Edit Presensi oleh Operator).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function attendanceUpdate(Request $request, $id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe' => 'required|in:masuk,istirahat,masuk_istirahat,pulang',
            'jam' => 'required',
            'status' => 'required|in:tepat_waktu,terlambat,lebih_awal,izin,sakit',
            'approval_status' => 'required|in:diterima,ditolak',
            'catatan_operator' => 'nullable|string|max:500',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'tanggal.required' => 'Tanggal presensi wajib diisi.',
            'tipe.required' => 'Tipe sesi presensi wajib dipilih.',
            'jam.required' => 'Jam presensi wajib diisi.',
            'status.required' => 'Status ketepatan waktu wajib dipilih.',
            'approval_status.required' => 'Status approval wajib dipilih.',
            'foto.max' => 'Ukuran berkas foto maksimal 5MB.',
        ]);

        // Cek duplikasi jika tanggal atau sesi diubah bentrok dengan rekaman presensi lain milik pegawai
        $duplicate = Attendance::where('user_id', $attendance->user_id)
            ->where('tanggal', $validated['tanggal'])
            ->where('tipe', $validated['tipe'])
            ->where('id', '!=', $id)
            ->first();

        if ($duplicate) {
            return back()->with('error', 'Gagal memperbarui! Sudah ada rekaman presensi sesi ' . Attendance::getTipeLabel($validated['tipe']) . ' untuk pegawai ' . $attendance->user->name . ' pada tanggal ' . $validated['tanggal'] . ' (ID: #' . $duplicate->id . ').');
        }

        $waktu = Carbon::parse($validated['tanggal'] . ' ' . $validated['jam']);

        $updateData = [
            'tanggal' => $validated['tanggal'],
            'tipe' => $validated['tipe'],
            'waktu' => $waktu,
            'status' => $validated['status'],
            'approval_status' => $validated['approval_status'],
            'catatan_operator' => $validated['catatan_operator'] ?? null,
        ];

        // Jika operator mengunggah foto bukti baru
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $folder = 'uploads/absensi';
            $fullFolder = public_path($folder);
            if (!File::exists($fullFolder)) {
                File::makeDirectory($fullFolder, 0755, true, true);
            }
            $file = $request->file('foto');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = 'edited_' . $attendance->user_id . '_' . $validated['tipe'] . '_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($fullFolder, $filename);
            $updateData['foto'] = $folder . '/' . $filename;
        }

        $attendance->update($updateData);

        return redirect()->route('operator.attendances.index', ['tanggal' => $validated['tanggal']])
            ->with('success', 'Data presensi ' . Attendance::getTipeLabel($validated['tipe']) . ' untuk pegawai ' . $attendance->user->name . ' berhasil diperbarui!');
    }

    /**
     * Menghapus rekaman data presensi pegawai oleh operator.
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function attendanceDestroy($id, Request $request)
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        $userName = $attendance->user->name ?? 'Pegawai';
        $tipeLabel = Attendance::getTipeLabel($attendance->tipe);
        $tanggal = Carbon::parse($attendance->tanggal)->format('d/m/Y');

        // Jika foto rekaman bukan gambar default sistem, hapus berkasnya
        if ($attendance->foto && !str_contains($attendance->foto, 'manual_attendance.png') && File::exists(public_path($attendance->foto))) {
            @File::delete(public_path($attendance->foto));
        }

        $attendance->delete();

        $msg = 'Presensi ' . $tipeLabel . ' (' . $tanggal . ') milik ' . $userName . ' berhasil dihapus dari sistem.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}

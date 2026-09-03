<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Layanan Ekspor Rekapitulasi Presensi ke Format Excel Resmi (Book1.xlsx)
 *
 * Mengonstruksi berkas spreadsheet Excel (.xlsx) resmi Pengadilan Tinggi Pontianak:
 * 1. Mengumpulkan data statistik kehadiran per pegawai (4 sesi presensi harian).
 * 2. Menghitung metrik ketepatan waktu: Tepat Waktu, Terlambat, Lebih Awal, Ditolak (ALFA), dan Tanpa Keterangan.
 * 3. Menghitung hari izin cuti yang telah disetujui (cuti tahunan, sakit, luar negeri, dll.).
 * 4. Menyusun tata letak lembar kerja: Judul, Satker, Bulan, Tabel Berkisi Hijau, Keterangan Legenda, dan Tanda Tangan Pimpinan.
 */
class AttendanceExportService
{
    /**
     * Menghasilkan berkas unduhan spreadsheet Excel rekapitulasi presensi.
     *
     * @param string $tanggal_mulai Batas awal periode tanggal (format Y-m-d)
     * @param string $tanggal_selesai Batas akhir periode tanggal (format Y-m-d)
     * @param int|null $user_id ID pegawai spesifik (jika difilter 1 orang) atau null untuk seluruh pegawai
     * @return BinaryFileResponse Aliran respons berkas Excel untuk diunduh oleh peramban pengguna
     */
    public function exportBook1Format(string $tanggal_mulai, string $tanggal_selesai, ?int $user_id = null): BinaryFileResponse
    {
        Carbon::setLocale('id');
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $start = Carbon::parse($tanggal_mulai)->startOfDay();
        $end = Carbon::parse($tanggal_selesai)->endOfDay();
        $today = Carbon::today();

        // 1. Mengambil daftar pegawai yang akan disertakan dalam laporan
        if ($user_id) {
            $employees = User::where('id', $user_id)->get();
        } else {
            $employees = User::where('role', 'karyawan')->orderBy('name', 'asc')->get();
            if ($employees->isEmpty()) {
                $employees = User::orderBy('name', 'asc')->get();
            }
        }

        // 2. Membuat deretan tanggal harian dalam rentang periode
        $period = CarbonPeriod::create($start, $end);

        // Pre-cache jadwal kerja untuk seluruh tanggal dalam periode (menghindari ribuan query berulang)
        $schedulesByDate = [];
        foreach ($period as $date) {
            $dStr = $date->format('Y-m-d');
            $schedulesByDate[$dStr] = Schedule::getScheduleForDate($dStr);
        }

        // Preload seluruh presensi dan izin pegawai dalam rentang tanggal sekaligus (sangat cepat & ramah resource server cPanel)
        $empIds = $employees->pluck('id')->toArray();
        $startDateStr = $start->format('Y-m-d');
        $endDateStr = $end->format('Y-m-d');

        $attendancesGrouped = Attendance::whereIn('user_id', $empIds)
            ->whereBetween('tanggal', [$startDateStr, $endDateStr])
            ->get()
            ->groupBy(function ($att) {
                return $att->user_id . '_' . Carbon::parse($att->tanggal)->format('Y-m-d');
            });

        $leavesGrouped = Leave::whereIn('user_id', $empIds)
            ->where('status', 'disetujui')
            ->whereDate('tanggal_mulai', '<=', $endDateStr)
            ->whereDate('tanggal_selesai', '>=', $startDateStr)
            ->get()
            ->groupBy('user_id');

        // 3. Menghitung akumulasi statistik kehadiran per pegawai
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

            $userLeaves = $leavesGrouped->get($emp->id, collect());

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                // Don't count absences for future dates
                if ($date->gt($today)) {
                    continue;
                }

                // Check schedule for day (libur/weekend)
                $schedule = $schedulesByDate[$dateStr] ?? Schedule::getScheduleForDate($dateStr);
                if ($schedule->is_libur) {
                    continue;
                }

                // Check if user is on approved leave on this working day
                $approvedLeave = $userLeaves->first(function ($l) use ($dateStr) {
                    $mulai = Carbon::parse($l->tanggal_mulai)->format('Y-m-d');
                    $selesai = Carbon::parse($l->tanggal_selesai)->format('Y-m-d');
                    return $mulai <= $dateStr && $selesai >= $dateStr;
                });

                // Fetch attendances on that day for employee from preloaded collection
                $allDayAttendances = $attendancesGrouped->get($emp->id . '_' . $dateStr, collect());

                $approvedAttendances = $allDayAttendances->where('approval_status', 'diterima');
                $rejectedAttendances = $allDayAttendances->where('approval_status', 'ditolak');

                if ($approvedLeave) {
                    // Pegawai memiliki izin/cuti resmi yang disetujui (tidak dianggap ALFA)
                    if ($approvedLeave->jenis_cuti === 'cuti_tahunan') $countCutiTahunan++;
                    elseif ($approvedLeave->jenis_cuti === 'cuti_sakit') $countCutiSakit++;
                    elseif ($approvedLeave->jenis_cuti === 'cuti_luar_negeri') $countCutiLN++;
                    else $countCutiLainnya++;
                }

                if ($allDayAttendances->isEmpty()) {
                    if (!$approvedLeave) {
                        // Tidak hadir sama sekali dan tidak ada izin cuti di hari kerja
                        $countTanpaKeterangan++;
                    }
                } else {
                    // Count approved sessions
                    if ($approvedAttendances->firstWhere('tipe', 'masuk')) $countMasuk++;
                    if ($approvedAttendances->firstWhere('tipe', 'istirahat')) $countIstirahat++;
                    if ($approvedAttendances->firstWhere('tipe', 'masuk_istirahat')) $countMasukIstirahat++;
                    if ($approvedAttendances->firstWhere('tipe', 'pulang')) $countPulang++;

                    // Count statuses from approved attendances
                    foreach ($approvedAttendances as $att) {
                        if ($att->status === 'tepat_waktu') {
                            $countTepatWaktu++;
                        } elseif ($att->status === 'terlambat') {
                            $countTerlambat++;
                        } elseif ($att->status === 'lebih_awal') {
                            $countLebihAwal++;
                        } elseif ($att->status === 'sakit') {
                            $countCutiSakit++;
                        } elseif ($att->status === 'izin') {
                            $countCutiLainnya++;
                        }
                    }

                    // Count rejected attendances (ALFA)
                    $countDitolak += $rejectedAttendances->count();
                }
            }

            // Build leave summary string for Keterangan column
            $keteranganParts = [];
            if ($countCutiTahunan > 0) $keteranganParts[] = "Cuti Tahunan: {$countCutiTahunan} hr";
            if ($countCutiSakit > 0) $keteranganParts[] = "Cuti Sakit: {$countCutiSakit} hr";
            if ($countCutiLN > 0) $keteranganParts[] = "Cuti LN: {$countCutiLN} hr";
            if ($countCutiLainnya > 0) $keteranganParts[] = "Cuti Lainnya: {$countCutiLainnya} hr";
            $keteranganText = implode(', ', $keteranganParts);

            $employeeStats[] = [
                'user' => $emp,
                'masuk' => $countMasuk,
                'istirahat' => $countIstirahat,
                'masuk_istirahat' => $countMasukIstirahat,
                'pulang' => $countPulang,
                'tepat_waktu' => $countTepatWaktu,
                'terlambat' => $countTerlambat,
                'lebih_awal' => $countLebihAwal,
                'ditolak' => $countDitolak,
                'tanpa_keterangan' => $countTanpaKeterangan,
                'keterangan' => $keteranganText,
            ];
        }

        // Build Excel Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Presensi');

        // Page setup (Landscape for clear multi-column visibility)
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        // Default Font
        $spreadsheet->getDefaultStyle()->getFont()->setName('Aptos Narrow')->setSize(11);

        // Row 1: Title
        $sheet->mergeCells('A1:N1');
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI PRESENSI PEGAWAI');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Row 2: Blank row
        $sheet->mergeCells('A2:N2');
        $sheet->getRowDimension(2)->setRowHeight(12);

        // Row 3: Blank
        $sheet->getRowDimension(3)->setRowHeight(10);

        // Row 4: Satker & Bulan
        $setting = Setting::getOfficeSetting();
        $satkerName = $setting->satker_name ?: 'PENGADILAN TINGGI PONTIANAK';
        $kotaSurat = $setting->kota_surat ?: 'Pontianak';
        $jabatanKetua = $setting->jabatan_ketua ?: 'Ketua Pengadilan Tinggi Pontianak';
        $namaKetua = $setting->nama_ketua ?: 'Isnurul Syamsyul Arif';
        $nipKetua = $setting->nip_ketua;

        $sheet->setCellValue('A4', 'SATKER : ' . strtoupper($satkerName));
        $sheet->getStyle('A4')->getFont()->setSize(11)->setBold(true);
        $sheet->getStyle('A4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Month text calculation
        $startMonth = Carbon::parse($tanggal_mulai)->locale('id')->isoFormat('MMMM Y');
        $endMonth = Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('MMMM Y');
        $bulanText = ($startMonth === $endMonth) ? strtoupper($startMonth) : strtoupper($startMonth . ' - ' . $endMonth);

        $sheet->mergeCells('L4:N4');
        $sheet->setCellValue('L4', 'BULAN : ' . $bulanText);
        $sheet->getStyle('L4')->getFont()->setSize(11)->setBold(true);
        $sheet->getStyle('L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('L4:N4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(4)->setRowHeight(18);

        // Header Table (Rows 5 & 6)
        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('A5', 'NO.');

        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('B5', "NAMA / NIP");

        $sheet->mergeCells('C5:C6');
        $sheet->setCellValue('C5', 'JABATAN');

        $sheet->mergeCells('D5:D6');
        $sheet->setCellValue('D5', 'SATUAN KERJA');

        // Sesi Presensi Group (Col E - H)
        $sheet->mergeCells('E5:H5');
        $sheet->setCellValue('E5', 'REKAPITULASI SESI PRESENSI');

        // Status & Evaluasi Group (Col I - M)
        $sheet->mergeCells('I5:M5');
        $sheet->setCellValue('I5', 'STATUS & EVALUASI PRESENSI');

        // Keterangan (Col N)
        $sheet->mergeCells('N5:N6');
        $sheet->setCellValue('N5', 'KETERANGAN');

        // Subheaders Row 6
        $sheet->setCellValue('E6', 'Masuk');
        $sheet->setCellValue('F6', 'Istirahat');
        $sheet->setCellValue('G6', 'Masuk Ist.');
        $sheet->setCellValue('H6', 'Pulang');

        $sheet->setCellValue('I6', 'Tepat Waktu');
        $sheet->setCellValue('J6', 'Terlambat');
        $sheet->setCellValue('K6', 'Lebih Awal');
        $sheet->setCellValue('L6', 'Ditolak');
        $sheet->setCellValue('M6', 'Tanpa Ket.');

        // Header Styling
        $headerStyle = [
            'font' => [
                'name' => 'Aptos Narrow',
                'size' => 10,
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF92D050'], // Light Green matching Book1.xlsx
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $sheet->getStyle('A5:N6')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(22);
        $sheet->getRowDimension(6)->setRowHeight(22);

        // Data Rows
        $currRow = 7;
        $no = 1;

        foreach ($employeeStats as $stat) {
            $emp = $stat['user'];

            $sheet->setCellValue("A{$currRow}", $no++);

            $nameNip = $emp->name;
            if (!empty($emp->nip)) {
                $prefix = $emp->tipe_identitas_label ?? 'NIP';
                $nameNip .= "\n{$prefix}. " . $emp->nip;
            }
            if (!empty($emp->asal_instansi)) {
                $nameNip .= "\n(" . $emp->asal_instansi . ")";
            }
            $sheet->setCellValue("B{$currRow}", $nameNip);
            $sheet->setCellValue("C{$currRow}", $emp->jabatan ?? 'Pegawai');
            $sheet->setCellValue("D{$currRow}", 'PT. Pontianak');

            // Sesi values (Leave blank if 0 for clean look)
            $sheet->setCellValue("E{$currRow}", $stat['masuk'] > 0 ? $stat['masuk'] : '');
            $sheet->setCellValue("F{$currRow}", $stat['istirahat'] > 0 ? $stat['istirahat'] : '');
            $sheet->setCellValue("G{$currRow}", $stat['masuk_istirahat'] > 0 ? $stat['masuk_istirahat'] : '');
            $sheet->setCellValue("H{$currRow}", $stat['pulang'] > 0 ? $stat['pulang'] : '');

            // Status values (Leave blank if 0)
            $sheet->setCellValue("I{$currRow}", $stat['tepat_waktu'] > 0 ? $stat['tepat_waktu'] : '');
            $sheet->setCellValue("J{$currRow}", $stat['terlambat'] > 0 ? $stat['terlambat'] : '');
            $sheet->setCellValue("K{$currRow}", $stat['lebih_awal'] > 0 ? $stat['lebih_awal'] : '');
            $sheet->setCellValue("L{$currRow}", $stat['ditolak'] > 0 ? $stat['ditolak'] : '');
            $sheet->setCellValue("M{$currRow}", $stat['tanpa_keterangan'] > 0 ? $stat['tanpa_keterangan'] : '');
            $sheet->setCellValue("N{$currRow}", $stat['keterangan'] ?? '');

            // Row Styling
            $sheet->getStyle("A{$currRow}:N{$currRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D{$currRow}:N{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getRowDimension($currRow)->setRowHeight(28);
            $currRow++;
        }

        // Spacing row before legend & signature
        $currRow++;

        // Footer / Signature Section
        $sigDateRow = $currRow;
        $sheet->mergeCells("L{$sigDateRow}:N{$sigDateRow}");
        $signDate = $kotaSurat . ', ' . Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('D MMMM Y');
        $sheet->setCellValue("L{$sigDateRow}", $signDate);
        $sheet->getStyle("L{$sigDateRow}")->getFont()->setSize(11)->setBold(true);
        $sheet->getStyle("L{$sigDateRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($sigDateRow)->setRowHeight(16);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "KETERANGAN SIMBOL / STATUS:");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(10)->setBold(true);
        $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells("L{$currRow}:N{$currRow}");
        $tampilkanMengetahui = (bool) $setting->tampilkan_mengetahui;
        if ($tampilkanMengetahui) {
            $sheet->setCellValue("L{$currRow}", "Mengetahui,\n" . $jabatanKetua . ",");
            $sheet->getStyle("L{$currRow}")->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($currRow)->setRowHeight(28);
        } else {
            $sheet->setCellValue("L{$currRow}", $jabatanKetua . ",");
            $sheet->getRowDimension($currRow)->setRowHeight(16);
        }
        $sheet->getStyle("L{$currRow}")->getFont()->setSize(11)->setBold(true);
        $sheet->getStyle("L{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Masuk = Jam Masuk (Sesi Pagi)");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Istirahat = Jam Istirahat (Keluar Siang)");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Masuk Ist. = Jam Masuk Kembali dari Istirahat");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Pulang = Jam Pulang (Selesai Kerja)");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Tepat Waktu = Presensi Masuk Tepat Waktu");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Terlambat = Presensi Masuk / Masuk Istirahat Terlambat");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);

        // Signer name on right
        $sheet->mergeCells("L{$currRow}:N{$currRow}");
        $signerText = $namaKetua;
        if (!empty($nipKetua)) {
            $signerText .= "\nNIP. " . $nipKetua;
            $sheet->getStyle("L{$currRow}")->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($currRow)->setRowHeight(28);
        } else {
            $sheet->getRowDimension($currRow)->setRowHeight(16);
        }
        $sheet->setCellValue("L{$currRow}", $signerText);
        $sheet->getStyle("L{$currRow}")->getFont()->setSize(11)->setBold(true);
        $sheet->getStyle("L{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Lebih Awal = Presensi Pulang / Istirahat Sebelum Waktunya");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Ditolak = Presensi Ditolak Operator (Foto Tidak Sesuai/Invalid)");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        $currRow++;
        $sheet->setCellValue("A{$currRow}", "Tanpa Ket. = Hari Kerja Tanpa Rekaman Presensi (ALFA)");
        $sheet->getStyle("A{$currRow}")->getFont()->setSize(9);
        $sheet->getRowDimension($currRow)->setRowHeight(15);

        // Column Widths for a clean layout
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(13);
        $sheet->getColumnDimension('J')->setWidth(11);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(10);
        $sheet->getColumnDimension('M')->setWidth(12);
        $sheet->getColumnDimension('N')->setWidth(16);

        // File output name
        $fileName = 'Laporan_Rekap_Presensi_PPTK_' . str_replace('-', '', $tanggal_mulai) . '_sd_' . str_replace('-', '', $tanggal_selesai) . '.xlsx';

        // Pastikan folder temp storage siap dan aman dari buffer/interupsi web server hosting
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }
        if (!is_writable($tempDir)) {
            $tempDir = sys_get_temp_dir();
        }

        $tempFilePath = rtrim($tempDir, '/\\') . DIRECTORY_SEPARATOR . 'rekap_' . uniqid('', true) . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);

        return response()->download($tempFilePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }
}

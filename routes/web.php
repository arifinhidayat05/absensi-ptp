<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\KaryawanController;

/*
|--------------------------------------------------------------------------
| RUTE APLIKASI PRESENSI PENGADILAN TINGGI PONTIANAK
|--------------------------------------------------------------------------
| Seluruh rute aplikasi didefinisikan di sini.
| Rute dikelompokkan berdasarkan hak akses pengguna:
| 1. Autentikasi Publik (Login & Logout)
| 2. Portal Operator Presensi (Middleware: auth & operator)
| 3. Portal Pegawai & Peserta Magang (Middleware: auth & karyawan)
*/

// =========================================================================
// 1. RUTE AUTENTIKASI (LOGIN & LOGOUT)
// =========================================================================
Route::get('/', [AuthController::class, 'showLogin'])->name('login'); // Menampilkan halaman form login
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login_attempts')->name('login.post'); // Memproses verifikasi login pengguna (dengan anti brute-force)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); // Mengakhiri sesi pengguna (keluar sistem)

// =========================================================================
// 2. RUTE PORTAL OPERATOR PRESENSI
// =========================================================================
Route::middleware(['auth', 'operator'])->prefix('operator')->name('operator.')->group(function () {

    // Dashboard Utama Operator (Statistik kehadiran harian, grafik, & presensi terbaru)
    Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');

    // Manajemen Jam Kerja Mingguan & Jadwal Khusus
    Route::get('/schedules', [OperatorController::class, 'scheduleIndex'])->name('schedules.index'); // Tampilan tabel jam kerja
    Route::post('/schedules', [OperatorController::class, 'scheduleStore'])->name('schedules.store'); // Simpan / perbarui jam kerja

    // Manajemen Data Pegawai & Peserta Magang (CRUD Pengguna)
    Route::get('/employees', [OperatorController::class, 'employeeIndex'])->name('employees.index'); // Daftar seluruh pegawai
    Route::post('/employees', [OperatorController::class, 'employeeStore'])->name('employees.store'); // Tambah akun pegawai baru
    Route::put('/employees/{id}', [OperatorController::class, 'employeeUpdate'])->name('employees.update'); // Ubah data profil pegawai
    Route::post('/employees/{id}/reset-password', [OperatorController::class, 'employeeResetPassword'])->name('employees.reset-password'); // Reset kata sandi ke default
    Route::delete('/employees/{id}', [OperatorController::class, 'employeeDestroy'])->name('employees.destroy'); // Hapus akun pegawai
    Route::get('/employees/{id}/download-photo', [OperatorController::class, 'employeeDownloadPhoto'])->name('employees.download-photo'); // Unduh berkas foto profil pegawai
    Route::get('/employees/template-excel', [OperatorController::class, 'employeeTemplateExcel'])->name('employees.template-excel'); // Unduh template berkas Excel import pegawai
    Route::post('/employees/import-excel', [OperatorController::class, 'employeeImportExcel'])->middleware('throttle:file_upload')->name('employees.import-excel'); // Import data pegawai dari berkas Excel

    // Rekapitulasi Laporan, Cetak PDF/A4, dan Unduh Berkas Excel Resmi
    Route::get('/reports', [OperatorController::class, 'attendanceReports'])->name('reports.index'); // Tampilan rekapitulasi data filter
    Route::get('/reports/export', [OperatorController::class, 'exportReports'])->middleware('throttle:reports_export')->name('reports.export'); // Ekspor ke Excel (Book1.xlsx)
    Route::get('/reports/print', [OperatorController::class, 'printReports'])->middleware('throttle:reports_export')->name('reports.print'); // Cetak resmi A4 (Semua / Timesheet 1 Orang)

    // Verifikasi & Persetujuan Bukti Presensi Pegawai
    Route::post('/attendances/{id}/reject', [OperatorController::class, 'rejectAttendance'])->name('attendances.reject'); // Tolak presensi (foto invalid/ALFA)
    Route::post('/attendances/{id}/approve', [OperatorController::class, 'approveAttendance'])->name('attendances.approve'); // Setujui kembali presensi

    // Manajemen Input & Edit Presensi Pegawai (Manual Entry Operator)
    Route::get('/attendances', [OperatorController::class, 'attendanceManageIndex'])->name('attendances.index'); // Tampilan daftar presensi, filter, dan form input/edit
    Route::post('/attendances/manual', [OperatorController::class, 'attendanceManualStore'])->name('attendances.manual-store'); // Simpan input presensi manual
    Route::get('/attendances/{id}/json', [OperatorController::class, 'attendanceShowJson'])->name('attendances.show-json'); // Ambil data presensi (JSON) untuk modal edit
    Route::put('/attendances/{id}', [OperatorController::class, 'attendanceUpdate'])->name('attendances.update'); // Simpan perubahan edit presensi
    Route::delete('/attendances/{id}', [OperatorController::class, 'attendanceDestroy'])->name('attendances.destroy'); // Hapus data presensi

    // Pengaturan Instansi & Radius Geofencing GPS Kantor
    Route::get('/location', [OperatorController::class, 'locationSettingsIndex'])->name('location.index'); // Form setting GPS kantor
    Route::post('/location', [OperatorController::class, 'locationSettingsUpdate'])->name('location.update'); // Simpan koordinat & pejabat penandatangan

    // Manajemen & Persetujuan Izin Cuti Pegawai
    Route::get('/leaves', [OperatorController::class, 'leaveIndex'])->name('leaves.index'); // Tampilan daftar pengajuan cuti
    Route::post('/leaves', [OperatorController::class, 'leaveStore'])->name('leaves.store'); // Operator mencatat cuti pegawai
    Route::put('/leaves/{id}', [OperatorController::class, 'leaveUpdate'])->name('leaves.update'); // Perbarui rincian cuti
    Route::delete('/leaves/{id}', [OperatorController::class, 'leaveDestroy'])->name('leaves.destroy'); // Hapus data cuti
    Route::post('/leaves/{id}/approve', [OperatorController::class, 'leaveApprove'])->name('leaves.approve'); // Setujui izin cuti
    Route::post('/leaves/{id}/reject', [OperatorController::class, 'leaveReject'])->name('leaves.reject'); // Tolak izin cuti

    // Manajemen Daftar Hari Libur Nasional & Cuti Bersama (Tanggal Merah)
    Route::get('/holidays', [OperatorController::class, 'holidayIndex'])->name('holidays.index'); // Tampilan kalender hari libur
    Route::post('/holidays', [OperatorController::class, 'holidayStore'])->name('holidays.store'); // Tambah tanggal merah baru
    Route::put('/holidays/{id}', [OperatorController::class, 'holidayUpdate'])->name('holidays.update'); // Perbarui data libur
    Route::delete('/holidays/{id}', [OperatorController::class, 'holidayDestroy'])->name('holidays.destroy'); // Hapus tanggal merah
    Route::post('/holidays/generate-national', [OperatorController::class, 'holidayGenerateNational'])->name('holidays.generateNational'); // Otomatis generate libur nasional 2026

    // Pengaturan Akun Operator Sendiri (Ubah NIP, Password, & Foto Profil)
    Route::get('/profile', [OperatorController::class, 'profile'])->name('profile'); // Tampilan profil operator
    Route::post('/profile/nip', [OperatorController::class, 'updateNip'])->name('profile.nip'); // Simpan perubahan NIP operator
    Route::post('/profile/password', [OperatorController::class, 'updatePassword'])->name('profile.password'); // Simpan kata sandi baru operator
    Route::post('/profile/foto', [OperatorController::class, 'updateFoto'])->middleware('throttle:file_upload')->name('profile.foto'); // Simpan foto profil operator
    Route::delete('/profile/foto', [OperatorController::class, 'deleteFoto'])->name('profile.foto.delete'); // Hapus foto profil operator
});

// =========================================================================
// 3. RUTE PORTAL PEGAWAI & PESERTA MAGANG
// =========================================================================
Route::middleware(['auth', 'karyawan'])->prefix('karyawan')->name('karyawan.')->group(function () {

    // Dashboard Utama Pegawai (Kamera liveness webcam, Leaflet GPS, & status 4 sesi presensi)
    Route::get('/dashboard', [KaryawanController::class, 'dashboard'])->name('dashboard');

    // Pengiriman Presensi (Upload foto webcam + koordinat GPS)
    Route::post('/attendance', [KaryawanController::class, 'storeAttendance'])->middleware('throttle:attendance_submit')->name('attendance.store');

    // Riwayat & Catatan Kehadiran Pribadi Pegawai
    Route::get('/riwayat', [KaryawanController::class, 'riwayat'])->name('riwayat');

    // Pengajuan Permohonan Cuti Mandiri
    Route::get('/cuti', [KaryawanController::class, 'leaveIndex'])->name('cuti.index'); // Daftar cuti pribadi & form pengajuan
    Route::post('/cuti', [KaryawanController::class, 'leaveStore'])->name('cuti.store'); // Simpan pengajuan cuti baru

    // Pengaturan Akun Pribadi Pegawai
    Route::get('/profile', [KaryawanController::class, 'profile'])->name('profile'); // Tampilan ganti kata sandi & foto profil
    Route::post('/profile/password', [KaryawanController::class, 'updatePassword'])->name('profile.password'); // Simpan kata sandi baru
    Route::post('/profile/foto', [KaryawanController::class, 'updateFoto'])->middleware('throttle:file_upload')->name('profile.foto'); // Upload & ubah foto profil
    Route::delete('/profile/foto', [KaryawanController::class, 'deleteFoto'])->name('profile.foto.delete'); // Hapus foto profil
});


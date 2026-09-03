<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * Controller Portal Pegawai & Peserta Magang
 *
 * Mengelola seluruh interaksi pegawai dalam sistem presensi meliputi:
 * 1. Dashboard presensi (status 4 sesi: masuk, istirahat, masuk istirahat, pulang).
 * 2. Pemrosesan bukti kehadiran (upload foto wajah webcam liveness + geolokasi GPS Leaflet).
 * 3. Validasi radius kantor (geofencing) dan jendela waktu toleransi 15 menit.
 * 4. Penentuan status ketepatan waktu resmi (tepat waktu / terlambat / lebih awal).
 * 5. Riwayat kehadiran pribadi, pengajuan cuti mandiri, serta ubah kata sandi akun.
 */
class KaryawanController extends Controller
{
    /**
     * Menampilkan dashboard utama pegawai.
     * Memuat informasi jadwal kerja hari ini, status buka/tutup jendela presensi,
     * serta status rekaman presensi yang telah dilakukan pegawai.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Mengambil jadwal kerja yang berlaku untuk hari ini
        $schedule = Schedule::getScheduleForDate($today);

        // Mengambil seluruh presensi milik pegawai hari ini berdasarkan sesi
        $myAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->get()
            ->keyBy('tipe');

        $setting = Setting::getOfficeSetting();
        $todayLeave = Leave::getUserLeaveOnDate($user->id, $today);

        // Menyusun status 4 sesi presensi harian
        $types = ['masuk', 'istirahat', 'masuk_istirahat', 'pulang'];
        $cards = [];

        foreach ($types as $tipe) {
            $windowInfo = $schedule->getWindowStatus($tipe, $now);
            $attendanceRecord = $myAttendances->get($tipe);
            $isApproved = $attendanceRecord && $attendanceRecord->approval_status === 'diterima';
            $isRejected = $attendanceRecord && $attendanceRecord->approval_status === 'ditolak';
            $isOnLeave = !empty($todayLeave) && !$isApproved;

            $cards[$tipe] = [
                'tipe' => $tipe,
                'label' => Attendance::getTipeLabel($tipe),
                'window' => $windowInfo,
                'record' => $attendanceRecord,
                'has_attended' => $isApproved,
                'is_rejected' => $isRejected,
                'is_on_leave' => $isOnLeave,
                'leave' => $todayLeave,
            ];
        }

        return view('karyawan.dashboard', compact('user', 'schedule', 'cards', 'today', 'now', 'setting', 'todayLeave'));
    }

    /**
     * Memproses penyimpanan presensi pegawai dengan foto bukti webcam dan koordinat GPS.
     * Menjalankan validasi berlapis:
     * 1. Cek duplikasi presensi yang telah diterima di sesi yang sama.
     * 2. Cek hari libur nasional / tanggal merah (presensi ditiadakan).
     * 3. Cek jendela waktu presensi (15 menit sebelum hingga 15 menit setelah target).
     * 4. Cek radius geofencing dari lokasi kantor Pengadilan Tinggi Pontianak.
     * 5. Penyimpanan berkas foto wajah (mendukung upload berkas biner langsung & base64).
     * 6. Penentuan status ketepatan waktu resmi (Tepat Waktu / Terlambat / Lebih Awal).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function storeAttendance(Request $request)
    {
        // Validasi input data dari formulir presensi
        $request->validate([
            'tipe' => 'required|in:masuk,istirahat,masuk_istirahat,pulang',
            'foto' => 'nullable|string',
            'foto_file' => 'nullable|file',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'alamat' => 'nullable|string',
        ], [
            'latitude.required' => 'Lokasi GPS belum terdeteksi.',
            'longitude.required' => 'Lokasi GPS belum terdeteksi.',
        ]);

        // Verifikasi bahwa bukti foto wajah telah diambil melalui kamera
        if (!$request->hasFile('foto_file') && empty($request->foto)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Foto wajah wajib diambil menggunakan kamera.'], 422);
            }
            return back()->with('error', 'Foto wajah wajib diambil menggunakan kamera.');
        }

        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // 1. Cek apakah sudah pernah melakukan presensi yang disetujui pada sesi ini
        $existingApproved = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->where('tipe', $request->tipe)
            ->where('approval_status', 'diterima')
            ->first();

        if ($existingApproved) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan presensi ' . Attendance::getTipeLabel($request->tipe) . ' hari ini dan telah diterima.'
                ], 422);
            }
            return back()->with('error', 'Anda sudah melakukan presensi ' . Attendance::getTipeLabel($request->tipe) . ' hari ini dan telah diterima.');
        }

        // Hapus rekaman presensi sebelumnya jika berstatus ditolak agar pegawai bisa mengirim ulang foto yang valid
        Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->where('tipe', $request->tipe)
            ->where('approval_status', 'ditolak')
            ->delete();

        // 2. Cek apakah hari ini adalah hari libur nasional / tanggal merah
        $schedule = Schedule::getScheduleForDate($today);
        if ($schedule->is_libur) {
            $msg = 'Presensi ditiadakan karena hari ini adalah hari libur (' . ($schedule->keterangan ?? 'Tanggal Merah') . ').';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // 3. Cek jendela waktu toleransi presensi (15 menit sebelum s/d 15 menit setelah target)
        $windowInfo = $schedule->getWindowStatus($request->tipe, $now);

        if (!$windowInfo['is_open']) {
            $msg = $windowInfo['is_before']
                ? 'Presensi ' . Attendance::getTipeLabel($request->tipe) . ' belum dibuka. Pintu dibuka pukul ' . $windowInfo['open_time'] . '.'
                : 'Presensi ' . Attendance::getTipeLabel($request->tipe) . ' telah ditutup pada pukul ' . $windowInfo['close_time'] . '.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // 4. Validasi Radius Geofencing GPS Kantor
        $setting = Setting::getOfficeSetting();
        if ($setting->enforce_radius) {
            $distance = Setting::calculateDistanceInMeters(
                $request->latitude,
                $request->longitude,
                $setting->latitude_kantor,
                $setting->longitude_kantor
            );

            if ($distance > $setting->radius_meter) {
                $msg = 'Gagal Presensi! Lokasi Anda (' . $distance . 'm) berada di luar radius kantor ' . $setting->nama_kantor . ' (Maksimal: ' . $setting->radius_meter . 'm).';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
        }

        // 5. Pemrosesan dan penyimpanan berkas gambar foto wajah
        $folderPath = 'uploads/absensi';
        $fullFolderPath = public_path($folderPath);
        if (!File::exists($fullFolderPath)) {
            File::makeDirectory($fullFolderPath, 0755, true, true);
        }

        if ($request->hasFile('foto_file') && $request->file('foto_file')->isValid()) {
            // Upload langsung berkas biner
            $uploadedFile = $request->file('foto_file');
            $imgInfo = @getimagesize($uploadedFile->getRealPath());
            if ($imgInfo === false || !in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Berkas foto tidak valid (bukan gambar asli).'], 422);
                }
                return back()->with('error', 'Berkas foto tidak valid (bukan gambar asli).');
            }
            $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }
            $type = ($ext === 'jpeg') ? 'jpg' : $ext;
            $filename = 'absensi_' . $user->id . '_' . $request->tipe . '_' . time() . '_' . uniqid() . '.' . $type;
            $uploadedFile->move($fullFolderPath, $filename);
            $filePath = $folderPath . '/' . $filename;
        } else {
            // Cadangan data URL Base64 dari canvas kamera
            $image = $request->foto;
            if (preg_match('/^data:image\/(\w+);base64,/', $image, $typeMatches)) {
                $image = substr($image, strpos($image, ',') + 1);
                $type = strtolower($typeMatches[1]);
            } else {
                $type = 'jpg';
            }
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                $type = 'jpg';
            }
            if ($type === 'jpeg') {
                $type = 'jpg';
            }

            $imageData = base64_decode($image);

            if ($imageData === false || strlen($imageData) < 8) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Gagal memproses gambar foto wajah.'], 422);
                }
                return back()->with('error', 'Gagal memproses gambar foto wajah.');
            }

            $filename = 'absensi_' . $user->id . '_' . $request->tipe . '_' . time() . '_' . uniqid() . '.' . $type;
            $filePath = $folderPath . '/' . $filename;
            file_put_contents(public_path($filePath), $imageData);
        }

        // 6. Penentuan status ketepatan waktu resmi:
        // - Kecepatan absen masuk pagi (<= target): Tepat Waktu
        // - Telat absen istirahat (>= target): Tepat Waktu
        // - Kecepatan absen masuk setelah istirahat (<= target): Tepat Waktu
        // - Telat absensi pulang (>= target): Tepat Waktu
        $targetDateTime = $windowInfo['target_datetime'];
        $status = Attendance::determineStatus($request->tipe, $now, $targetDateTime);

        // 7. Ekstraksi Alamat IP Klien (mendukung Cloudflare, Proxy, WiFi, dan IP Langsung)
        $rawForwarded = $request->header('X-Forwarded-For');
        $clientIp = $request->header('CF-Connecting-IP')
            ?? ($rawForwarded ? trim(explode(',', $rawForwarded)[0]) : null)
            ?? $request->ip();

        // 8. Buat rekaman data presensi baru di database
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'tanggal' => $today,
            'tipe' => $request->tipe,
            'waktu' => $now,
            'foto' => $filePath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'alamat' => $request->alamat ?? 'Koordinat: ' . $request->latitude . ', ' . $request->longitude,
            'ip_address' => $clientIp,
            'status' => $status,
        ]);

        $message = 'Presensi ' . Attendance::getTipeLabel($request->tipe) . ' berhasil tercatat (' . Attendance::getStatusLabel($status) . ')!';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'attendance' => $attendance,
            ]);
        }

        return redirect()->route('karyawan.dashboard')->with('success', $message);
    }

    /**
     * Menampilkan riwayat kehadiran pribadi pegawai berdasarkan filter bulan dan tahun.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function riwayat(Request $request)
    {
        $user = Auth::user();
        $month = $request->get('bulan', date('m'));
        $year = $request->get('tahun', date('Y'));

        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('karyawan.riwayat', compact('attendances', 'month', 'year'));
    }

    /**
     * Menampilkan halaman profil & formulir ubah kata sandi pegawai.
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = Auth::user();
        return view('karyawan.profile', compact('user'));
    }

    /**
     * Memproses pembaruan kata sandi akun pegawai.
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
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password Anda berhasil diperbarui!');
    }

    /**
     * Memproses pengunggahan / pembaruan foto profil (PP) pegawai.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'foto.required' => 'Silakan pilih berkas foto profil terlebih dahulu.',
            'foto.image' => 'Berkas harus berupa gambar.',
            'foto.mimes' => 'Format foto harus berupa JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran berkas foto maksimal 2MB.',
        ]);

        $user = Auth::user();

        $folder = 'uploads/profiles';
        $fullFolder = public_path($folder);
        if (!File::exists($fullFolder)) {
            File::makeDirectory($fullFolder, 0755, true, true);
        }

        // Hapus berkas foto lama jika ada
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
        $filename = 'profile_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $ext;
        $file->move($fullFolder, $filename);

        $user->update([
            'foto' => $folder . '/' . $filename,
        ]);

        return back()->with('success', 'Foto profil (PP) berhasil diperbarui!');
    }

    /**
     * Menghapus foto profil (PP) pegawai dan kembali menggunakan inisial.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteFoto(Request $request)
    {
        $user = Auth::user();

        if ($user->foto && File::exists(public_path($user->foto))) {
            File::delete(public_path($user->foto));
        }

        $user->update([
            'foto' => null,
        ]);

        return back()->with('success', 'Foto profil berhasil dihapus.');
    }

    /**
     * Menampilkan riwayat izin cuti pribadi pegawai dan formulir pengajuan cuti baru.
     *
     * @return \Illuminate\View\View
     */
    public function leaveIndex()
    {
        $user = Auth::user();
        $leaves = Leave::where('user_id', $user->id)
            ->orderBy('tanggal_mulai', 'desc')
            ->paginate(10);

        // Rekapitulasi jumlah hari cuti yang telah disetujui
        $totalCutiTahunan = Leave::where('user_id', $user->id)->where('jenis_cuti', 'cuti_tahunan')->where('status', 'disetujui')->sum('jumlah_hari');
        $totalCutiSakit = Leave::where('user_id', $user->id)->where('jenis_cuti', 'cuti_sakit')->where('status', 'disetujui')->sum('jumlah_hari');
        $totalCutiLuarNegeri = Leave::where('user_id', $user->id)->where('jenis_cuti', 'cuti_luar_negeri')->where('status', 'disetujui')->sum('jumlah_hari');
        $totalPending = Leave::where('user_id', $user->id)->where('status', 'menunggu')->count();

        return view('karyawan.cuti', compact(
            'user',
            'leaves',
            'totalCutiTahunan',
            'totalCutiSakit',
            'totalCutiLuarNegeri',
            'totalPending'
        ));
    }

    /**
     * Memproses pengajuan permohonan cuti baru oleh pegawai.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function leaveStore(Request $request)
    {
        // 1. Ambil data pegawai/user yang sedang aktif login
        $user = Auth::user();

        // 2. Validasi input formulir pengajuan izin/cuti
        $validated = $request->validate([
            // Memastikan jenis cuti yang dipilih sesuai kategori resmi
            'jenis_cuti' => 'required|in:cuti_tahunan,cuti_sakit,cuti_luar_negeri,cuti_alasan_penting,cuti_lainnya',
            // PENTING: Aturan 'after_or_equal:today' mencegah user/pegawai mengajukan izin ke tanggal lampau
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            // Memastikan tanggal selesai tidak sebelum tanggal mulai
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            // Alasan pengajuan wajib diisi (maksimal 1000 karakter)
            'alasan' => 'required|string|max:1000',
            // Berkas pendukung (misal: surat dokter) bersifat opsional dengan ukuran maksimal 5MB
            'dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            // Pesan peringatan dalam Bahasa Indonesia yang ramah pengguna
            'jenis_cuti.required' => 'Jenis izin/cuti wajib dipilih.',
            'tanggal_mulai.required' => 'Tanggal mulai izin/cuti wajib diisi.',
            'tanggal_mulai.after_or_equal' => 'Pengajuan izin/cuti tidak dapat dilakukan untuk tanggal yang telah lewat (hanya untuk hari ini atau tanggal mendatang).',
            'tanggal_selesai.required' => 'Tanggal selesai izin/cuti wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'alasan.required' => 'Alasan / keterangan izin/cuti wajib diisi.',
            'dokumen.max' => 'Ukuran file dokumen pendukung maksimal 5MB.',
        ]);

        // 3. Konversi string tanggal menjadi objek Carbon untuk menghitung jumlah hari pengajuan
        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);
        // Hitung selisih hari inklusif (+1 hari)
        $jumlahHari = $start->diffInDays($end) + 1;

        // 4. Proses penyimpanan dokumen lampiran jika diunggah oleh user
        $docPath = null;
        if ($request->hasFile('dokumen') && $request->file('dokumen')->isValid()) {
            // Tentukan folder penyimpanan dokumen cuti
            $folder = 'uploads/dokumen_cuti';
            $fullFolder = public_path($folder);

            // Buat folder jika belum ada di direktori server
            if (!File::exists($fullFolder)) {
                File::makeDirectory($fullFolder, 0755, true, true);
            }

            // Ambil berkas yang diunggah
            $file = $request->file('dokumen');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');

            // Validasi format ekstensi file
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                return back()->with('error', 'Format dokumen pendukung tidak didukung.');
            }

            // Buat nama berkas unik
            $filename = 'cuti_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $ext;
            // Pindahkan file ke folder tujuan
            $file->move($fullFolder, $filename);
            // Simpan path relatif untuk database
            $docPath = $folder . '/' . $filename;
        }

        // 5. Simpan rekaman permohonan cuti baru ke tabel 'leaves' dengan status awal 'menunggu'
        Leave::create([
            'user_id' => $user->id,                                 // ID user pemohon
            'jenis_cuti' => $validated['jenis_cuti'],               // Kategori cuti
            'tanggal_mulai' => $validated['tanggal_mulai'],         // Tanggal mulai
            'tanggal_selesai' => $validated['tanggal_selesai'],     // Tanggal selesai
            'jumlah_hari' => $jumlahHari,                           // Total hari
            'alasan' => $validated['alasan'],                       // Alasan pengajuan
            'dokumen_pendukung' => $docPath,                        // Path berkas dokumen
            'status' => 'menunggu',                                 // Menunggu persetujuan operator/admin
        ]);

        // 6. Redirect kembali ke halaman daftar izin/cuti dengan flash message sukses
        return redirect()->route('karyawan.cuti.index')
            ->with('success', 'Pengajuan Cuti berhasil dikirim! Menunggu persetujuan Operator.');
    }
}

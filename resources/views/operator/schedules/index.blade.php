@extends('layouts.app')

@section('title', 'Pengaturan Jam Kerja (Senin - Jumat) - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Top Navigation Tabs (Jam Kerja vs Hari Libur) -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <a href="{{ route('operator.schedules.index') }}"
            class="px-4 py-2.5 rounded-xl font-extrabold text-xs transition flex items-center gap-2 bg-[#064e3b] text-white shadow-md border border-emerald-700">
            <i class="fa-solid fa-clock text-amber-300"></i> Jam Kerja Mingguan
        </a>
        <a href="{{ route('operator.holidays.index') }}"
            class="px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100">
            <i class="fa-solid fa-calendar-xmark text-rose-500"></i> Daftar Hari Libur &amp; Tanggal Merah
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clock text-emerald-700"></i> Pengaturan Jam Kerja &amp; Toleransi Presensi
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Atur jam target, jam dibuka, jam ditutup, dan batas toleransi keterlambatan. Pegawai yang absen dalam batas toleransi tetap dihitung <span class="font-bold text-emerald-700">Tepat Waktu</span>.
            </p>
        </div>

        <div class="flex items-center gap-2 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 text-xs font-semibold text-emerald-900">
            <i class="fa-solid fa-calendar-week text-emerald-700 text-base"></i>
            <span>Hari Kerja: <strong class="text-emerald-950 font-extrabold">Senin s/d Jumat</strong></span>
        </div>
    </div>

    <!-- Day Selector Tabs (Senin - Jumat & Weekend) -->
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        @foreach(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $h)
            @php
                $isSel = ($h === $selectedHari);
                $isWk = ($h === 'sabtu' || $h === 'minggu');
                $schedItem = $daySchedules[$h] ?? null;
            @endphp
            <a href="{{ route('operator.schedules.index', ['hari' => $h]) }}"
               class="px-4 py-2.5 rounded-xl font-extrabold text-xs transition flex items-center gap-2 shadow-sm
               {{ $isSel ? 'bg-[#064e3b] text-amber-300 border border-amber-400/40 shadow-emerald-900/30' : ($isWk ? 'bg-slate-100 text-slate-400 hover:bg-slate-200' : 'bg-white text-slate-700 border border-slate-200 hover:bg-emerald-50/50') }}">
                @if($h === 'jumat')
                    <i class="fa-solid fa-mosque me-0.5 {{ $isSel ? 'text-amber-300' : 'text-emerald-700' }}"></i>
                @elseif($isWk)
                    <i class="fa-solid fa-bed me-0.5"></i>
                @else
                    <i class="fa-solid fa-briefcase me-0.5 {{ $isSel ? 'text-amber-300' : 'text-emerald-700' }}"></i>
                @endif
                {{ \App\Models\Schedule::getHariLabel($h) }}
                @if($schedItem && $schedItem->is_libur)
                    <span class="px-1.5 py-0.5 text-[9px] rounded font-bold uppercase {{ $isSel ? 'bg-amber-400 text-slate-950' : 'bg-rose-100 text-rose-700' }}">Libur</span>
                @endif
            </a>
        @endforeach
    </div>

    <!-- Form Configuration for Selected Day -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Active Day Form -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl bg-[#064e3b] text-amber-300 flex items-center justify-center text-xl font-bold shadow-lg shadow-emerald-900/30 border border-emerald-700">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider font-bold text-emerald-800 block">Konfigurasi Jam &amp; Toleransi</span>
                        <h2 class="text-xl font-black text-slate-900">
                            Hari {{ \App\Models\Schedule::getHariLabel($selectedHari) }}
                        </h2>
                    </div>
                </div>

                <div>
                    @if($activeSchedule->is_libur)
                        <span class="px-3 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-full border border-rose-200">
                            <i class="fa-solid fa-ban me-1"></i> Status: HARI LIBUR
                        </span>
                    @else
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full border border-emerald-200">
                            <i class="fa-solid fa-briefcase me-1"></i> Status: HARI KERJA
                        </span>
                    @endif
                </div>
            </div>

            <!-- Petunjuk Singkat Toleransi -->
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-900 space-y-1">
                <div class="font-extrabold flex items-center gap-1.5 text-emerald-950">
                    <i class="fa-solid fa-circle-info text-emerald-700"></i> Panduan Pengaturan Jam Buka, Tutup &amp; Toleransi:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-700">
                    <li><strong>Jam Dibuka</strong>: Waktu paling awal pegawai diizinkan menekan tombol absen.</li>
                    <li><strong>Jam Target</strong>: Jam patokan resmi masuk/pulang kantor sesuai ketentuan.</li>
                    <li><strong>Batas Toleransi Tepat Waktu</strong>: Jika masuk jam 08:00 dan toleransi diset 08:59, maka pegawai yang absen hingga <strong>08:59:59</strong> statusnya tetap <strong>TEPAT WAKTU</strong>. Baru setelah lewat jam tersebut tercatat <strong>TERLAMBAT</strong>.</li>
                    <li><strong>Jam Ditutup</strong>: Pintu presensi dikunci. Setelah jam ini pegawai tidak dapat melakukan presensi mandiri.</li>
                </ul>
            </div>

            <form action="{{ route('operator.schedules.store') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="hari" value="{{ $selectedHari }}">

                <!-- Is Libur Checkbox Toggle -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Tandai Sebagai Hari Libur</span>
                        <span class="text-[11px] text-slate-500">Jika diaktifkan, presensi tidak akan dibuka pada hari {{ \App\Models\Schedule::getHariLabel($selectedHari) }}.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_libur" value="1" {{ old('is_libur', $activeSchedule->is_libur) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                    </label>
                </div>

                <!-- 4 Sesi Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    
                    <!-- 1. Sesi Jam Masuk -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-right-to-bracket text-emerald-700"></i> Sesi 1: Jam Masuk
                            </span>
                            <span class="text-[10px] text-emerald-800 font-bold uppercase bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200">Check-In</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Target Jam -->
                            <div>
                                <label for="jam_masuk" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Target Jam Masuk:
                                </label>
                                <input type="time" name="jam_masuk" id="jam_masuk" value="{{ old('jam_masuk', \Carbon\Carbon::parse($activeSchedule->jam_masuk)->format('H:i')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Batas Toleransi Tepat Waktu -->
                            <div>
                                <label for="jam_toleransi_masuk" class="block text-[11px] font-extrabold text-emerald-800 mb-1 flex items-center justify-between">
                                    <span>Batas Tepat Waktu:</span>
                                    <i class="fa-solid fa-shield-halved text-emerald-600" title="Absen s/d jam ini tetap Tepat Waktu"></i>
                                </label>
                                <input type="time" name="jam_toleransi_masuk" id="jam_toleransi_masuk" value="{{ old('jam_toleransi_masuk', $activeRules['masuk']['jam_toleransi'] ?? '08:59') }}" required
                                    class="w-full bg-emerald-50/80 border border-emerald-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-emerald-900 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Dibuka -->
                            <div>
                                <label for="jam_buka_masuk" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Dibuka Pukul:
                                </label>
                                <input type="time" name="jam_buka_masuk" id="jam_buka_masuk" value="{{ old('jam_buka_masuk', $activeRules['masuk']['jam_buka'] ?? '06:30') }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Ditutup -->
                            <div>
                                <label for="jam_tutup_masuk" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Ditutup Pukul:
                                </label>
                                <input type="time" name="jam_tutup_masuk" id="jam_tutup_masuk" value="{{ old('jam_tutup_masuk', $activeRules['masuk']['jam_tutup'] ?? '11:00') }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-500 pt-1.5 border-t border-slate-200">
                            <i class="fa-solid fa-info-circle text-emerald-600 me-1"></i> Absen s/d <strong class="text-emerald-800 font-mono">{{ $activeRules['masuk']['jam_toleransi'] ?? '08:59' }}</strong> dihitung <strong>Tepat Waktu</strong>. Melewati batas ini status <strong>Terlambat</strong>.
                        </div>
                    </div>

                    <!-- 2. Sesi Jam Istirahat -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-mug-hot text-amber-600"></i> Sesi 2: Jam Istirahat
                            </span>
                            <span class="text-[10px] text-amber-800 font-bold uppercase bg-amber-100 px-2 py-0.5 rounded border border-amber-200">Break Start</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Target Jam -->
                            <div>
                                <label for="jam_istirahat" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Target Istirahat:
                                </label>
                                <input type="time" name="jam_istirahat" id="jam_istirahat" value="{{ old('jam_istirahat', \Carbon\Carbon::parse($activeSchedule->jam_istirahat)->format('H:i')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Batas Tepat Waktu -->
                            <div>
                                <label for="jam_toleransi_istirahat" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Batas Tepat Waktu:
                                </label>
                                <input type="time" name="jam_toleransi_istirahat" id="jam_toleransi_istirahat" value="{{ old('jam_toleransi_istirahat', $activeRules['istirahat']['jam_toleransi'] ?? ($selectedHari === 'jumat' ? '11:30' : '12:00')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Dibuka -->
                            <div>
                                <label for="jam_buka_istirahat" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Dibuka Pukul:
                                </label>
                                <input type="time" name="jam_buka_istirahat" id="jam_buka_istirahat" value="{{ old('jam_buka_istirahat', $activeRules['istirahat']['jam_buka'] ?? ($selectedHari === 'jumat' ? '11:00' : '11:30')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Ditutup -->
                            <div>
                                <label for="jam_tutup_istirahat" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Ditutup Pukul:
                                </label>
                                <input type="time" name="jam_tutup_istirahat" id="jam_tutup_istirahat" value="{{ old('jam_tutup_istirahat', $activeRules['istirahat']['jam_tutup'] ?? '13:00') }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-500 pt-1.5 border-t border-slate-200">
                            <i class="fa-solid fa-info-circle text-amber-600 me-1"></i> Keluar mendahului target dihitung <strong>Lebih Awal</strong>. Pas/setelah jam istirahat dihitung <strong>Tepat Waktu</strong>.
                        </div>
                    </div>

                    <!-- 3. Sesi Jam Masuk Istirahat -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-briefcase text-teal-700"></i> Sesi 3: Masuk Istirahat
                            </span>
                            <span class="text-[10px] text-teal-800 font-bold uppercase bg-teal-100 px-2 py-0.5 rounded border border-teal-200">Break End</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Target Jam -->
                            <div>
                                <label for="jam_masuk_istirahat" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Target Masuk Istirahat:
                                </label>
                                <input type="time" name="jam_masuk_istirahat" id="jam_masuk_istirahat" value="{{ old('jam_masuk_istirahat', \Carbon\Carbon::parse($activeSchedule->jam_masuk_istirahat)->format('H:i')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Batas Toleransi Tepat Waktu -->
                            <div>
                                <label for="jam_toleransi_masuk_istirahat" class="block text-[11px] font-extrabold text-teal-800 mb-1 flex items-center justify-between">
                                    <span>Batas Tepat Waktu:</span>
                                    <i class="fa-solid fa-shield-halved text-teal-600" title="Kembali istirahat s/d jam ini tetap Tepat Waktu"></i>
                                </label>
                                <input type="time" name="jam_toleransi_masuk_istirahat" id="jam_toleransi_masuk_istirahat" value="{{ old('jam_toleransi_masuk_istirahat', $activeRules['masuk_istirahat']['jam_toleransi'] ?? '13:15') }}" required
                                    class="w-full bg-teal-50/80 border border-teal-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-teal-900 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Dibuka -->
                            <div>
                                <label for="jam_buka_masuk_istirahat" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Dibuka Pukul:
                                </label>
                                <input type="time" name="jam_buka_masuk_istirahat" id="jam_buka_masuk_istirahat" value="{{ old('jam_buka_masuk_istirahat', $activeRules['masuk_istirahat']['jam_buka'] ?? '12:30') }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Ditutup -->
                            <div>
                                <label for="jam_tutup_masuk_istirahat" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Ditutup Pukul:
                                </label>
                                <input type="time" name="jam_tutup_masuk_istirahat" id="jam_tutup_masuk_istirahat" value="{{ old('jam_tutup_masuk_istirahat', $activeRules['masuk_istirahat']['jam_tutup'] ?? '14:30') }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-500 pt-1.5 border-t border-slate-200">
                            <i class="fa-solid fa-info-circle text-teal-600 me-1"></i> Kembali s/d <strong class="text-teal-800 font-mono">{{ $activeRules['masuk_istirahat']['jam_toleransi'] ?? '13:15' }}</strong> dihitung <strong>Tepat Waktu</strong>. Melewati batas ini status <strong>Terlambat</strong>.
                        </div>
                    </div>

                    <!-- 4. Sesi Jam Pulang -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-right-from-bracket text-emerald-700"></i> Sesi 4: Jam Pulang
                            </span>
                            <span class="text-[10px] text-emerald-800 font-bold uppercase bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200">Check-Out</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Target Jam -->
                            <div>
                                <label for="jam_pulang" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Target Jam Pulang:
                                </label>
                                <input type="time" name="jam_pulang" id="jam_pulang" value="{{ old('jam_pulang', \Carbon\Carbon::parse($activeSchedule->jam_pulang)->format('H:i')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Batas Tepat Waktu / Pulang Kantor -->
                            <div>
                                <label for="jam_toleransi_pulang" class="block text-[11px] font-bold text-slate-700 mb-1">
                                    Batas Tepat Waktu:
                                </label>
                                <input type="time" name="jam_toleransi_pulang" id="jam_toleransi_pulang" value="{{ old('jam_toleransi_pulang', $activeRules['pulang']['jam_toleransi'] ?? ($selectedHari === 'jumat' ? '16:30' : '17:00')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-bold text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Dibuka -->
                            <div>
                                <label for="jam_buka_pulang" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Dibuka Pukul:
                                </label>
                                <input type="time" name="jam_buka_pulang" id="jam_buka_pulang" value="{{ old('jam_buka_pulang', $activeRules['pulang']['jam_buka'] ?? ($selectedHari === 'jumat' ? '16:00' : '16:30')) }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>

                            <!-- Jam Ditutup -->
                            <div>
                                <label for="jam_tutup_pulang" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Ditutup Pukul:
                                </label>
                                <input type="time" name="jam_tutup_pulang" id="jam_tutup_pulang" value="{{ old('jam_tutup_pulang', $activeRules['pulang']['jam_tutup'] ?? '23:59') }}" required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-500 pt-1.5 border-t border-slate-200">
                            <i class="fa-solid fa-info-circle text-emerald-600 me-1"></i> Pulang mendahului jam kantor dihitung <strong>Lebih Awal</strong>. Lembur / pulang setelahnya dihitung <strong>Tepat Waktu</strong>.
                        </div>
                    </div>

                </div>

                <!-- Terapkan ke Seluruh Hari Kerja Checkbox -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
                            <i class="fa-solid fa-copy"></i>
                        </div>
                        <div>
                            <span class="text-xs font-black text-slate-900 block">Terapkan ke Seluruh Hari Kerja (Senin - Jumat)</span>
                            <span class="text-[11px] text-slate-600">Salin otomatis jam target, toleransi keterlambatan, dan jendela buka/tutup hari {{ \App\Models\Schedule::getHariLabel($selectedHari) }} ini ke hari Senin s/d Jumat.</span>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="terapkan_semua_hari" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Keterangan -->
                <div>
                    <label for="keterangan" class="block text-xs font-bold text-slate-700 mb-1">
                        Keterangan Hari {{ \App\Models\Schedule::getHariLabel($selectedHari) }} (Opsional)
                    </label>
                    <input type="text" name="keterangan" id="keterangan" value="{{ old('keterangan', $activeSchedule->keterangan) }}" placeholder="Contoh: Jam Kerja Regular / Sholat Jumat / Pulang Cepat"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-extrabold rounded-xl shadow-lg shadow-emerald-900/30 transition text-xs flex items-center justify-center gap-2 border border-emerald-700 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-amber-300 text-base"></i> SIMPAN JAM KERJA &amp; TOLERANSI HARI {{ strtoupper(\App\Models\Schedule::getHariLabel($selectedHari)) }}
                </button>
            </form>
        </div>

        <!-- Weekly Summary Cards Sidebar (Senin - Jumat Overview) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
                <i class="fa-solid fa-list-check text-emerald-700"></i> Ringkasan Jam &amp; Toleransi Mingguan
            </h3>

            <div class="space-y-2.5">
                @foreach(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $dh)
                    @php
                        $sc = $daySchedules[$dh];
                        $isCurrent = ($dh === $selectedHari);
                        $dRules = \App\Models\Schedule::getRulesForDay($dh);
                    @endphp
                    <div class="p-3 rounded-xl border text-xs transition {{ $isCurrent ? 'bg-emerald-50 border-emerald-300 ring-2 ring-emerald-600/30' : 'bg-slate-50 border-slate-200' }}">
                        <div class="flex items-center justify-between font-bold text-slate-900 mb-1">
                            <span class="flex items-center gap-1.5">
                                {{ \App\Models\Schedule::getHariLabel($dh) }}
                                @if($dh === 'jumat')
                                    <span class="text-[10px] text-emerald-700 font-bold">(Jumat)</span>
                                @endif
                            </span>
                            @if($sc->is_libur)
                                <span class="px-2 py-0.5 rounded text-[10px] bg-rose-100 text-rose-700 font-bold">Libur</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 font-bold">Masuk</span>
                            @endif
                        </div>
                        @if(!$sc->is_libur)
                            <div class="text-[11px] text-slate-700 font-mono flex justify-between">
                                <span>Masuk:</span>
                                <span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($sc->jam_masuk)->format('H:i') }} (Tol: {{ $dRules['masuk']['jam_toleransi'] ?? '08:59' }})</span>
                            </div>
                            <div class="text-[11px] text-slate-700 font-mono flex justify-between">
                                <span>Pulang:</span>
                                <span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($sc->jam_pulang)->format('H:i') }}</span>
                            </div>
                            <div class="text-[10px] text-slate-500 flex justify-between pt-1 border-t border-slate-200/80 mt-1">
                                <span>Jendela Pagi:</span>
                                <span class="font-semibold text-emerald-800">{{ $dRules['masuk']['jam_buka'] ?? '06:30' }} - {{ $dRules['masuk']['jam_tutup'] ?? '11:00' }}</span>
                            </div>
                        @else
                            <div class="text-[11px] text-slate-400 italic">Tidak ada presensi</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection

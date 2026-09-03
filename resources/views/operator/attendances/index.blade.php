@extends('layouts.app')

@section('title', 'Input & Edit Presensi Pegawai - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                    Modul Presensi
                </span>
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="text-xs font-bold text-slate-500">Tanggal: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 flex items-center gap-2 mt-1">
                <i class="fa-solid fa-clipboard-user text-emerald-700"></i> Input &amp; Edit Presensi Pegawai
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola rekaman kehadiran, input manual presensi pegawai (hadir tepat waktu, terlambat, izin, atau sakit), serta sesuaikan jam dan status kehadiran.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="openCreateModal()"
                class="px-4 py-2.5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-2 border border-emerald-700 cursor-pointer">
                <i class="fa-solid fa-plus text-amber-300 text-sm"></i> Input Presensi Manual
            </button>
            <a href="{{ route('operator.reports.index', ['tanggal_mulai' => $tanggal, 'tanggal_selesai' => $tanggal]) }}"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition flex items-center gap-2 border border-slate-300">
                <i class="fa-solid fa-file-invoice text-emerald-700 text-sm"></i> Laporan Rekap
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid (6 Kartu Statistik Ringkas Tanpa Emoji) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Total Sesi -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-base border border-slate-200 shrink-0">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black text-slate-900">{{ number_format($totalAll) }}</div>
                <div class="text-[10px] text-slate-500 font-semibold truncate">Total Sesi</div>
            </div>
        </div>

        <!-- Tepat Waktu -->
        <div class="bg-white p-3.5 rounded-2xl border border-emerald-200 shadow-sm flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-base border border-emerald-200 shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black text-emerald-700">{{ number_format($totalTepatWaktu) }}</div>
                <div class="text-[10px] text-slate-500 font-semibold truncate">Tepat Waktu</div>
            </div>
        </div>

        <!-- Terlambat -->
        <div class="bg-white p-3.5 rounded-2xl border border-amber-200 shadow-sm flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base border border-amber-200 shrink-0">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black text-amber-700">{{ number_format($totalTerlambat) }}</div>
                <div class="text-[10px] text-slate-500 font-semibold truncate">Terlambat</div>
            </div>
        </div>

        <!-- Izin -->
        <div class="bg-white p-3.5 rounded-2xl border border-indigo-200 shadow-sm flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base border border-indigo-200 shrink-0">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black text-indigo-700">{{ number_format($totalIzin) }}</div>
                <div class="text-[10px] text-slate-500 font-semibold truncate">Izin</div>
            </div>
        </div>

        <!-- Sakit -->
        <div class="bg-white p-3.5 rounded-2xl border border-rose-200 shadow-sm flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-base border border-rose-200 shrink-0">
                <i class="fa-solid fa-notes-medical"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black text-rose-700">{{ number_format($totalSakit) }}</div>
                <div class="text-[10px] text-slate-500 font-semibold truncate">Sakit</div>
            </div>
        </div>

        <!-- Input Manual Operator -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 shadow-sm flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-base border border-slate-300 shrink-0">
                <i class="fa-solid fa-pen-nib"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black text-slate-800">{{ number_format($totalManual) }}</div>
                <div class="text-[10px] text-slate-500 font-semibold truncate">Input Manual</div>
            </div>
        </div>
    </div>

    <!-- Filter Form Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                <i class="fa-solid fa-sliders text-emerald-700"></i> Filter &amp; Pencarian Presensi
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('operator.attendances.index', ['tanggal' => \Carbon\Carbon::today()->format('Y-m-d')]) }}"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold {{ $tanggal === \Carbon\Carbon::today()->format('Y-m-d') ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} transition">
                    <i class="fa-regular fa-calendar-check me-1"></i> Hari Ini
                </a>
                <a href="{{ route('operator.attendances.index', ['tanggal' => \Carbon\Carbon::yesterday()->format('Y-m-d')]) }}"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold {{ $tanggal === \Carbon\Carbon::yesterday()->format('Y-m-d') ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} transition">
                    Kemarin
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('operator.attendances.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
            <!-- Tanggal -->
            <div>
                <label for="filter_tanggal" class="block text-[11px] font-bold text-slate-700 mb-1">
                    Tanggal:
                </label>
                <input type="date" name="tanggal" id="filter_tanggal" value="{{ $tanggal }}"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Pegawai -->
            <div>
                <label for="filter_user_id" class="block text-[11px] font-bold text-slate-700 mb-1">
                    Pegawai:
                </label>
                <select name="user_id" id="filter_user_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Pegawai --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $user_id == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->tipe_identitas_label }}: {{ $emp->nip }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sesi Presensi -->
            <div>
                <label for="filter_tipe" class="block text-[11px] font-bold text-slate-700 mb-1">
                    Sesi:
                </label>
                <select name="tipe" id="filter_tipe" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Sesi --</option>
                    <option value="masuk" {{ $tipe == 'masuk' ? 'selected' : '' }}>Jam Masuk</option>
                    <option value="istirahat" {{ $tipe == 'istirahat' ? 'selected' : '' }}>Jam Istirahat</option>
                    <option value="masuk_istirahat" {{ $tipe == 'masuk_istirahat' ? 'selected' : '' }}>Jam Masuk Istirahat</option>
                    <option value="pulang" {{ $tipe == 'pulang' ? 'selected' : '' }}>Jam Pulang</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="filter_status" class="block text-[11px] font-bold text-slate-700 mb-1">
                    Status Kehadiran:
                </label>
                <select name="status" id="filter_status" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Status --</option>
                    <option value="tepat_waktu" {{ $status == 'tepat_waktu' ? 'selected' : '' }}>Tepat Waktu</option>
                    <option value="terlambat" {{ $status == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="lebih_awal" {{ $status == 'lebih_awal' ? 'selected' : '' }}>Lebih Awal</option>
                    <option value="izin" {{ $status == 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ $status == 'sakit' ? 'selected' : '' }}>Sakit</option>
                </select>
            </div>

            <!-- Sumber Presensi -->
            <div>
                <label for="filter_sumber" class="block text-[11px] font-bold text-slate-700 mb-1">
                    Sumber:
                </label>
                <select name="sumber" id="filter_sumber" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Sumber --</option>
                    <option value="manual" {{ $sumber == 'manual' ? 'selected' : '' }}>Hanya Input Manual</option>
                    <option value="karyawan" {{ $sumber == 'karyawan' ? 'selected' : '' }}>Hanya Presensi Mandiri</option>
                </select>
            </div>

            <!-- Tombol Aksi Filter -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow transition border border-emerald-700 flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-filter text-amber-300"></i> Terapkan
                </button>
                <a href="{{ route('operator.attendances.index') }}" class="py-2 px-3 bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold rounded-xl text-xs transition text-center" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Attendances Data Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-table-list text-emerald-700"></i>
                <h3 class="font-black text-sm text-slate-900">Daftar Rekaman Presensi Sesi</h3>
                <span class="text-xs text-slate-400">({{ $attendances->total() }} baris data)</span>
            </div>
            <div class="text-[11px] text-slate-500">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 border border-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-pen-to-square text-emerald-700"></i> Klik tombol edit untuk mengubah jam atau status sesi
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Pegawai</th>
                        <th class="py-3.5 px-4">Sesi &amp; Waktu</th>
                        <th class="py-3.5 px-4 text-center">Status Kehadiran</th>
                        <th class="py-3.5 px-4 text-center">Approval</th>
                        <th class="py-3.5 px-4 text-center">Sumber &amp; Bukti</th>
                        <th class="py-3.5 px-4">Catatan / Alamat</th>
                        <th class="py-3.5 px-4 text-center w-36">Aksi Operator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($attendances as $index => $att)
                        @php
                            $isManual = $att->isManual();
                            $badge = \App\Models\Attendance::getStatusBadge($att->status);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ $isManual ? 'bg-indigo-50/15' : '' }}">
                            <!-- No -->
                            <td class="py-3.5 px-4 font-bold text-slate-500 font-mono text-center">
                                {{ ($attendances->currentPage() - 1) * $attendances->perPage() + $index + 1 }}
                            </td>

                            <!-- Pegawai -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    @if(isset($att->user) && $att->user->foto_url)
                                        <img src="{{ $att->user->foto_url }}" alt="{{ $att->user->name }}" class="w-9 h-9 rounded-xl object-cover border border-emerald-600 shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-xl bg-emerald-800 text-amber-300 font-black text-xs flex items-center justify-center border border-emerald-700 shrink-0">
                                            {{ isset($att->user) ? $att->user->inisial : '?' }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-black text-slate-900 truncate">{{ $att->user->name ?? 'N/A' }}</div>
                                        <div class="font-mono text-[10px] text-amber-700 font-semibold">
                                            {{ $att->user->tipe_identitas_label ?? 'NIP' }}. {{ $att->user->nip ?? '-' }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 truncate">{{ $att->user->jabatan ?? 'Pegawai' }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Sesi & Waktu -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-1">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-100/70 text-emerald-900 font-black border border-emerald-300 inline-block text-[11px]">
                                        {{ \App\Models\Attendance::getTipeLabel($att->tipe) }}
                                    </span>
                                    <div class="font-mono font-bold text-slate-900 text-sm">
                                        {{ \Carbon\Carbon::parse($att->waktu)->format('H:i:s') }} <span class="text-[10px] font-normal text-slate-500">WIB</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono">
                                        {{ \Carbon\Carbon::parse($att->tanggal)->format('d/m/Y') }} ({{ \Carbon\Carbon::parse($att->tanggal)->translatedFormat('l') }})
                                    </div>
                                </div>
                            </td>

                            <!-- Status Kehadiran (Tepat Waktu / Terlambat / Lebih Awal / Izin / Sakit) -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full font-bold border inline-flex items-center gap-1.5 text-[11px] {{ $badge['bg'] }}">
                                    <i class="{{ $badge['icon'] }}"></i> {{ $badge['label'] }}
                                </span>
                            </td>

                            <!-- Approval -->
                            <td class="py-3.5 px-4 text-center">
                                @if($att->approval_status === 'diterima')
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white font-bold text-[10px] uppercase shadow-xs inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> Diterima
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white font-bold text-[10px] uppercase shadow-xs inline-flex items-center gap-1" title="{{ $att->catatan_operator }}">
                                        <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                    </span>
                                @endif
                            </td>

                            <!-- Sumber & Bukti -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex flex-col items-center gap-1.5">
                                    <button type="button" onclick="showAttendanceDetail({{ $att->id }})"
                                        class="group relative inline-block rounded-xl overflow-hidden shadow border border-slate-200 cursor-pointer transition transform hover:scale-105"
                                        title="Lihat bukti presensi &amp; rincian">
                                        <img src="{{ $att->foto_url }}" alt="Bukti" class="w-10 h-10 object-cover">
                                        <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </div>
                                    </button>
                                    @if($isManual)
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-indigo-100 text-indigo-800 border border-indigo-300 inline-flex items-center gap-1">
                                            <i class="fa-solid fa-user-pen"></i> Manual
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200 inline-flex items-center gap-1">
                                            <i class="fa-solid fa-camera"></i> Mandiri
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Catatan / Alamat -->
                            <td class="py-3.5 px-4 max-w-xs">
                                @if($att->catatan_operator)
                                    <div class="text-[11px] font-bold text-slate-800 bg-amber-50/80 border border-amber-200 rounded-lg p-1.5 mb-1">
                                        <i class="fa-solid fa-comment-dots text-amber-600 me-1"></i> "{{ $att->catatan_operator }}"
                                    </div>
                                @endif
                                <div class="text-[10px] text-slate-500 truncate" title="{{ $att->alamat }}">
                                    <i class="fa-solid fa-location-dot text-emerald-700 me-1"></i> {{ $att->alamat ?: 'Lokasi Kantor' }}
                                </div>
                                <div class="text-[9px] font-mono text-slate-400 mt-0.5">
                                    IP: {{ $att->ip_address ?: '127.0.0.1' }}
                                </div>
                            </td>

                            <!-- Aksi Operator -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Edit Button -->
                                    <button type="button" onclick="openEditModal({{ $att->id }})"
                                        class="p-2 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold border border-amber-300 rounded-xl text-xs transition shadow-xs flex items-center justify-center cursor-pointer"
                                        title="Edit Data Presensi">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Quick Approval Toggle Button -->
                                    @if($att->approval_status === 'diterima')
                                        <button type="button" onclick="handleReject({{ $att->id }}, '{{ addslashes($att->user->name ?? 'Pegawai') }}')"
                                            class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold border border-rose-300 rounded-xl text-xs transition shadow-xs flex items-center justify-center cursor-pointer"
                                            title="Tolak Presensi (ALFA)">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    @else
                                        <button type="button" onclick="handleApprove({{ $att->id }}, '{{ addslashes($att->user->name ?? 'Pegawai') }}')"
                                            class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold border border-emerald-300 rounded-xl text-xs transition shadow-xs flex items-center justify-center cursor-pointer"
                                            title="Setujui Presensi">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </button>
                                    @endif

                                    <!-- Delete Button -->
                                    <button type="button" onclick="confirmDeleteAttendance({{ $att->id }}, '{{ addslashes($att->user->name ?? 'Pegawai') }}', '{{ \App\Models\Attendance::getTipeLabel($att->tipe) }}', '{{ \Carbon\Carbon::parse($att->tanggal)->format('d/m/Y') }}')"
                                        class="p-2 bg-slate-50 hover:bg-rose-50 text-slate-500 hover:text-rose-700 font-bold border border-slate-200 hover:border-rose-300 rounded-xl text-xs transition shadow-xs flex items-center justify-center cursor-pointer"
                                        title="Hapus Rekaman Presensi">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-4xl block mb-2 text-slate-300"></i>
                                <div class="font-bold text-slate-600">Tidak ada rekaman presensi yang sesuai kriteria filter.</div>
                                <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Input Presensi Manual" di atas untuk menambahkan presensi pegawai.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: INPUT PRESENSI MANUAL OPERATOR (SEDERHANA, PRAKTIS, TANPA EMOJI) -->
<!-- ========================================================================= -->
<div id="createAttendanceModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 bg-[#064e3b] text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-white/10 text-amber-300 flex items-center justify-center font-bold text-lg border border-white/20">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>
                <div>
                    <h3 class="font-black text-base text-white leading-tight">Input Presensi Manual</h3>
                    <p class="text-[11px] text-emerald-200">Atur jam dan status (hadir tepat waktu, terlambat, izin, atau sakit) per sesi</p>
                </div>
            </div>
            <button type="button" onclick="closeCreateModal()" class="text-white/70 hover:text-white p-2 rounded-xl hover:bg-white/10 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Form Body (Scrollable) -->
        <form id="formCreateAttendance" action="{{ route('operator.attendances.manual-store') }}" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-5 space-y-4 text-xs">
            @csrf

            <!-- 1. Pilih Pegawai & Tanggal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="create_user_id" class="block font-bold text-slate-700 mb-1">
                        Pilih Pegawai <span class="text-rose-500">*</span>
                    </label>
                    <select name="user_id" id="create_user_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-semibold text-xs focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">-- Pilih Pegawai --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('user_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->tipe_identitas_label }}: {{ $emp->nip }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="create_tanggal" class="block font-bold text-slate-700 mb-1">
                        Tanggal Presensi <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal" id="create_tanggal" value="{{ old('tanggal', $tanggal ?: date('Y-m-d')) }}" required onchange="handleDateChange(this.value)"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-semibold text-xs focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <!-- 2. Menu Instan 1 Hari (4 Tombol Cepat: Tepat Waktu Semua, Terlambat Semua, Izin Semua, Sakit Semua) -->
            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200 space-y-2">
                <div class="text-[11px] font-black uppercase text-slate-700 flex items-center justify-between">
                    <span>Menu Paket Instan 1 Hari:</span>
                    <span class="text-[10px] text-slate-400 font-normal">Klik tombol di bawah untuk mengisi otomatis 4 sesi</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <!-- Tombol 1: Tepat Waktu Semua -->
                    <button type="button" onclick="applyPreset('tepat_waktu')"
                        class="py-2 px-2.5 rounded-xl font-extrabold text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span>Tepat Waktu Semua</span>
                    </button>

                    <!-- Tombol 2: Terlambat Semua -->
                    <button type="button" onclick="applyPreset('terlambat')"
                        class="py-2 px-2.5 rounded-xl font-extrabold text-xs bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                        <i class="fa-solid fa-clock-rotate-left text-amber-600"></i>
                        <span>Terlambat Semua</span>
                    </button>

                    <!-- Tombol 3: Izin Semua -->
                    <button type="button" onclick="applyPreset('izin')"
                        class="py-2 px-2.5 rounded-xl font-extrabold text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-300 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                        <i class="fa-solid fa-envelope-open-text text-indigo-600"></i>
                        <span>Izin Semua</span>
                    </button>

                    <!-- Tombol 4: Sakit Semua -->
                    <button type="button" onclick="applyPreset('sakit')"
                        class="py-2 px-2.5 rounded-xl font-extrabold text-xs bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                        <i class="fa-solid fa-notes-medical text-rose-600"></i>
                        <span>Sakit Semua</span>
                    </button>
                </div>
            </div>

            <!-- 3. Rincian 4 Sesi Presensi Harian (Bisa Dicampur Bebas: Masuk Hadir, Siang Izin/Sakit, dll.) -->
            <div class="space-y-2.5">
                <div class="font-bold text-slate-700 flex items-center justify-between">
                    <span>Atur Sesi &amp; Status Presensi:</span>
                    <span class="text-[10px] text-slate-400 font-normal">Centang sesi yang ingin diinput</span>
                </div>

                <!-- Sesi 1: Jam Masuk -->
                <div class="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 hover:border-emerald-300 transition">
                    <label class="flex items-center gap-2.5 font-black text-slate-900 cursor-pointer sm:w-44">
                        <input type="checkbox" name="sessions[]" value="masuk" id="check_masuk" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span>1. Jam Masuk</span>
                    </label>
                    <div class="flex items-center gap-2 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-slate-400 font-bold">Jam:</span>
                            <input type="time" name="jam_masuk" id="session_jam_masuk" value="08:00"
                                class="bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-mono font-bold">
                        </div>
                        <div class="flex items-center gap-1.5 flex-1">
                            <span class="text-[10px] text-slate-400 font-bold">Status:</span>
                            <select name="status_masuk" id="session_status_masuk" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-800">
                                <option value="tepat_waktu" selected>Tepat Waktu</option>
                                <option value="terlambat">Terlambat</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sesi 2: Jam Istirahat -->
                <div class="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 hover:border-emerald-300 transition">
                    <label class="flex items-center gap-2.5 font-black text-slate-900 cursor-pointer sm:w-44">
                        <input type="checkbox" name="sessions[]" value="istirahat" id="check_istirahat" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span>2. Jam Istirahat</span>
                    </label>
                    <div class="flex items-center gap-2 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-slate-400 font-bold">Jam:</span>
                            <input type="time" name="jam_istirahat" id="session_jam_istirahat" value="12:00"
                                class="bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-mono font-bold">
                        </div>
                        <div class="flex items-center gap-1.5 flex-1">
                            <span class="text-[10px] text-slate-400 font-bold">Status:</span>
                            <select name="status_istirahat" id="session_status_istirahat" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-800">
                                <option value="tepat_waktu" selected>Tepat Waktu</option>
                                <option value="lebih_awal">Lebih Awal</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sesi 3: Masuk Istirahat -->
                <div class="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 hover:border-emerald-300 transition">
                    <label class="flex items-center gap-2.5 font-black text-slate-900 cursor-pointer sm:w-44">
                        <input type="checkbox" name="sessions[]" value="masuk_istirahat" id="check_masuk_istirahat" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span>3. Masuk Istirahat</span>
                    </label>
                    <div class="flex items-center gap-2 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-slate-400 font-bold">Jam:</span>
                            <input type="time" name="jam_masuk_istirahat" id="session_jam_masuk_istirahat" value="13:00"
                                class="bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-mono font-bold">
                        </div>
                        <div class="flex items-center gap-1.5 flex-1">
                            <span class="text-[10px] text-slate-400 font-bold">Status:</span>
                            <select name="status_masuk_istirahat" id="session_status_masuk_istirahat" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-800">
                                <option value="tepat_waktu" selected>Tepat Waktu</option>
                                <option value="terlambat">Terlambat</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sesi 4: Jam Pulang -->
                <div class="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 hover:border-emerald-300 transition">
                    <label class="flex items-center gap-2.5 font-black text-slate-900 cursor-pointer sm:w-44">
                        <input type="checkbox" name="sessions[]" value="pulang" id="check_pulang" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span>4. Jam Pulang</span>
                    </label>
                    <div class="flex items-center gap-2 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-slate-400 font-bold">Jam:</span>
                            <input type="time" name="jam_pulang" id="session_jam_pulang" value="17:00"
                                class="bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-mono font-bold">
                        </div>
                        <div class="flex items-center gap-1.5 flex-1">
                            <span class="text-[10px] text-slate-400 font-bold">Status:</span>
                            <select name="status_pulang" id="session_status_pulang" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-800">
                                <option value="tepat_waktu" selected>Tepat Waktu</option>
                                <option value="lebih_awal">Lebih Awal</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Catatan Operator -->
            <div>
                <label for="create_catatan_operator" class="block font-bold text-slate-700 mb-1">
                    Catatan Operator <span class="text-slate-400 font-normal">(Opsional)</span>:
                </label>
                <textarea name="catatan_operator" id="create_catatan_operator" rows="2"
                    placeholder="Contoh: Pagi hadir tepat waktu, siang izin urusan dinas luar kantor"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-emerald-500 focus:border-emerald-500"></textarea>
            </div>

            <!-- 5. Foto / Surat Lampiran -->
            <div>
                <label for="create_foto" class="block font-bold text-slate-700 mb-1">
                    Foto / Surat Keterangan / Bukti <span class="text-slate-400 font-normal">(Opsional)</span>:
                </label>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-300 flex items-center justify-center overflow-hidden shrink-0">
                        <img id="create_preview_img" src="{{ asset('images/manual_attendance.png') }}" alt="Preview" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="foto" id="create_foto" accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf" onchange="previewImageInput(this, 'create_preview_img')"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-1">Bila dikosongkan, sistem menyematkan badge resmi presensi manual terverifikasi.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Action -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-2 border border-emerald-700 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                    <span>Simpan Data Presensi</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRESENSI PEGAWAI -->
<!-- ========================================================================= -->
<div id="editAttendanceModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 bg-[#064e3b] text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-400/20 text-amber-300 flex items-center justify-center font-bold text-lg border border-amber-400/30">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="font-black text-base text-white leading-tight">Edit Data Presensi</h3>
                    <p class="text-[11px] text-emerald-200" id="editEmployeeInfoSub">Memperbarui rincian catatan presensi</p>
                </div>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-white/70 hover:text-white p-2 rounded-xl hover:bg-white/10 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="formEditAttendance" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-5 space-y-4 text-xs">
            @csrf
            @method('PUT')

            <!-- Pegawai Info Card (Read-only) -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-800 text-amber-300 font-black text-xs flex items-center justify-center border border-emerald-700 shrink-0" id="editEmployeeInitial">
                    PG
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-black text-slate-900 truncate" id="editEmployeeName">-</div>
                    <div class="text-[11px] text-amber-800 font-mono font-bold" id="editEmployeeNip">-</div>
                    <div class="text-[10px] text-slate-500 truncate" id="editEmployeeJabatan">-</div>
                </div>
            </div>

            <!-- Tanggal & Sesi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="edit_tanggal" class="block font-bold text-slate-700 mb-1">
                        Tanggal Presensi <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal" id="edit_tanggal" required
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-semibold text-xs focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label for="edit_tipe" class="block font-bold text-slate-700 mb-1">
                        Sesi Presensi <span class="text-rose-500">*</span>
                    </label>
                    <select name="tipe" id="edit_tipe" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-semibold text-xs focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="masuk">Jam Masuk (Pagi)</option>
                        <option value="istirahat">Jam Istirahat (Siang)</option>
                        <option value="masuk_istirahat">Jam Masuk Istirahat (Siang)</option>
                        <option value="pulang">Jam Pulang (Sore)</option>
                    </select>
                </div>
            </div>

            <!-- Jam & Status Kehadiran (Termasuk Izin & Sakit) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="edit_jam" class="block font-bold text-slate-700 mb-1">
                        Jam Presensi (WIB) <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" name="jam" id="edit_jam" step="60" required
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-mono font-bold text-xs focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label for="edit_status" class="block font-bold text-slate-700 mb-1">
                        Status Kehadiran <span class="text-rose-500">*</span>
                    </label>
                    <select name="status" id="edit_status" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-semibold text-xs focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="tepat_waktu">Tepat Waktu</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="lebih_awal">Lebih Awal</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                    </select>
                </div>
            </div>

            <!-- Status Approval -->
            <div>
                <label for="edit_approval_status" class="block font-bold text-slate-700 mb-1">
                    Status Approval (Operator) <span class="text-rose-500">*</span>
                </label>
                <select name="approval_status" id="edit_approval_status" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-semibold text-xs focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="diterima">Diterima (Presensi Valid)</option>
                    <option value="ditolak">Ditolak (Dianggap ALFA / Tidak Hadir)</option>
                </select>
            </div>

            <!-- Catatan Operator -->
            <div>
                <label for="edit_catatan_operator" class="block font-bold text-slate-700 mb-1">
                    Catatan Operator:
                </label>
                <textarea name="catatan_operator" id="edit_catatan_operator" rows="2"
                    placeholder="Alasan perubahan atau catatan khusus untuk presensi ini"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-emerald-500 focus:border-emerald-500"></textarea>
            </div>

            <!-- Foto Saat Ini & Upload Baru -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">
                    Foto Bukti Presensi:
                </label>
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 border border-slate-300 flex items-center justify-center overflow-hidden shrink-0">
                        <img id="edit_current_foto" src="" alt="Foto" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="foto" id="edit_foto" accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf" onchange="previewImageInput(this, 'edit_current_foto')"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-1">Pilih berkas baru jika ingin mengganti foto/dokumen bukti.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Action -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-2 border border-amber-500 cursor-pointer">
                    <i class="fa-solid fa-check text-amber-200"></i> Simpan Perubahan Presensi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DETAIL / PREVIEW FOTO PRESENSI -->
<!-- ========================================================================= -->
<div id="detailModal" class="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl overflow-hidden max-w-lg w-full shadow-2xl border border-slate-200 animate-in fade-in zoom-in duration-200">
        <div class="p-4 bg-[#064e3b] text-white flex items-center justify-between">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-id-badge text-amber-300"></i>
                <span id="detailTitle">Bukti Presensi Pegawai</span>
            </div>
            <button type="button" onclick="closeDetailModal()" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-inner bg-slate-900 flex items-center justify-center max-h-72">
                <img id="detailFoto" src="" alt="Bukti Presensi" class="w-full h-full max-h-72 object-contain">
            </div>
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2 text-xs">
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-1.5">
                    <span class="text-slate-500 font-bold">Pegawai:</span>
                    <span class="font-black text-slate-900" id="detailName">-</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-1.5">
                    <span class="text-slate-500 font-bold">Sesi &amp; Waktu:</span>
                    <span class="font-bold text-slate-800" id="detailTime">-</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-1.5">
                    <span class="text-slate-500 font-bold">Status Kehadiran:</span>
                    <span id="detailStatusBadge" class="font-bold">-</span>
                </div>
                <div>
                    <span class="text-slate-500 font-bold block mb-0.5">Alamat / Lokasi:</span>
                    <span class="text-slate-700 font-medium" id="detailAlamat">-</span>
                </div>
                <div id="detailCatatanBox" class="hidden bg-amber-50 border border-amber-200 p-2 rounded-xl text-amber-800 text-[11px]">
                    <span class="font-bold block mb-0.5"><i class="fa-solid fa-comment-dots"></i> Catatan Operator:</span>
                    <span id="detailCatatanText">-</span>
                </div>
            </div>
        </div>
        <div class="p-3.5 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeDetailModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- Form tersembunyi untuk tolak / setujui / hapus presensi -->
<form id="rejectForm" method="POST" action="" class="hidden">
    @csrf
    <input type="hidden" name="catatan_operator" id="rejectCatatan">
</form>

<form id="approveForm" method="POST" action="" class="hidden">
    @csrf
</form>

<form id="deleteForm" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
    /**
     * Jadwal jam kerja standar
     */
    const scheduleHours = {
        normal: {
            masuk: '08:00',
            istirahat: '12:00',
            masuk_istirahat: '13:00',
            pulang: '17:00'
        },
        friday: {
            masuk: '08:00',
            istirahat: '11:30',
            masuk_istirahat: '13:00',
            pulang: '16:30'
        },
        late: {
            masuk: '08:35',
            istirahat: '11:45',
            masuk_istirahat: '13:35',
            pulang: '16:45'
        }
    };

    /**
     * Buka Modal Input Presensi Manual
     */
    function openCreateModal() {
        document.getElementById('createAttendanceModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    /**
     * Tutup Modal Input Presensi Manual
     */
    function closeCreateModal() {
        document.getElementById('createAttendanceModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    /**
     * Menu Instan 1 Hari: Mengisi otomatis seluruh sesi dalam 1 kali klik tombol.
     * Pilihan: Tepat Waktu Semua, Terlambat Semua, Izin Semua, atau Sakit Semua.
     */
    function applyPreset(presetType) {
        // 1. Ambil elemen input tanggal dari formulir modal
        const dateInput = document.getElementById('create_tanggal');
        // 2. Ambil nilai tanggal yang sedang dipilih (format YYYY-MM-DD)
        const dateStr = dateInput ? dateInput.value : '';
        // 3. Tentukan variabel penanda apakah hari tersebut merupakan hari Jumat
        let isFriday = false;
        if (dateStr) {
            const d = new Date(dateStr);
            // Angka 5 pada getDay() adalah hari Jumat (0: Minggu, 1: Senin, ..., 5: Jumat)
            if (d.getDay() === 5) isFriday = true;
        }

        // 4. Tentukan jam kerja acuan (Jumat istirahat 11:30, Senin-Kamis istirahat 12:00)
        const baseHours = isFriday ? scheduleHours.friday : scheduleHours.normal;

        // 5. Centang otomatis ke-4 checkbox sesi presensi
        document.getElementById('check_masuk').checked = true;
        document.getElementById('check_istirahat').checked = true;
        document.getElementById('check_masuk_istirahat').checked = true;
        document.getElementById('check_pulang').checked = true;

        // 6. Terapkan nilai jam dan status sesuai jenis tombol instan yang dipilih
        if (presetType === 'tepat_waktu') {
            // Sesi Masuk: Jam masuk standar & status tepat waktu
            document.getElementById('session_jam_masuk').value = baseHours.masuk;
            document.getElementById('session_status_masuk').value = 'tepat_waktu';

            // Sesi Istirahat: Jam istirahat standar & status tepat waktu
            document.getElementById('session_jam_istirahat').value = baseHours.istirahat;
            document.getElementById('session_status_istirahat').value = 'tepat_waktu';

            // Sesi Masuk Istirahat: Jam masuk istirahat standar & status tepat waktu
            document.getElementById('session_jam_masuk_istirahat').value = baseHours.masuk_istirahat;
            document.getElementById('session_status_masuk_istirahat').value = 'tepat_waktu';

            // Sesi Pulang: Jam pulang standar & status tepat waktu
            document.getElementById('session_jam_pulang').value = baseHours.pulang;
            document.getElementById('session_status_pulang').value = 'tepat_waktu';

            // Isi catatan ringkasan otomatis
            document.getElementById('create_catatan_operator').value = 'Hadir lengkap 1 hari (tepat waktu)';
        } else if (presetType === 'terlambat') {
            // Sesi Masuk: Jam terlambat (08:35) & status terlambat
            document.getElementById('session_jam_masuk').value = scheduleHours.late.masuk;
            document.getElementById('session_status_masuk').value = 'terlambat';

            // Sesi Istirahat: Jam istirahat standar & status lebih awal
            document.getElementById('session_jam_istirahat').value = baseHours.istirahat;
            document.getElementById('session_status_istirahat').value = 'lebih_awal';

            // Sesi Masuk Istirahat: Jam terlambat (13:35) & status terlambat
            document.getElementById('session_jam_masuk_istirahat').value = scheduleHours.late.masuk_istirahat;
            document.getElementById('session_status_masuk_istirahat').value = 'terlambat';

            // Sesi Pulang: Jam pulang lebih awal (16:45) & status lebih awal
            document.getElementById('session_jam_pulang').value = scheduleHours.late.pulang;
            document.getElementById('session_status_pulang').value = 'lebih_awal';

            // Isi catatan ringkasan otomatis
            document.getElementById('create_catatan_operator').value = 'Hadir terlambat';
        } else if (presetType === 'izin') {
            // Sesi Masuk: Status diset IZIN
            document.getElementById('session_jam_masuk').value = baseHours.masuk;
            document.getElementById('session_status_masuk').value = 'izin';

            // Sesi Istirahat: Status diset IZIN
            document.getElementById('session_jam_istirahat').value = baseHours.istirahat;
            document.getElementById('session_status_istirahat').value = 'izin';

            // Sesi Masuk Istirahat: Status diset IZIN
            document.getElementById('session_jam_masuk_istirahat').value = baseHours.masuk_istirahat;
            document.getElementById('session_status_masuk_istirahat').value = 'izin';

            // Sesi Pulang: Status diset IZIN
            document.getElementById('session_jam_pulang').value = baseHours.pulang;
            document.getElementById('session_status_pulang').value = 'izin';

            // Isi catatan ringkasan otomatis
            document.getElementById('create_catatan_operator').value = 'Izin tidak hadir 1 hari';
        } else if (presetType === 'sakit') {
            // Sesi Masuk: Status diset SAKIT
            document.getElementById('session_jam_masuk').value = baseHours.masuk;
            document.getElementById('session_status_masuk').value = 'sakit';

            // Sesi Istirahat: Status diset SAKIT
            document.getElementById('session_jam_istirahat').value = baseHours.istirahat;
            document.getElementById('session_status_istirahat').value = 'sakit';

            // Sesi Masuk Istirahat: Status diset SAKIT
            document.getElementById('session_jam_masuk_istirahat').value = baseHours.masuk_istirahat;
            document.getElementById('session_status_masuk_istirahat').value = 'sakit';

            // Sesi Pulang: Status diset SAKIT
            document.getElementById('session_jam_pulang').value = baseHours.pulang;
            document.getElementById('session_status_pulang').value = 'sakit';

            // Isi catatan ringkasan otomatis
            document.getElementById('create_catatan_operator').value = 'Sakit tidak hadir 1 hari';
        }
    }

    /**
     * Preview berkas gambar sebelum diupload
     */
    function previewImageInput(input, targetImgId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(targetImgId).src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    /**
     * Buka Modal Edit Presensi & Isi Data dari Server (AJAX JSON)
     */
    function openEditModal(attendanceId) {
        Swal.fire({
            title: 'Memuat data...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(`/operator/attendances/${attendanceId}/json`)
            .then(res => res.json())
            .then(res => {
                Swal.close();
                if (!res.success) {
                    Swal.fire('Gagal', 'Tidak dapat memuat data presensi.', 'error');
                    return;
                }

                const data = res.data;
                const form = document.getElementById('formEditAttendance');
                form.action = `/operator/attendances/${data.id}`;

                // Set user info
                document.getElementById('editEmployeeName').innerText = data.user_name;
                document.getElementById('editEmployeeNip').innerText = data.user_identitas;
                document.getElementById('editEmployeeJabatan').innerText = data.user_jabatan || 'Pegawai';
                document.getElementById('editEmployeeInitial').innerText = (data.user_name || 'PG').substring(0, 2).toUpperCase();
                document.getElementById('editEmployeeInfoSub').innerText = `${data.user_name} • Sesi ${data.tipe_label}`;

                // Form values
                document.getElementById('edit_tanggal').value = data.tanggal;
                document.getElementById('edit_tipe').value = data.tipe;
                document.getElementById('edit_jam').value = data.jam;
                document.getElementById('edit_status').value = data.status;
                document.getElementById('edit_approval_status').value = data.approval_status;
                document.getElementById('edit_catatan_operator').value = data.catatan_operator || '';
                document.getElementById('edit_current_foto').src = data.foto_url;

                document.getElementById('editAttendanceModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            })
            .catch(err => {
                Swal.close();
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan saat memuat data presensi.', 'error');
            });
    }

    /**
     * Tutup Modal Edit Presensi
     */
    function closeEditModal() {
        document.getElementById('editAttendanceModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    /**
     * Buka Modal Rincian Bukti Presensi
     */
    function showAttendanceDetail(attendanceId) {
        Swal.fire({
            title: 'Memuat detail...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(`/operator/attendances/${attendanceId}/json`)
            .then(res => res.json())
            .then(res => {
                Swal.close();
                if (!res.success) return;

                const data = res.data;
                document.getElementById('detailFoto').src = data.foto_url;
                document.getElementById('detailName').innerText = `${data.user_name} (${data.user_identitas})`;
                document.getElementById('detailTime').innerText = `${data.tipe_label} • ${data.tanggal_formatted} pukul ${data.jam} WIB`;
                document.getElementById('detailAlamat').innerText = data.alamat || 'Alamat tidak tersedia';

                const badge = document.getElementById('detailStatusBadge');
                if (data.status === 'tepat_waktu') {
                    badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300';
                    badge.innerText = 'Tepat Waktu';
                } else if (data.status === 'terlambat') {
                    badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300';
                    badge.innerText = 'Terlambat';
                } else if (data.status === 'izin') {
                    badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-300';
                    badge.innerText = 'Izin';
                } else if (data.status === 'sakit') {
                    badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300';
                    badge.innerText = 'Sakit';
                } else {
                    badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800 border border-teal-300';
                    badge.innerText = 'Lebih Awal';
                }

                const catBox = document.getElementById('detailCatatanBox');
                if (data.catatan_operator) {
                    catBox.classList.remove('hidden');
                    document.getElementById('detailCatatanText').innerText = data.catatan_operator;
                } else {
                    catBox.classList.add('hidden');
                }

                document.getElementById('detailModal').classList.remove('hidden');
            })
            .catch(err => {
                Swal.close();
                console.error(err);
            });
    }

    /**
     * Tutup Modal Rincian
     */
    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    /**
     * Handler Penolakan Presensi (Reject)
     */
    function handleReject(id, name) {
        Swal.fire({
            title: 'Tolak Presensi?',
            text: `Presensi pegawai "${name}" akan diubah menjadi DITOLAK (Dianggap ALFA). Masukkan alasan penolakan:`,
            input: 'text',
            inputValue: 'Wajah foto tidak sesuai / tidak valid',
            inputPlaceholder: 'Tuliskan alasan penolakan...',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Tolak Presensi',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan wajib diisi!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('rejectForm');
                form.action = `/operator/attendances/${id}/reject`;
                document.getElementById('rejectCatatan').value = result.value;
                form.submit();
            }
        });
    }

    /**
     * Handler Persetujuan Presensi (Approve)
     */
    function handleApprove(id, name) {
        Swal.fire({
            title: 'Setujui Presensi?',
            text: `Pulihkan status presensi pegawai "${name}" menjadi DITERIMA?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('approveForm');
                form.action = `/operator/attendances/${id}/approve`;
                form.submit();
            }
        });
    }

    /**
     * Konfirmasi Hapus Presensi
     */
    function confirmDeleteAttendance(id, name, tipeLabel, tanggal) {
        Swal.fire({
            title: 'Hapus Presensi?',
            text: `Apakah Anda yakin ingin menghapus data presensi ${tipeLabel} (${tanggal}) milik "${name}"? Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Permanen',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteForm');
                form.action = `/operator/attendances/${id}`;
                form.submit();
            }
        });
    }

    // Tangani perubahan tanggal di modal input manual
    function handleDateChange(dateStr) {
        if (!dateStr) return;
        const dateObj = new Date(dateStr);
        const dayOfWeek = dateObj.getDay(); // 5 is Friday
        const istirahatInput = document.getElementById('session_jam_istirahat');
        const pulangInput = document.getElementById('session_jam_pulang');

        if (dayOfWeek === 5) {
            if (istirahatInput) istirahatInput.value = '11:30';
            if (pulangInput) pulangInput.value = '16:30';
        } else {
            if (istirahatInput) istirahatInput.value = '12:00';
            if (pulangInput) pulangInput.value = '17:00';
        }
    }
</script>
@endpush

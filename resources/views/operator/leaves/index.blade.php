@extends('layouts.app')

@section('title', 'Manajemen Cuti Pegawai - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Button -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                    Modul Cuti
                </span>
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="text-xs font-bold text-slate-500">Pengadilan Tinggi Pontianak</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 flex items-center gap-2 mt-1">
                <i class="fa-solid fa-calendar-check text-emerald-700"></i> Manajemen Cuti Pegawai
            </h1>
            <p class="text-xs text-slate-500 mt-1">Kelola dan pantau daftar Cuti Tahunan, Cuti Sakit, Cuti Luar Negeri, dan izin dinas pegawai.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="openCreateModal()"
                class="px-4 py-2.5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-2 border border-emerald-700">
                <i class="fa-solid fa-plus text-amber-300 text-sm"></i> Catat Cuti Pegawai
            </button>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Total Semua -->
        <a href="{{ route('operator.leaves.index') }}" class="bg-white p-4 rounded-2xl border {{ !$jenis_cuti && !$status ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200' }} shadow-sm hover:border-emerald-400 transition block">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-base">
                    <i class="fa-solid fa-list-ul"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-400">Total</span>
            </div>
            <div class="mt-2 text-xl font-black text-slate-900">{{ number_format($totalAllLeaves) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Semua Catatan</div>
        </a>

        <!-- Cuti Tahunan -->
        <a href="{{ route('operator.leaves.index', ['jenis_cuti' => 'cuti_tahunan']) }}" class="bg-white p-4 rounded-2xl border {{ $jenis_cuti === 'cuti_tahunan' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200' }} shadow-sm hover:border-emerald-400 transition block">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-base border border-emerald-100">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <span class="text-[10px] font-bold text-emerald-600">Disetujui</span>
            </div>
            <div class="mt-2 text-xl font-black text-emerald-700">{{ number_format($totalCutiTahunan) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Cuti Tahunan</div>
        </a>

        <!-- Cuti Sakit -->
        <a href="{{ route('operator.leaves.index', ['jenis_cuti' => 'cuti_sakit']) }}" class="bg-white p-4 rounded-2xl border {{ $jenis_cuti === 'cuti_sakit' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200' }} shadow-sm hover:border-amber-400 transition block">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base border border-amber-100">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <span class="text-[10px] font-bold text-amber-600">Disetujui</span>
            </div>
            <div class="mt-2 text-xl font-black text-amber-700">{{ number_format($totalCutiSakit) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Cuti Sakit</div>
        </a>

        <!-- Cuti Luar Negeri -->
        <a href="{{ route('operator.leaves.index', ['jenis_cuti' => 'cuti_luar_negeri']) }}" class="bg-white p-4 rounded-2xl border {{ $jenis_cuti === 'cuti_luar_negeri' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'border-slate-200' }} shadow-sm hover:border-sky-400 transition block">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-base border border-sky-100">
                    <i class="fa-solid fa-plane-departure"></i>
                </div>
                <span class="text-[10px] font-bold text-sky-600">Disetujui</span>
            </div>
            <div class="mt-2 text-xl font-black text-sky-700">{{ number_format($totalCutiLuarNegeri) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Cuti Luar Negeri</div>
        </a>

        <!-- Menunggu Konfirmasi -->
        <a href="{{ route('operator.leaves.index', ['status' => 'menunggu']) }}" class="bg-white p-4 rounded-2xl border {{ $status === 'menunggu' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200' }} shadow-sm hover:border-rose-400 transition block">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-base border border-rose-100">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                @if($totalMenunggu > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black bg-rose-600 text-white animate-pulse">Butuh Aksi</span>
                @endif
            </div>
            <div class="mt-2 text-xl font-black text-rose-700">{{ number_format($totalMenunggu) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Menunggu Approval</div>
        </a>

        <!-- Sedang Cuti Hari Ini -->
        <div class="bg-gradient-to-br from-emerald-800 to-emerald-950 p-4 rounded-2xl border border-emerald-700 text-white shadow-sm">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-emerald-700/80 text-amber-300 flex items-center justify-center font-bold text-base border border-emerald-600">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <span class="text-[10px] font-bold text-emerald-200">Hari Ini</span>
            </div>
            <div class="mt-2 text-xl font-black text-amber-300">{{ number_format($totalActiveToday) }}</div>
            <div class="text-[11px] text-emerald-100 font-semibold truncate">Pegawai Aktif Cuti</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <form method="GET" action="{{ route('operator.leaves.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            <!-- Jenis Cuti -->
            <div>
                <label for="jenis_cuti" class="block text-[11px] font-bold text-slate-700 mb-1">
                    <i class="fa-solid fa-tag text-emerald-700 me-1"></i> Jenis Cuti:
                </label>
                <select name="jenis_cuti" id="jenis_cuti" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Jenis Cuti --</option>
                    <option value="cuti_tahunan" {{ $jenis_cuti === 'cuti_tahunan' ? 'selected' : '' }}>Cuti Tahunan</option>
                    <option value="cuti_sakit" {{ $jenis_cuti === 'cuti_sakit' ? 'selected' : '' }}>Cuti Sakit</option>
                    <option value="cuti_luar_negeri" {{ $jenis_cuti === 'cuti_luar_negeri' ? 'selected' : '' }}>Cuti Luar Negeri</option>
                    <option value="cuti_alasan_penting" {{ $jenis_cuti === 'cuti_alasan_penting' ? 'selected' : '' }}>Cuti Alasan Penting</option>
                    <option value="cuti_lainnya" {{ $jenis_cuti === 'cuti_lainnya' ? 'selected' : '' }}>Cuti Lainnya</option>
                </select>
            </div>

            <!-- Status Approval -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-700 mb-1">
                    <i class="fa-solid fa-stamp text-emerald-700 me-1"></i> Status Approval:
                </label>
                <select name="status" id="status" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Status --</option>
                    <option value="disetujui" {{ $status === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                    <option value="ditolak" {{ $status === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <!-- Pegawai -->
            <div>
                <label for="user_id" class="block text-[11px] font-bold text-slate-700 mb-1">
                    <i class="fa-solid fa-user text-emerald-700 me-1"></i> Pegawai:
                </label>
                <select name="user_id" id="user_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Pegawai --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $user_id == $emp->id ? 'selected' : '' }}>
                            {{ $emp->tipe_identitas_label }}: {{ $emp->nip }} - {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button type="submit" class="w-full py-2.5 px-3 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow transition border border-emerald-700 flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter text-amber-300"></i> Terapkan Filter
                </button>
                <a href="{{ route('operator.leaves.index') }}" class="py-2.5 px-3 bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold rounded-xl text-xs transition text-center" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Leaves Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                        <th class="py-3.5 px-4">No</th>
                        <th class="py-3.5 px-4">Pegawai (NIP &amp; Nama)</th>
                        <th class="py-3.5 px-4">Jenis Cuti</th>
                        <th class="py-3.5 px-4">Rentang Tanggal</th>
                        <th class="py-3.5 px-4 text-center">Durasi</th>
                        <th class="py-3.5 px-4">Alasan / Dokumen</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi Operator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($leaves as $index => $leave)
                        @php
                            $badge = \App\Models\Leave::getJenisCutiBadge($leave->jenis_cuti);
                            $isCurrentlyActive = $leave->status === 'disetujui' &&
                                \Carbon\Carbon::today()->between(
                                    \Carbon\Carbon::parse($leave->tanggal_mulai),
                                    \Carbon\Carbon::parse($leave->tanggal_selesai)
                                );
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $isCurrentlyActive ? 'bg-emerald-50/40' : '' }}">
                            <td class="py-3.5 px-4 font-bold text-slate-500 font-mono">
                                {{ ($leaves->currentPage() - 1) * $leaves->perPage() + $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-amber-700 font-mono text-[11px]">{{ $leave->user->tipe_identitas_label ?? 'NIP' }}: {{ $leave->user->nip ?? '-' }}</div>
                                <div class="font-black text-slate-900 flex items-center gap-1.5">
                                    {{ $leave->user->name ?? 'N/A' }}
                                    @if($isCurrentlyActive)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-600 text-white uppercase tracking-wider">Aktif Hari Ini</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-500">{{ $leave->user->jabatan ?? 'Pegawai' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold border inline-flex items-center gap-1.5 {{ $badge['bg'] }}">
                                    <i class="{{ $badge['icon'] }}"></i> {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div class="font-bold text-slate-900">
                                    {{ \Carbon\Carbon::parse($leave->tanggal_mulai)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($leave->tanggal_selesai)->format('d/m/Y') }}
                                </div>
                                <div class="text-[11px] text-slate-500 font-sans">
                                    {{ \Carbon\Carbon::parse($leave->tanggal_mulai)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($leave->tanggal_selesai)->translatedFormat('d M Y') }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold font-mono">
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ $leave->jumlah_hari }} Hari
                                </span>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="text-slate-800 truncate" title="{{ $leave->alasan }}">{{ $leave->alasan ?: '-' }}</div>
                                @if($leave->dokumen_pendukung)
                                    <div class="mt-1">
                                        <a href="{{ asset($leave->dokumen_pendukung) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            <i class="fa-solid fa-paperclip"></i> Lihat Dokumen Pendukung
                                        </a>
                                    </div>
                                @endif
                                @if($leave->catatan_operator)
                                    <div class="text-[10px] text-rose-600 font-medium italic mt-0.5">Catatan: "{{ $leave->catatan_operator }}"</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($leave->status === 'disetujui')
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white font-bold text-[10px] uppercase shadow-sm inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> Disetujui
                                    </span>
                                @elseif($leave->status === 'menunggu')
                                    <span class="px-2.5 py-1 rounded-full bg-amber-500 text-white font-bold text-[10px] uppercase shadow-sm inline-flex items-center gap-1">
                                        <i class="fa-solid fa-clock"></i> Menunggu
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white font-bold text-[10px] uppercase shadow-sm inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    @if($leave->status === 'menunggu' || $leave->status === 'ditolak')
                                        <form action="{{ route('operator.leaves.approve', $leave->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold border border-emerald-300 rounded-lg text-xs transition" title="Setujui Cuti">
                                                <i class="fa-solid fa-check text-sm"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($leave->status === 'menunggu' || $leave->status === 'disetujui')
                                        <button type="button" onclick="openRejectModal('{{ $leave->id }}', '{{ addslashes($leave->user->name ?? 'Pegawai') }}')" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold border border-rose-300 rounded-lg text-xs transition" title="Tolak Cuti">
                                            <i class="fa-solid fa-ban text-sm"></i>
                                        </button>
                                    @endif

                                    <button type="button" onclick='openEditModal(@json($leave))' class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs transition" title="Edit Data Cuti">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </button>

                                    <form action="{{ route('operator.leaves.destroy', $leave->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data cuti ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-slate-100 hover:bg-rose-100 text-slate-500 hover:text-rose-700 font-bold rounded-lg text-xs transition" title="Hapus">
                                            <i class="fa-solid fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-calendar-xmark text-4xl block mb-2 text-slate-300"></i>
                                Tidak ada data cuti pegawai yang sesuai kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leaves->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $leaves->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Catat / Tambah Cuti -->
<div id="createModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl">
        <div class="bg-[#064e3b] text-white p-5 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-bold text-sm text-white flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-amber-300"></i> Catat Cuti Pegawai
            </h3>
            <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('operator.leaves.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Pegawai Select -->
            <div>
                <label for="create_user_id" class="block text-xs font-bold text-slate-700 mb-1">
                    Pilih Pegawai *
                </label>
                <select name="user_id" id="create_user_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->tipe_identitas_label }}: {{ $emp->nip }} - {{ $emp->name }} ({{ $emp->jabatan ?? 'Pegawai' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Jenis Cuti -->
            <div>
                <label for="create_jenis_cuti" class="block text-xs font-bold text-slate-700 mb-1">
                    Jenis Cuti *
                </label>
                <select name="jenis_cuti" id="create_jenis_cuti" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="cuti_tahunan">Cuti Tahunan</option>
                    <option value="cuti_sakit">Cuti Sakit</option>
                    <option value="cuti_luar_negeri">Cuti Luar Negeri</option>
                    <option value="cuti_alasan_penting">Cuti Alasan Penting</option>
                    <option value="cuti_lainnya">Cuti Lainnya</option>
                </select>
            </div>

            <!-- Rentang Tanggal -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="create_tanggal_mulai" class="block text-xs font-bold text-slate-700 mb-1">
                        Tanggal Mulai *
                    </label>
                    <input type="date" name="tanggal_mulai" id="create_tanggal_mulai" required value="{{ date('Y-m-d') }}"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label for="create_tanggal_selesai" class="block text-xs font-bold text-slate-700 mb-1">
                        Tanggal Selesai *
                    </label>
                    <input type="date" name="tanggal_selesai" id="create_tanggal_selesai" required value="{{ date('Y-m-d') }}"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <!-- Alasan -->
            <div>
                <label for="create_alasan" class="block text-xs font-bold text-slate-700 mb-1">
                    Alasan / Keterangan Cuti
                </label>
                <textarea name="alasan" id="create_alasan" rows="2" placeholder="Contoh: Cuti tahunan keluarga / istirahat sakit / izin perjalanan luar negeri..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500"></textarea>
            </div>

            <!-- Upload Dokumen Pendukung -->
            <div>
                <label for="create_dokumen" class="block text-xs font-bold text-slate-700 mb-1">
                    Upload Surat Dokter / Izin / Tiket <span class="text-slate-400 font-normal">(Tidak wajib / Opsional)</span>
                </label>
                <input type="file" name="dokumen" id="create_dokumen" accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                <p class="text-[10px] text-slate-400 mt-1">File tidak wajib diunggah (opsional jika memiliki berkas pendukung).</p>
            </div>

            <!-- Status Approval -->
            <div>
                <label for="create_status" class="block text-xs font-bold text-slate-700 mb-1">
                    Status Approval *
                </label>
                <select name="status" id="create_status" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="disetujui" selected>Disetujui (Langsung Aktif)</option>
                    <option value="menunggu">Menunggu Konfirmasi</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 py-3 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-extrabold rounded-xl text-xs shadow transition border border-emerald-700 flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk text-amber-300"></i> Simpan Data Cuti
                </button>
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Cuti -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl">
        <div class="bg-[#064e3b] text-white p-5 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-bold text-sm text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-300"></i> Edit Data Cuti Pegawai
            </h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="editForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- Pegawai Select -->
            <div>
                <label for="edit_user_id" class="block text-xs font-bold text-slate-700 mb-1">
                    Pilih Pegawai *
                </label>
                <select name="user_id" id="edit_user_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->tipe_identitas_label }}: {{ $emp->nip }} - {{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Jenis Cuti -->
            <div>
                <label for="edit_jenis_cuti" class="block text-xs font-bold text-slate-700 mb-1">
                    Jenis Cuti *
                </label>
                <select name="jenis_cuti" id="edit_jenis_cuti" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="cuti_tahunan">Cuti Tahunan</option>
                    <option value="cuti_sakit">Cuti Sakit</option>
                    <option value="cuti_luar_negeri">Cuti Luar Negeri</option>
                    <option value="cuti_alasan_penting">Cuti Alasan Penting</option>
                    <option value="cuti_lainnya">Cuti Lainnya</option>
                </select>
            </div>

            <!-- Rentang Tanggal -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit_tanggal_mulai" class="block text-xs font-bold text-slate-700 mb-1">
                        Tanggal Mulai *
                    </label>
                    <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" required
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label for="edit_tanggal_selesai" class="block text-xs font-bold text-slate-700 mb-1">
                        Tanggal Selesai *
                    </label>
                    <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" required
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <!-- Alasan -->
            <div>
                <label for="edit_alasan" class="block text-xs font-bold text-slate-700 mb-1">
                    Alasan / Keterangan Cuti
                </label>
                <textarea name="alasan" id="edit_alasan" rows="2"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500"></textarea>
            </div>

            <!-- Upload Dokumen Pendukung Baru -->
            <div>
                <label for="edit_dokumen" class="block text-xs font-bold text-slate-700 mb-1">
                    Ganti Dokumen Pendukung <span class="text-slate-400 font-normal">(Kosongkan jika tidak diubah)</span>
                </label>
                <input type="file" name="dokumen" id="edit_dokumen" accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            </div>

            <!-- Status Approval -->
            <div>
                <label for="edit_status" class="block text-xs font-bold text-slate-700 mb-1">
                    Status Approval *
                </label>
                <select name="status" id="edit_status" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="disetujui">Disetujui</option>
                    <option value="menunggu">Menunggu Konfirmasi</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 py-3 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-extrabold rounded-xl text-xs shadow transition border border-emerald-700 flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk text-amber-300"></i> Simpan Perubahan
                </button>
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tolak Cuti -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl">
        <div class="bg-rose-700 text-white p-5 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-bold text-sm text-white flex items-center gap-2">
                <i class="fa-solid fa-ban"></i> Tolak Pengajuan Cuti
            </h3>
            <button onclick="document.getElementById('rejectModal').classList.add('hidden')" class="text-slate-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="rejectForm" method="POST" class="p-6 space-y-4">
            @csrf
            <p class="text-xs text-slate-600" id="rejectModalPrompt">Tolak pengajuan cuti pegawai?</p>
            <div>
                <label for="reject_reason" class="block text-xs font-bold text-slate-700 mb-1">
                    Alasan Penolakan:
                </label>
                <textarea name="catatan_operator" id="reject_reason" required rows="3" placeholder="Masukkan alasan penolakan..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium focus:ring-rose-500 focus:border-rose-500"></textarea>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 py-3 px-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl text-xs shadow transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-ban"></i> Tolak Pengajuan
                </button>
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }

    function openEditModal(leave) {
        document.getElementById('editForm').action = `/operator/leaves/${leave.id}`;
        document.getElementById('edit_user_id').value = leave.user_id;
        document.getElementById('edit_jenis_cuti').value = leave.jenis_cuti;
        document.getElementById('edit_tanggal_mulai').value = leave.tanggal_mulai.split('T')[0];
        document.getElementById('edit_tanggal_selesai').value = leave.tanggal_selesai.split('T')[0];
        document.getElementById('edit_alasan').value = leave.alasan || '';
        document.getElementById('edit_status').value = leave.status;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function openRejectModal(id, name) {
        document.getElementById('rejectForm').action = `/operator/leaves/${id}/reject`;
        document.getElementById('rejectModalPrompt').innerText = `Tolak pengajuan cuti untuk ${name}?`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
</script>
@endpush
@endsection

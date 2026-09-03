@extends('layouts.app')

@section('title', 'Pengajuan & Riwayat Izin / Cuti - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-[#064e3b] rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-emerald-800 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <span class="px-3.5 py-1 bg-amber-400 text-slate-950 text-xs font-black rounded-full uppercase tracking-wider mb-3 inline-block shadow">
                <i class="fa-solid fa-calendar-check me-1 text-emerald-900"></i> Portal Izin / Cuti Pegawai
            </span>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Pengajuan &amp; Riwayat Izin / Cuti
            </h1>
            <p class="text-xs sm:text-sm text-emerald-100 font-bold mt-2">
                Nama: <strong class="text-amber-300">{{ $user->name }}</strong> &bull; {{ $user->tipe_identitas_label }}: <span class="font-mono">{{ $user->nip }}</span>
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('karyawan.dashboard') }}" class="px-4 py-2.5 bg-emerald-950/80 hover:bg-emerald-900 text-white font-extrabold rounded-2xl text-xs border border-emerald-700 shadow transition flex items-center gap-2">
                <i class="fa-solid fa-camera text-amber-300"></i> Presensi Hari Ini
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- Cuti Tahunan -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl font-bold border border-emerald-100">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <div class="text-xl font-black text-emerald-700">{{ $totalCutiTahunan }} Hari</div>
                <div class="text-xs text-slate-500 font-semibold">Cuti Tahunan</div>
            </div>
        </div>

        <!-- Cuti Sakit -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-100">
                <i class="fa-solid fa-notes-medical"></i>
            </div>
            <div>
                <div class="text-xl font-black text-amber-700">{{ $totalCutiSakit }} Hari</div>
                <div class="text-xs text-slate-500 font-semibold">Cuti Sakit</div>
            </div>
        </div>

        <!-- Cuti Luar Negeri -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold border border-sky-100">
                <i class="fa-solid fa-plane-departure"></i>
            </div>
            <div>
                <div class="text-xl font-black text-sky-700">{{ $totalCutiLuarNegeri }} Hari</div>
                <div class="text-xs text-slate-500 font-semibold">Cuti Luar Negeri</div>
            </div>
        </div>

        <!-- Pengajuan Menunggu -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold border border-rose-100">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="text-xl font-black text-rose-700">{{ $totalPending }} Pengajuan</div>
                <div class="text-xs text-slate-500 font-semibold">Menunggu Persetujuan</div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Form Pengajuan (Left) & Riwayat Tabel (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Pengajuan Cuti -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fa-solid fa-file-signature text-emerald-700"></i> Formulir Pengajuan Cuti
            </h2>

            <form action="{{ route('karyawan.cuti.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <!-- Jenis Cuti -->
                <div>
                    <label for="jenis_cuti" class="block text-xs font-bold text-slate-700 mb-1">
                        Pilih Jenis Cuti *
                    </label>
                    <select name="jenis_cuti" id="jenis_cuti" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
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
                        <label for="tanggal_mulai" class="block text-xs font-bold text-slate-700 mb-1">
                            Mulai Tanggal *
                        </label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" required min="{{ date('Y-m-d') }}" value="{{ old('tanggal_mulai', date('Y-m-d')) }}"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label for="tanggal_selesai" class="block text-xs font-bold text-slate-700 mb-1">
                            Sampai Tanggal *
                        </label>
                        <input type="date" name="tanggal_selesai" id="tanggal_selesai" required min="{{ date('Y-m-d') }}" value="{{ old('tanggal_selesai', date('Y-m-d')) }}"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                <!-- Alasan Cuti -->
                <div>
                    <label for="alasan" class="block text-xs font-bold text-slate-700 mb-1">
                        Alasan / Keterangan Cuti *
                    </label>
                    <textarea name="alasan" id="alasan" required rows="3" placeholder="Jelaskan alasan pengajuan cuti Anda..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">{{ old('alasan') }}</textarea>
                </div>

                <!-- Upload Dokumen Pendukung -->
                <div>
                    <label for="dokumen" class="block text-xs font-bold text-slate-700 mb-1">
                        Lampiran Surat Dokter / Izin / Tiket <span class="text-slate-400 font-normal">(Tidak wajib / Opsional)</span>
                    </label>
                    <input type="file" name="dokumen" id="dokumen" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    <p class="text-[10px] text-slate-400 mt-1">File tidak wajib diisi / diunggah. Silakan lampirkan jika ada dokumen pendukung.</p>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-extrabold rounded-xl shadow-lg shadow-emerald-900/30 transition text-xs flex items-center justify-center gap-2 border border-emerald-700">
                    <i class="fa-solid fa-paper-plane text-amber-300 text-sm"></i> KIRIM PENGAJUAN CUTI
                </button>
            </form>
        </div>

        <!-- Tabel Riwayat Pengajuan Cuti -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-emerald-700"></i> Riwayat Pengajuan Cuti Anda
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                            <th class="py-3.5 px-4">No</th>
                            <th class="py-3.5 px-4">Jenis Cuti</th>
                            <th class="py-3.5 px-4">Periode Tanggal</th>
                            <th class="py-3.5 px-4 text-center">Durasi</th>
                            <th class="py-3.5 px-4">Alasan &amp; Bukti</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($leaves as $index => $leave)
                            @php
                                $badge = \App\Models\Leave::getJenisCutiBadge($leave->jenis_cuti);
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-500 font-mono">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 rounded-xl text-xs font-bold border inline-flex items-center gap-1.5 {{ $badge['bg'] }}">
                                        <i class="{{ $badge['icon'] }}"></i> {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="font-bold text-slate-900">
                                        {{ \Carbon\Carbon::parse($leave->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($leave->tanggal_selesai)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-sans">
                                        {{ \Carbon\Carbon::parse($leave->tanggal_mulai)->translatedFormat('d M') }} s/d {{ \Carbon\Carbon::parse($leave->tanggal_selesai)->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold font-mono">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ $leave->jumlah_hari }} Hari
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="text-slate-800">{{ $leave->alasan }}</div>
                                    @if($leave->dokumen_pendukung)
                                        <div class="mt-1">
                                            <a href="{{ asset($leave->dokumen_pendukung) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                <i class="fa-solid fa-paperclip"></i> Dokumen Terlampir
                                            </a>
                                        </div>
                                    @endif
                                    @if($leave->catatan_operator)
                                        <div class="text-[10px] text-rose-600 font-medium italic mt-0.5">Catatan Operator: "{{ $leave->catatan_operator }}"</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <i class="fa-regular fa-calendar-check text-4xl block mb-2 text-slate-300"></i>
                                    Belum ada riwayat pengajuan cuti yang tercatat.
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

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tglMulai = document.getElementById('tanggal_mulai');
        const tglSelesai = document.getElementById('tanggal_selesai');
        if (tglMulai && tglSelesai) {
            tglMulai.addEventListener('change', function() {
                if (this.value) {
                    tglSelesai.min = this.value;
                    if (tglSelesai.value && tglSelesai.value < this.value) {
                        tglSelesai.value = this.value;
                    }
                }
            });
        }
    });
</script>
@endpush

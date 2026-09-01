@extends('layouts.app')

@section('title', 'Riwayat Presensi Pegawai - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">
    <!-- Header & Filter -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-history text-emerald-700"></i> Riwayat Presensi Pegawai
            </h1>
            <p class="text-xs text-slate-500 mt-1">Daftar presensi harian beserta foto wajah &amp; koordinat lokasi GPS</p>
        </div>

        <!-- Month / Year Filter Form -->
        <form method="GET" action="{{ route('karyawan.riwayat') }}" class="flex flex-wrap items-center gap-3">
            <div>
                <select name="bulan" class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl p-2.5 focus:ring-emerald-500 focus:border-emerald-500">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ sprintf('%02d', $m) }}" {{ $month == sprintf('%02d', $m) ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="tahun" class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl p-2.5 focus:ring-emerald-500 focus:border-emerald-500">
                    @foreach(range(date('Y') - 2, date('Y') + 1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow transition border border-emerald-700 flex items-center gap-1.5">
                <i class="fa-solid fa-filter text-amber-300"></i> Filter
            </button>
        </form>
    </div>

    <!-- Attendance Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Tipe Presensi</th>
                        <th class="py-3.5 px-4">Waktu Record</th>
                        <th class="py-3.5 px-4">Status Waktu</th>
                        <th class="py-3.5 px-4">Status Operator</th>
                        <th class="py-3.5 px-4">Foto Wajah</th>
                        <th class="py-3.5 px-4">Lokasi GPS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($attendances as $att)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ \Carbon\Carbon::parse($att->tanggal)->translatedFormat('d F Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold border border-emerald-200 inline-block">
                                    {{ \App\Models\Attendance::getTipeLabel($att->tipe) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-800">
                                {{ \Carbon\Carbon::parse($att->waktu)->format('H:i:s') }} WIB
                            </td>
                            <td class="py-3.5 px-4">
                                @if($att->status === 'tepat_waktu')
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold border border-emerald-300">
                                        Tepat Waktu
                                    </span>
                                @elseif($att->status === 'terlambat')
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold border border-amber-300">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 font-bold border border-teal-300">
                                        Lebih Awal
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($att->approval_status === 'diterima')
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white font-bold text-[10px] uppercase shadow-sm inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> Diterima
                                    </span>
                                @else
                                    <div class="space-y-1">
                                        <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white font-bold text-[10px] uppercase shadow-sm inline-flex items-center gap-1">
                                            <i class="fa-solid fa-circle-xmark"></i> Ditolak (ALFA)
                                        </span>
                                        @if($att->catatan_operator)
                                            <div class="text-[10px] text-rose-600 font-medium italic">"{{ $att->catatan_operator }}"</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <button onclick="previewImage('{{ asset($att->foto) }}')" class="group relative inline-block rounded-xl overflow-hidden shadow border border-slate-200">
                                    <img src="{{ asset($att->foto) }}" alt="Foto" class="w-12 h-12 object-cover">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition text-xs">
                                        <i class="fa-solid fa-eye"></i>
                                    </div>
                                </button>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs truncate" title="{{ $att->alamat }}">
                                <div class="font-mono text-[11px] text-slate-800">
                                    <i class="fa-solid fa-location-dot text-emerald-700 me-1"></i> {{ $att->latitude }}, {{ $att->longitude }}
                                </div>
                                <div class="text-[10px] text-slate-500 truncate">{{ $att->alamat }}</div>
                                <div class="mt-1">
                                    <span class="font-mono text-[10px] text-emerald-800 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded font-semibold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-network-wired text-emerald-600"></i> IP: {{ $att->ip_address ?? '127.0.0.1' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-4xl block mb-2 text-slate-300"></i>
                                Belum ada data presensi pada bulan ini.
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

<!-- Image Preview Modal -->
<div id="imageModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl">
        <div class="p-4 bg-[#064e3b] text-white flex justify-between items-center border-b-2 border-amber-400">
            <span class="font-bold text-sm">Foto Presensi</span>
            <button onclick="document.getElementById('imageModal').classList.add('hidden')" class="text-slate-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-4 bg-slate-950 text-center">
            <img id="modalImage" src="" alt="Preview" class="max-h-96 mx-auto rounded-xl object-contain">
        </div>
    </div>
</div>

<script>
    function previewImage(url) {
        document.getElementById('modalImage').src = url;
        document.getElementById('imageModal').classList.remove('hidden');
    }
</script>
@endsection

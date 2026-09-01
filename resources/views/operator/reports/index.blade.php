@extends('layouts.app')

@section('title', 'Laporan Rekap Presensi Pegawai - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Header & Export Action -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                    Rekap Presensi
                </span>
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="text-xs font-bold text-slate-500">Periode: {{ \Carbon\Carbon::parse($tanggal_mulai)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($tanggal_selesai)->translatedFormat('d M Y') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 flex items-center gap-2 mt-1">
                <i class="fa-solid fa-file-invoice text-emerald-700"></i> Laporan Rekap Presensi Pegawai
            </h1>
            <p class="text-xs text-slate-500 mt-1">Filter rentang tanggal/tahun presensi dan export data ke Excel (.xlsx) atau cetak dokumen resmi</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Export Excel (.xlsx) -->
            <a href="{{ route('operator.reports.export', request()->all()) }}"
                class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-2 border border-emerald-600">
                <i class="fa-solid fa-file-excel text-amber-300 text-sm"></i> Export Excel (.xlsx)
            </a>

            <!-- Print Official Document -->
            <a href="{{ route('operator.reports.print', request()->all()) }}" target="_blank"
                class="px-4 py-2.5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-2 border border-emerald-700">
                <i class="fa-solid fa-print text-amber-300 text-sm"></i> Cetak Laporan Resmi
            </a>
        </div>
    </div>

    <!-- Summary Stats Range Card (Filtered) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 print:hidden">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center font-bold text-lg border border-emerald-100">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div>
                <div class="text-xl font-black text-slate-900">{{ number_format($totalPresensi) }}</div>
                <div class="text-[11px] text-slate-500 font-semibold">Total Record</div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg border border-emerald-100">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="text-xl font-black text-emerald-700">{{ number_format($totalDiterima) }}</div>
                <div class="text-[11px] text-slate-500 font-semibold">Presensi Diterima</div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg border border-amber-100">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <div class="text-xl font-black text-amber-700">{{ number_format($totalTerlambat) }}</div>
                <div class="text-[11px] text-slate-500 font-semibold">Terlambat</div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-lg border border-rose-100">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <div class="text-xl font-black text-rose-700">{{ number_format($totalDitolak) }}</div>
                <div class="text-[11px] text-slate-500 font-semibold">Ditolak (ALFA)</div>
            </div>
        </div>
    </div>

    <!-- Date Range & Criteria Filter Form Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm print:hidden space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                <i class="fa-solid fa-sliders text-emerald-700"></i> Filter Rentang Tanggal &amp; Parameter
            </h2>
            
            <!-- Quick Preset Range Buttons -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[11px] text-slate-400 font-bold me-1">Pilihan Cepat:</span>
                <button type="button" onclick="setPreset('today')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 rounded-lg text-[11px] font-bold transition">
                    Hari Ini
                </button>
                <button type="button" onclick="setPreset('month')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 rounded-lg text-[11px] font-bold transition">
                    Bulan Ini
                </button>
                <button type="button" onclick="setPreset('last_month')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 rounded-lg text-[11px] font-bold transition">
                    Bulan Lalu
                </button>
                <button type="button" onclick="setPreset('year')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 rounded-lg text-[11px] font-bold transition">
                    Tahun Ini
                </button>
            </div>
        </div>

        <form id="filterForm" method="GET" action="{{ route('operator.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
            <!-- Tanggal Mulai -->
            <div>
                <label for="tanggal_mulai" class="block text-[11px] font-bold text-slate-700 mb-1">
                    <i class="fa-regular fa-calendar text-emerald-700 me-1"></i> Dari Tanggal:
                </label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ $tanggal_mulai }}" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Tanggal Selesai -->
            <div>
                <label for="tanggal_selesai" class="block text-[11px] font-bold text-slate-700 mb-1">
                    <i class="fa-regular fa-calendar-check text-emerald-700 me-1"></i> Sampai Tanggal:
                </label>
                <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ $tanggal_selesai }}" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Pegawai Select -->
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

            <!-- Tipe Presensi -->
            <div>
                <label for="tipe" class="block text-[11px] font-bold text-slate-700 mb-1">
                    <i class="fa-solid fa-clock text-emerald-700 me-1"></i> Tipe Sesi:
                </label>
                <select name="tipe" id="tipe" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Sesi --</option>
                    <option value="masuk" {{ $tipe == 'masuk' ? 'selected' : '' }}>Jam Masuk</option>
                    <option value="istirahat" {{ $tipe == 'istirahat' ? 'selected' : '' }}>Jam Istirahat</option>
                    <option value="masuk_istirahat" {{ $tipe == 'masuk_istirahat' ? 'selected' : '' }}>Jam Masuk Istirahat</option>
                    <option value="pulang" {{ $tipe == 'pulang' ? 'selected' : '' }}>Jam Pulang</option>
                </select>
            </div>

            <!-- Status Approval -->
            <div>
                <label for="approval_status" class="block text-[11px] font-bold text-slate-700 mb-1">
                    <i class="fa-solid fa-stamp text-emerald-700 me-1"></i> Status Approval:
                </label>
                <select name="approval_status" id="approval_status" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Semua Status --</option>
                    <option value="diterima" {{ $approval_status == 'diterima' ? 'selected' : '' }}>Diterima</option>
                    <option value="ditolak" {{ $approval_status == 'ditolak' ? 'selected' : '' }}>Ditolak (ALFA)</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <button type="submit" class="w-full py-2.5 px-3 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow transition border border-emerald-700 flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter text-amber-300"></i> Terapkan
                </button>
                <a href="{{ route('operator.reports.index') }}" class="py-2.5 px-3 bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold rounded-xl text-xs transition text-center" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Print Title Header (Only visible when printing) -->
    <div class="hidden print:block text-center border-b-2 border-slate-800 pb-4 mb-6">
        <div class="flex items-center justify-center gap-4 mb-2">
            <img src="{{ asset('LOGO-PPTK.png') }}" alt="Logo" class="w-16 h-16 object-contain">
            <div>
                <h1 class="text-xl font-black uppercase tracking-wider text-slate-900 leading-tight">PENGADILAN TINGGI PONTIANAK</h1>
                <h2 class="text-base font-bold text-slate-700 leading-tight">Laporan Rekapitulasi Presensi Pegawai</h2>
            </div>
        </div>
        <p class="text-xs text-slate-600 mt-1">
            Periode: <strong>{{ \Carbon\Carbon::parse($tanggal_mulai)->translatedFormat('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($tanggal_selesai)->translatedFormat('d F Y') }}</strong>
        </p>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                        <th class="py-3.5 px-4">No</th>
                        <th class="py-3.5 px-4">Tanggal &amp; Waktu</th>
                        <th class="py-3.5 px-4">NIP &amp; Nama Pegawai</th>
                        <th class="py-3.5 px-4">Tipe Presensi</th>
                        <th class="py-3.5 px-4">Status Waktu</th>
                        <th class="py-3.5 px-4">Status Approval</th>
                        <th class="py-3.5 px-4 print:hidden">Foto Wajah</th>
                        <th class="py-3.5 px-4">Lokasi GPS / Alamat</th>
                        <th class="py-3.5 px-4 print:hidden text-center">Aksi Operator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($attendances as $index => $att)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-500 font-mono">
                                {{ ($attendances->currentPage() - 1) * $attendances->perPage() + $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($att->tanggal)->format('d/m/Y') }}</div>
                                <div class="text-[11px] text-slate-500 font-sans font-semibold">{{ \Carbon\Carbon::parse($att->tanggal)->translatedFormat('l') }} &bull; {{ \Carbon\Carbon::parse($att->waktu)->format('H:i:s') }} WIB</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-amber-700 font-mono text-[11px]">{{ $att->user->tipe_identitas_label ?? 'NIP' }}: {{ $att->user->nip ?? '-' }}</div>
                                <div class="font-black text-slate-900">{{ $att->user->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-500">{{ $att->user->jabatan ?? 'Pegawai' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold border border-emerald-200 inline-block">
                                    {{ \App\Models\Attendance::getTipeLabel($att->tipe) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($att->status === 'tepat_waktu')
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold border border-emerald-300">Tepat Waktu</span>
                                @elseif($att->status === 'terlambat')
                                    <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold border border-amber-300">Terlambat</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-teal-100 text-teal-800 font-bold border border-teal-300">Lebih Awal</span>
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
                            <td class="py-3.5 px-4 print:hidden">
                                <button onclick="viewDetail('{{ asset($att->foto) }}', '{{ $att->latitude }}', '{{ $att->longitude }}', '{{ $att->alamat }}', '{{ addslashes($att->user->name ?? 'N/A') }}', '{{ \App\Models\Attendance::getTipeLabel($att->tipe) }}', '{{ $att->id }}', '{{ $att->approval_status }}')"
                                    class="group relative inline-block rounded-xl overflow-hidden shadow border border-slate-200">
                                    <img src="{{ asset($att->foto) }}" alt="Foto" class="w-10 h-10 object-cover">
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
                            <td class="py-3.5 px-4 print:hidden text-center">
                                @if($att->approval_status === 'diterima')
                                    <button type="button" onclick="handleReject(this)" data-id="{{ $att->id }}" data-name="{{ e($att->user->name ?? 'Pegawai') }}" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold border border-rose-300 rounded-xl text-xs transition inline-flex items-center gap-1 shadow-sm">
                                        <i class="fa-solid fa-ban"></i> Tolak
                                    </button>
                                @else
                                    <button type="button" onclick="handleApprove(this)" data-id="{{ $att->id }}" data-name="{{ e($att->user->name ?? 'Pegawai') }}" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold border border-emerald-300 rounded-xl text-xs transition inline-flex items-center gap-1 shadow-sm">
                                        <i class="fa-solid fa-circle-check"></i> Setujui
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-4xl block mb-2 text-slate-300"></i>
                                Tidak ada data presensi yang sesuai dengan rentang tanggal dan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100 print:hidden">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>

<!-- View Detail Modal with Leaflet Map -->
<div id="detailModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 print:hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl">
        <div class="bg-[#064e3b] text-white p-4 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-bold text-sm text-white" id="modalTitle">Detail Presensi</h3>
            <button onclick="document.getElementById('detailModal').classList.add('hidden')" class="text-slate-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-5 space-y-4">
            <div class="rounded-2xl overflow-hidden shadow border border-slate-200 bg-slate-900 text-center">
                <img id="modalPhoto" src="" alt="Foto Absensi" class="w-full max-h-64 object-cover">
            </div>
            <div id="reportLeafletMap" class="w-full h-44 rounded-xl border border-slate-200"></div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs space-y-1">
                <div class="font-bold text-slate-800" id="modalUserInfo"></div>
                <div class="text-slate-600" id="modalAddressInfo"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let reportMap = null;
    let reportMarker = null;

    function setPreset(type) {
        const now = new Date();
        const startInput = document.getElementById('tanggal_mulai');
        const endInput = document.getElementById('tanggal_selesai');

        const formatDate = (d) => {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        };

        if (type === 'today') {
            startInput.value = formatDate(now);
            endInput.value = formatDate(now);
        } else if (type === 'month') {
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            startInput.value = formatDate(firstDay);
            endInput.value = formatDate(now);
        } else if (type === 'last_month') {
            const firstDayLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);
            startInput.value = formatDate(firstDayLastMonth);
            endInput.value = formatDate(lastDayLastMonth);
        } else if (type === 'year') {
            const firstDayYear = new Date(now.getFullYear(), 0, 1);
            startInput.value = formatDate(firstDayYear);
            endInput.value = formatDate(now);
        }

        document.getElementById('filterForm').submit();
    }

    function viewDetail(photoUrl, lat, lng, alamat, empName, tipeLabel) {
        document.getElementById('modalTitle').innerText = 'Presensi: ' + empName + ' (' + tipeLabel + ')';
        document.getElementById('modalPhoto').src = photoUrl;
        document.getElementById('modalUserInfo').innerText = 'Pegawai: ' + empName;
        document.getElementById('modalAddressInfo').innerText = 'Lokasi: ' + alamat + ' (' + lat + ', ' + lng + ')';
        document.getElementById('detailModal').classList.remove('hidden');

        setTimeout(() => {
            if (!reportMap) {
                reportMap = L.map('reportLeafletMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(reportMap);
                reportMarker = L.marker([lat, lng]).addTo(reportMap);
            } else {
                reportMap.invalidateSize();
                reportMap.setView([lat, lng], 16);
                reportMarker.setLatLng([lat, lng]);
            }
        }, 300);
    }

    function handleReject(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        
        if (typeof Swal === 'undefined') {
            const reason = prompt('Tolak presensi untuk ' + name + '? Masukkan alasan penolakan:', 'Wajah foto tidak sesuai pegawai');
            if (reason !== null) {
                submitRejectForm(id, reason);
            }
            return;
        }

        Swal.fire({
            title: 'Tolak Presensi?',
            text: 'Masukkan alasan penolakan presensi untuk ' + name + ' (Wajah foto tidak sesuai / tidak valid). Presensi akan diubah menjadi DITOLAK (Dianggap ALFA).',
            input: 'text',
            inputValue: 'Wajah foto tidak sesuai pegawai',
            inputPlaceholder: 'Alasan penolakan...',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Tolak Presensi (ALFA)',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                submitRejectForm(id, result.value || 'Wajah foto tidak sesuai pegawai');
            }
        });
    }

    function submitRejectForm(id, reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/operator/attendances/${id}/reject`;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);

        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'catatan_operator';
        reasonInput.value = reason;
        form.appendChild(reasonInput);

        document.body.appendChild(form);
        form.submit();
    }

    function handleApprove(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        
        if (typeof Swal === 'undefined') {
            if (confirm('Setujui kembali presensi untuk ' + name + '?')) {
                submitApproveForm(id);
            }
            return;
        }

        Swal.fire({
            title: 'Setujui Presensi?',
            text: 'Pulihkan status presensi untuk ' + name + ' menjadi DITERIMA.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#064e3b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Setujui Presensi',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                submitApproveForm(id);
            }
        });
    }

    function submitApproveForm(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/operator/attendances/${id}/approve`;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
@endsection

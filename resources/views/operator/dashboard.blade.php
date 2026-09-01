@extends('layouts.app')

@section('title', 'Dashboard Operator - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Header Banner with PPTK Deep Green & Gold Theme -->
    <div class="bg-[#064e3b] rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-emerald-800 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <span class="px-3.5 py-1 bg-amber-400 text-slate-950 text-xs font-black rounded-full uppercase tracking-wider mb-3 inline-block shadow">
                <i class="fa-solid fa-user-gear me-1 text-emerald-900"></i> Dashboard Operator
            </span>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Ringkasan Presensi Hari Ini
            </h1>
            <p class="text-xs sm:text-sm text-emerald-100 font-bold mt-2">
                Tanggal: <strong class="text-amber-300 bg-emerald-950/80 px-3 py-1 rounded-xl border border-emerald-800 font-mono">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</strong>
            </p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('operator.schedules.index') }}" class="px-4 py-2.5 bg-amber-400 hover:bg-amber-500 text-slate-950 font-black rounded-2xl text-xs shadow-lg transition flex items-center gap-2">
                <i class="fa-solid fa-clock text-emerald-900"></i> Atur Jam Kerja (Per Hari)
            </a>
            <a href="{{ route('operator.employees.index') }}" class="px-4 py-2.5 bg-emerald-950/80 hover:bg-emerald-900 text-white font-extrabold rounded-2xl text-xs border border-emerald-700 shadow transition flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-amber-300"></i> Kelola Pegawai
            </a>
        </div>
    </div>

    <!-- Pending Leave Alert (if any) -->
    @if(isset($pendingLeavesCount) && $pendingLeavesCount > 0)
        <div class="bg-rose-50 border-2 border-rose-300 rounded-2xl p-4 flex items-center justify-between gap-4 text-rose-950">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold text-lg animate-pulse">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-rose-900">Ada {{ $pendingLeavesCount }} Pengajuan Cuti Menunggu Persetujuan</h3>
                    <p class="text-xs text-rose-700">Terdapat pengajuan cuti pegawai yang membutuhkan verifikasi / persetujuan dari Operator.</p>
                </div>
            </div>
            <a href="{{ route('operator.leaves.index', ['status' => 'menunggu']) }}" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow transition flex items-center gap-1.5 whitespace-nowrap">
                <i class="fa-solid fa-check-double"></i> Tinjau Cuti
            </a>
        </div>
    @endif

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg font-bold border border-emerald-100">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900">{{ $totalKaryawan }}</div>
                <div class="text-[11px] text-slate-500 font-semibold truncate">Total Pegawai</div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg font-bold border border-emerald-100">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900">{{ $countMasuk }}</div>
                <div class="text-[11px] text-slate-500 font-semibold truncate">Jam Masuk</div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold border border-amber-100">
                <i class="fa-solid fa-mug-hot"></i>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900">{{ $countIstirahat }}</div>
                <div class="text-[11px] text-slate-500 font-semibold truncate">Istirahat</div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-11 h-11 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg font-bold border border-teal-100">
                <i class="fa-solid fa-briefcase"></i>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900">{{ $countMasukIstirahat }}</div>
                <div class="text-[11px] text-slate-500 font-semibold truncate">Masuk Istirahat</div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg font-bold border border-emerald-100">
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900">{{ $countPulang }}</div>
                <div class="text-[11px] text-slate-500 font-semibold truncate">Jam Pulang</div>
            </div>
        </div>

        <!-- Cuti Hari Ini Stat Card -->
        <a href="{{ route('operator.leaves.index') }}" class="bg-gradient-to-br from-emerald-800 to-emerald-950 p-4 sm:p-5 rounded-2xl border border-emerald-700 text-white shadow-sm flex items-center space-x-3 hover:opacity-95 transition">
            <div class="w-11 h-11 rounded-2xl bg-emerald-700/80 text-amber-300 flex items-center justify-center text-lg font-bold border border-emerald-600">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-amber-300">{{ isset($todayLeaves) ? $todayLeaves->count() : 0 }}</div>
                <div class="text-[11px] text-emerald-200 font-semibold truncate">Cuti Hari Ini</div>
            </div>
        </a>
    </div>

    <!-- Active Windows Status Panel -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clock text-emerald-700"></i> Status Jendela Waktu Buka/Tutup Presensi Hari Ini
            </h2>
            <span class="text-xs text-slate-500">Auto Rule: <strong class="text-emerald-800">15m Sebelum - 15m Setelah</strong> Target</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach(['masuk', 'istirahat', 'masuk_istirahat', 'pulang'] as $tipe)
                @php
                    $win = $windows[$tipe];
                    $labelMap = [
                        'masuk' => 'Jam Masuk',
                        'istirahat' => 'Jam Istirahat',
                        'masuk_istirahat' => 'Masuk Istirahat',
                        'pulang' => 'Jam Pulang',
                    ];
                @endphp
                <div class="p-4 rounded-xl border {{ $win['is_open'] ? 'bg-emerald-50/90 border-emerald-300' : 'bg-slate-50 border-slate-200' }} text-xs space-y-1.5">
                    <div class="flex items-center justify-between font-extrabold text-slate-900">
                        <span>{{ $labelMap[$tipe] }}</span>
                        @if($win['is_open'])
                            <span class="px-2 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] uppercase tracking-wider font-bold animate-pulse">DIBUKA</span>
                        @elseif($win['is_before'])
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] uppercase tracking-wider font-bold">Belum Buka</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px] uppercase tracking-wider font-bold">Ditutup</span>
                        @endif
                    </div>
                    <div class="text-slate-600 flex justify-between">
                        <span>Jam Target:</span>
                        <span class="font-bold text-slate-900">{{ $win['target_time'] }} WIB</span>
                    </div>
                    <div class="text-slate-500 flex justify-between text-[11px]">
                        <span>Buka Window:</span>
                        <span class="font-bold text-emerald-800">{{ $win['open_time'] }} - {{ $win['close_time'] }} WIB</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Today's Active Leaves Section (if any) -->
    @if(isset($todayLeaves) && $todayLeaves->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-emerald-700"></i> Pegawai Sedang Cuti Hari Ini ({{ $todayLeaves->count() }} Pegawai)
                </h2>
                <a href="{{ route('operator.leaves.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1">
                    Kelola Semua Cuti <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($todayLeaves as $tl)
                    @php
                        $badge = \App\Models\Leave::getJenisCutiBadge($tl->jenis_cuti);
                    @endphp
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex items-start justify-between gap-2">
                        <div>
                            <div class="font-bold text-xs text-slate-900">{{ $tl->user->name ?? 'Pegawai' }}</div>
                            <div class="text-[11px] font-mono text-slate-500">NIP: {{ $tl->user->nip ?? '-' }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                s/d {{ \Carbon\Carbon::parse($tl->tanggal_selesai)->translatedFormat('d M Y') }} ({{ $tl->jumlah_hari }} Hari)
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold border inline-flex items-center gap-1 {{ $badge['bg'] }} whitespace-nowrap">
                            <i class="{{ $badge['icon'] }}"></i> {{ $badge['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Today's Attendance Activity Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-emerald-700"></i> Log Presensi Realtime Hari Ini
            </h2>
            <a href="{{ route('operator.reports.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1">
                Lihat Semua Laporan <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                        <th class="py-3.5 px-4">Pegawai</th>
                        <th class="py-3.5 px-4">Tipe Presensi</th>
                        <th class="py-3.5 px-4">Waktu</th>
                        <th class="py-3.5 px-4">Status Waktu</th>
                        <th class="py-3.5 px-4">Status Approval</th>
                        <th class="py-3.5 px-4">Foto Wajah</th>
                        <th class="py-3.5 px-4">Lokasi GPS</th>
                        <th class="py-3.5 px-4 text-center">Aksi Operator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($todayAttendances as $att)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    @if(isset($att->user) && $att->user->hasFoto())
                                        <img src="{{ $att->user->foto_url }}" alt="{{ $att->user->name }}" class="w-8 h-8 rounded-lg object-cover border border-emerald-600 shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-[#064e3b] font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ isset($att->user) ? $att->user->inisial : '?' }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-slate-900 leading-tight">{{ $att->user->name ?? 'N/A' }}</div>
                                        <div class="text-[11px] text-amber-700 font-mono font-semibold">NIP: {{ $att->user->nip ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold border border-emerald-200">
                                    {{ \App\Models\Attendance::getTipeLabel($att->tipe) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-800">
                                {{ \Carbon\Carbon::parse($att->waktu)->format('H:i:s') }} WIB
                            </td>
                            <td class="py-3.5 px-4">
                                @if($att->status === 'tepat_waktu')
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold border border-emerald-300">Tepat Waktu</span>
                                @elseif($att->status === 'terlambat')
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold border border-amber-300">Terlambat</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 font-bold border border-teal-300">Lebih Awal</span>
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
                            <td class="py-3.5 px-4 text-center">
                                @if($att->approval_status === 'diterima')
                                    <button type="button" onclick="handleReject(this)" data-id="{{ $att->id }}" data-name="{{ e($att->user->name ?? 'Pegawai') }}" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold border border-rose-300 rounded-xl text-xs transition inline-flex items-center gap-1 shadow-sm">
                                        <i class="fa-solid fa-ban"></i> Tolak
                                    </button>
                                @else
                                    <button type="button" onclick="handleApprove(this)" data-id="{{ $att->id }}" data-name="{{ e($att->user->name ?? 'Pegawai') }}" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold border border-emerald-300 rounded-xl text-xs transition inline-flex items-center gap-1 shadow-sm">
                                        <i class="fa-solid fa-circle-check"></i> Setujui
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                Belum ada presensi yang tercatat hari ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl">
        <div class="p-4 bg-[#064e3b] text-white flex justify-between items-center border-b-2 border-amber-400">
            <span class="font-bold text-sm">Foto Presensi Pegawai</span>
            <button onclick="document.getElementById('imageModal').classList.add('hidden')" class="text-slate-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-4 bg-slate-950 text-center">
            <img id="modalImage" src="" alt="Preview" class="max-h-96 mx-auto rounded-xl object-contain">
        </div>
    </div>
</div>

@push('scripts')
<script>
    function previewImage(url) {
        document.getElementById('modalImage').src = url;
        document.getElementById('imageModal').classList.remove('hidden');
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

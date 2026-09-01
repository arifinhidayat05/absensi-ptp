@extends('layouts.app')

@section('title', 'Daftar Hari Libur & Tanggal Merah - Operator')

@section('content')
<div class="space-y-6">

    <!-- Top Navigation Tabs (Jam Kerja vs Hari Libur) -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <a href="{{ route('operator.schedules.index') }}"
            class="px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100">
            <i class="fa-solid fa-clock text-slate-400"></i> Jam Kerja Mingguan
        </a>
        <a href="{{ route('operator.holidays.index') }}"
            class="px-4 py-2.5 rounded-xl font-extrabold text-xs transition flex items-center gap-2 bg-[#064e3b] text-white shadow-md border border-emerald-700">
            <i class="fa-solid fa-calendar-xmark text-amber-300"></i> Daftar Hari Libur &amp; Tanggal Merah
        </a>
    </div>

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-calendar-xmark text-rose-600"></i> Daftar Hari Libur (Tanggal Merah)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Karyawan dan mahasiswa/siswa magang tidak dapat melakukan presensi pada tanggal merah yang terdaftar.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            <!-- 1-Click Generate National Holidays -->
            <form action="{{ route('operator.holidays.generateNational') }}" method="POST" onsubmit="return confirm('Muat daftar resmi Hari Libur Nasional & Cuti Bersama untuk Tahun {{ $year }}?')">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <button type="submit"
                    class="py-2.5 px-3.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black rounded-xl text-xs shadow-sm transition border border-amber-400 flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Muat Libur Nasional {{ $year }}
                </button>
            </form>

            <!-- Add Holiday Button -->
            <button onclick="openCreateModal()"
                class="py-2.5 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition duration-200 flex items-center gap-1.5 border border-emerald-700 cursor-pointer">
                <i class="fa-solid fa-plus text-amber-300"></i> Tambah Hari Libur
            </button>
        </div>
    </div>

    <!-- Statistics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-bold block">Total Libur ({{ $year }})</span>
                <span class="text-2xl font-black text-slate-900 font-mono mt-0.5 block">{{ $totalHolidays }} Hari</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-lg shadow-sm">
                <i class="fa-solid fa-calendar-xmark"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-bold block">Libur Mendatang ({{ $year }})</span>
                <span class="text-2xl font-black text-amber-600 font-mono mt-0.5 block">{{ $totalUpcoming }} Hari</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-lg shadow-sm">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
        </div>

        @php
            $todayHoliday = \App\Models\Holiday::getHoliday(\Carbon\Carbon::today());
        @endphp
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-bold block">Status Hari Ini ({{ \Carbon\Carbon::today()->translatedFormat('d M Y') }})</span>
                @if($todayHoliday)
                    <span class="text-xs font-black text-rose-700 mt-1 block truncate max-w-[200px]" title="{{ $todayHoliday->nama }}">
                        Libur: {{ $todayHoliday->nama }}
                    </span>
                @else
                    <span class="text-xs font-black text-emerald-700 mt-1 block">
                        Hari Kerja (Bukan Libur)
                    </span>
                @endif
            </div>
            <div class="w-11 h-11 rounded-xl {{ $todayHoliday ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }} flex items-center justify-center text-lg shadow-sm">
                <i class="fa-solid {{ $todayHoliday ? 'fa-ban' : 'fa-briefcase' }}"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <form action="{{ route('operator.holidays.index') }}" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <!-- Year Selector -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <label for="year" class="text-xs font-bold text-slate-700 whitespace-nowrap">
                    <i class="fa-regular fa-calendar me-1 text-emerald-700"></i> Tahun:
                </label>
                <select name="year" id="year" onchange="this.form.submit()"
                    class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold focus:ring-emerald-500 focus:border-emerald-500">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search Input -->
            <div class="flex items-center gap-2 w-full sm:w-72">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama hari libur..."
                        class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                @if($search)
                    <a href="{{ route('operator.holidays.index', ['year' => $year]) }}" class="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold" title="Reset Pencarian">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
                <button type="submit" class="py-2 px-3 bg-[#064e3b] text-white font-bold rounded-xl text-xs hover:bg-[#043d2e] transition">
                    Cari
                </button>
            </div>
        </form>
    </div>

    <!-- Holidays Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Hari &amp; Tanggal</th>
                        <th class="py-3.5 px-4">Nama Hari Libur</th>
                        <th class="py-3.5 px-4">Kategori / Keterangan</th>
                        <th class="py-3.5 px-4 text-center">Status Waktu</th>
                        <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($holidays as $index => $holiday)
                        @php
                            $holDate = \Carbon\Carbon::parse($holiday->tanggal);
                            $isPast = $holDate->isPast() && !$holDate->isToday();
                            $isToday = $holDate->isToday();
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $isToday ? 'bg-rose-50/60' : '' }}">
                            <td class="py-3.5 px-4 text-center font-bold text-slate-500 font-mono">
                                {{ ($holidays->currentPage() - 1) * $holidays->perPage() + $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-extrabold text-slate-900 flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar text-rose-600"></i>
                                    {{ $holDate->translatedFormat('l, d F Y') }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono pl-5">
                                    {{ $holDate->format('Y-m-d') }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-xs">{{ $holiday->nama }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border {{ str_contains(strtolower($holiday->keterangan ?? ''), 'cuti') ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-rose-50 text-rose-900 border-rose-200' }}">
                                    {{ $holiday->keterangan ?? 'Hari Libur Nasional' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($isToday)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white animate-pulse shadow-sm">
                                        Hari Ini
                                    </span>
                                @elseif($isPast)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                        Selesai
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Mendatang
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Edit Button -->
                                    <button type="button"
                                        onclick="openEditModal('{{ $holiday->id }}', '{{ $holiday->tanggal->format('Y-m-d') }}', '{{ addslashes($holiday->nama) }}', '{{ addslashes($holiday->keterangan) }}')"
                                        class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 flex items-center justify-center transition border border-amber-200"
                                        title="Edit Hari Libur">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('operator.holidays.destroy', $holiday->id) }}" method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus hari libur {{ $holiday->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition border border-rose-200"
                                            title="Hapus Hari Libur">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 text-2xl">
                                    <i class="fa-solid fa-calendar-xmark"></i>
                                </div>
                                <p class="font-bold text-sm text-slate-600">Belum ada hari libur terdaftar di Tahun {{ $year }}</p>
                                <p class="text-xs text-slate-400 mt-1">Klik tombol <strong>"Muat Libur Nasional {{ $year }}"</strong> atau <strong>"Tambah Hari Libur"</strong> untuk mendaftarkan tanggal merah.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($holidays->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $holidays->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH HARI LIBUR -->
<!-- ========================================== -->
<div id="createModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 my-auto">
        <div class="bg-[#064e3b] text-white p-4 sm:p-5 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-black text-sm sm:text-base flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-amber-300"></i> Tambah Hari Libur
            </h3>
            <button onclick="closeCreateModal()" class="w-8 h-8 rounded-full bg-emerald-900 text-slate-200 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('operator.holidays.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <div>
                <label for="create_tanggal" class="block text-xs font-bold text-slate-700 mb-1">
                    Tanggal Libur (Tanggal Merah) *
                </label>
                <input type="date" name="tanggal" id="create_tanggal" required value="{{ date('Y-m-d') }}"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="create_nama" class="block text-xs font-bold text-slate-700 mb-1">
                    Nama Hari Libur *
                </label>
                <input type="text" name="nama" id="create_nama" required placeholder="Contoh: Hari Kemerdekaan RI Ke-81"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="create_keterangan" class="block text-xs font-bold text-slate-700 mb-1">
                    Kategori / Keterangan
                </label>
                <select name="keterangan" id="create_keterangan"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="Hari Libur Nasional">Hari Libur Nasional</option>
                    <option value="Cuti Bersama">Cuti Bersama</option>
                    <option value="Libur Khusus Satker">Libur Khusus Satker</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeCreateModal()" class="py-2.5 px-4 bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs transition shadow border border-emerald-700">
                    Simpan Hari Libur
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL EDIT HARI LIBUR -->
<!-- ========================================== -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 my-auto">
        <div class="bg-[#064e3b] text-white p-4 sm:p-5 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-black text-sm sm:text-base flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-300"></i> Edit Hari Libur
            </h3>
            <button onclick="closeEditModal()" class="w-8 h-8 rounded-full bg-emerald-900 text-slate-200 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="editForm" method="POST" class="p-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="edit_tanggal" class="block text-xs font-bold text-slate-700 mb-1">
                    Tanggal Libur (Tanggal Merah) *
                </label>
                <input type="date" name="tanggal" id="edit_tanggal" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="edit_nama" class="block text-xs font-bold text-slate-700 mb-1">
                    Nama Hari Libur *
                </label>
                <input type="text" name="nama" id="edit_nama" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label for="edit_keterangan" class="block text-xs font-bold text-slate-700 mb-1">
                    Kategori / Keterangan
                </label>
                <input type="text" name="keterangan" id="edit_keterangan"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeEditModal()" class="py-2.5 px-4 bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs transition shadow border border-emerald-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }
    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
    }

    function openEditModal(id, tanggal, nama, keterangan) {
        document.getElementById('editForm').action = '/operator/holidays/' + id;
        document.getElementById('edit_tanggal').value = tanggal;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_keterangan').value = keterangan || 'Hari Libur Nasional';
        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endpush

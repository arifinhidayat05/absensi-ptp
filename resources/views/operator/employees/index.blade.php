@extends('layouts.app')

@section('title', 'Kelola Data Pegawai & Magang - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-users text-emerald-700"></i> Kelola Data Pegawai &amp; Magang
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola data Pegawai (NIP), Mahasiswa Magang (NIM), dan Siswa Magang SMA/SMK (NISN). Password default: <strong class="text-emerald-800 font-bold bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded font-mono">password</strong>
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <!-- Search Form -->
            <form method="GET" action="{{ route('operator.employees.index') }}" class="relative">
                @if(request('jenis_pegawai'))
                    <input type="hidden" name="jenis_pegawai" value="{{ request('jenis_pegawai') }}">
                @endif
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari NIP / NIM / NISN / Nama..."
                    class="bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500 w-56">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
            </form>

            <button onclick="openImportModal()" class="px-4 py-2.5 text-xs bg-white hover:bg-emerald-50 text-emerald-800 font-bold rounded-xl shadow-xs border border-emerald-300 transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i> Import Excel
            </button>

            <button onclick="openAddModal()" class="px-4 py-2.5 text-xs bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl shadow-md transition flex items-center gap-2 border border-emerald-700 cursor-pointer">
                <i class="fa-solid fa-user-plus text-amber-300"></i> Tambah Data Baru
            </button>
        </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- Semua -->
        <a href="{{ route('operator.employees.index', array_merge(request()->except('jenis_pegawai', 'page'))) }}"
            class="bg-white p-4 rounded-2xl border {{ empty($jenis_pegawai) ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200' }} shadow-sm hover:border-emerald-400 transition block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Semua Kategori</span>
                <i class="fa-solid fa-users text-slate-400 text-sm"></i>
            </div>
            <div class="mt-2 text-xl font-black text-slate-900">{{ number_format($countAll) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Total Keseluruhan</div>
        </a>

        <!-- Pegawai (NIP) -->
        <a href="{{ route('operator.employees.index', array_merge(request()->except('page'), ['jenis_pegawai' => 'pegawai'])) }}"
            class="bg-white p-4 rounded-2xl border {{ $jenis_pegawai === 'pegawai' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200' }} shadow-sm hover:border-emerald-400 transition block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Pegawai (NIP)</span>
                <i class="fa-solid fa-user-tie text-emerald-600 text-sm"></i>
            </div>
            <div class="mt-2 text-xl font-black text-emerald-800">{{ number_format($countPegawai) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">PNS / PPPK / Honorer</div>
        </a>

        <!-- Mahasiswa Magang (NIM) -->
        <a href="{{ route('operator.employees.index', array_merge(request()->except('page'), ['jenis_pegawai' => 'mahasiswa_magang'])) }}"
            class="bg-white p-4 rounded-2xl border {{ $jenis_pegawai === 'mahasiswa_magang' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'border-slate-200' }} shadow-sm hover:border-sky-400 transition block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-sky-600 uppercase tracking-wider">Kuliah (NIM)</span>
                <i class="fa-solid fa-graduation-cap text-sky-600 text-sm"></i>
            </div>
            <div class="mt-2 text-xl font-black text-sky-700">{{ number_format($countMahasiswa) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Mahasiswa Magang / PKL</div>
        </a>

        <!-- Siswa Magang (NISN) -->
        <a href="{{ route('operator.employees.index', array_merge(request()->except('page'), ['jenis_pegawai' => 'siswa_magang'])) }}"
            class="bg-white p-4 rounded-2xl border {{ $jenis_pegawai === 'siswa_magang' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200' }} shadow-sm hover:border-amber-400 transition block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">SMA/SMK (NISN)</span>
                <i class="fa-solid fa-school text-amber-600 text-sm"></i>
            </div>
            <div class="mt-2 text-xl font-black text-amber-700">{{ number_format($countSiswa) }}</div>
            <div class="text-[11px] text-slate-500 font-semibold truncate">Siswa Magang / Prakerin</div>
        </a>
    </div>

    <!-- Employee Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#064e3b] text-emerald-100 font-extrabold uppercase tracking-wider text-[11px] border-b-2 border-amber-400">
                        <th class="py-3.5 px-4" style="width: 160px;">Nomor Identitas</th>
                        <th class="py-3.5 px-4">Pegawai (Foto &amp; Nama)</th>
                        <th class="py-3.5 px-4">Kategori Status</th>
                        <th class="py-3.5 px-4">Jabatan / Asal Instansi</th>
                        <th class="py-3.5 px-4">Kontak (No HP / Email)</th>
                        <th class="py-3.5 px-4">Terdaftar</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-slate-50 transition">
                            <!-- Nomor Identitas (NIP / NIM / NISN) -->
                            <td class="py-3.5 px-4 font-mono">
                                @if($emp->tipe_identitas === 'nim')
                                    <span class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-800 font-bold border border-sky-200 text-[10px] uppercase inline-block mb-0.5">
                                        NIM
                                    </span>
                                @elseif($emp->tipe_identitas === 'nisn')
                                    <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-bold border border-amber-200 text-[10px] uppercase inline-block mb-0.5">
                                        NISN
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-bold border border-emerald-200 text-[10px] uppercase inline-block mb-0.5">
                                        NIP
                                    </span>
                                @endif
                                <div class="font-bold text-slate-900">{{ $emp->nip }}</div>
                            </td>

                            <!-- Nama Lengkap & Foto Profil Pegawai -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    @if($emp->hasFoto())
                                        <button type="button" onclick="showPhotoModal('{{ $emp->foto_url }}', '{{ addslashes($emp->name) }}', '{{ $emp->identitas_lengkap }}', '{{ addslashes($emp->jabatan ?? 'Pegawai') }}', '{{ route('operator.employees.download-photo', $emp->id) }}')"
                                            class="group relative shrink-0 focus:outline-none cursor-pointer" title="Klik untuk melihat foto profil penuh">
                                            <img src="{{ $emp->foto_url }}" alt="{{ $emp->name }}" class="w-10 h-10 rounded-xl object-cover border-2 border-emerald-600 shadow-xs group-hover:scale-105 group-hover:border-amber-400 transition-all">
                                            <span class="absolute inset-0 bg-black/30 rounded-xl opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] transition">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                                            </span>
                                        </button>
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 font-black text-xs flex items-center justify-center border border-slate-200 shrink-0" title="Belum ada foto profil">
                                            {{ $emp->inisial }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 leading-tight">{{ $emp->name }}</div>
                                        @if($emp->hasFoto())
                                            <button type="button" onclick="showPhotoModal('{{ $emp->foto_url }}', '{{ addslashes($emp->name) }}', '{{ $emp->identitas_lengkap }}', '{{ addslashes($emp->jabatan ?? 'Pegawai') }}', '{{ route('operator.employees.download-photo', $emp->id) }}')"
                                                class="text-[10px] text-emerald-700 hover:text-emerald-900 font-bold inline-flex items-center gap-1 mt-0.5 cursor-pointer" title="Lihat Foto Profil">
                                                <i class="fa-solid fa-image"></i> Lihat Foto
                                            </button>
                                        @else
                                            <span class="text-[10px] text-slate-400 italic mt-0.5 inline-block">Tanpa Foto</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori Status -->
                            <td class="py-3.5 px-4">
                                @if($emp->jenis_pegawai === 'mahasiswa_magang')
                                    <span class="px-2.5 py-1 rounded-lg bg-sky-50 text-sky-800 font-bold border border-sky-200 inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-graduation-cap text-sky-600"></i> Mahasiswa Magang
                                    </span>
                                @elseif($emp->jenis_pegawai === 'siswa_magang')
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 font-bold border border-amber-200 inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-school text-amber-600"></i> Siswa Magang (SMA/SMK)
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold border border-emerald-200 inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-user-tie text-emerald-600"></i> Pegawai
                                    </span>
                                @endif
                            </td>

                            <!-- Jabatan & Asal Instansi -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $emp->jabatan ?? 'Pegawai' }}</div>
                                @if($emp->asal_instansi)
                                    <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                        <i class="fa-solid fa-building-columns text-slate-400"></i> {{ $emp->asal_instansi }}
                                    </div>
                                @endif
                            </td>

                            <!-- Kontak (No HP & Email) -->
                            <td class="py-3.5 px-4 text-slate-600">
                                @if($emp->no_hp)
                                    <div class="flex items-center gap-1.5 font-mono text-[11px] text-slate-900 font-bold mb-0.5">
                                        <i class="fa-solid fa-phone text-emerald-600 text-[10px]"></i>
                                        <span>{{ $emp->no_hp }}</span>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', (str_starts_with($emp->no_hp, '0') ? '62' . substr($emp->no_hp, 1) : $emp->no_hp)) }}"
                                            target="_blank" title="Kirim Pesan WhatsApp"
                                            class="text-emerald-600 hover:text-emerald-700 text-xs">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                @endif
                                @if($emp->email)
                                    <div class="text-[11px] text-slate-500 truncate max-w-[170px]" title="{{ $emp->email }}">
                                        <i class="fa-solid fa-envelope text-slate-400 text-[10px] me-1"></i>{{ $emp->email }}
                                    </div>
                                @elseif(!$emp->no_hp)
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @endif
                            </td>

                            <!-- Terdaftar -->
                            <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                                {{ $emp->created_at->format('d/m/Y') }}
                            </td>

                            <!-- Aksi Operator -->
                            <td class="py-3.5 px-4 text-center space-x-1 whitespace-nowrap">
                                <!-- Edit Button -->
                                <button onclick="openEditModal({{ json_encode($emp) }})" title="Edit Data"
                                    class="p-2 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-500 hover:text-white transition">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <!-- Reset Password Button -->
                                <form action="{{ route('operator.employees.reset-password', $emp->id) }}" method="POST" class="inline" id="reset-form-{{ $emp->id }}">
                                    @csrf
                                    <button type="button" onclick="confirmReset('{{ $emp->name }}', 'reset-form-{{ $emp->id }}')" title="Reset Password ke 'password'"
                                        class="p-2 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-700 hover:text-white transition">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </form>

                                <!-- Delete Button -->
                                <form action="{{ route('operator.employees.destroy', $emp->id) }}" method="POST" class="inline" id="delete-form-{{ $emp->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete('{{ $emp->name }}', 'delete-form-{{ $emp->id }}')" title="Hapus Data"
                                        class="p-2 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-600 hover:text-white transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-3xl block mb-2 text-slate-300"></i>
                                Tidak ada data pegawai atau anak magang yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- ADD EMPLOYEE / INTERN MODAL -->
<!-- ========================================== -->
<div id="addModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl">
        <div class="bg-[#064e3b] text-white p-5 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-black text-base text-white flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-amber-300"></i> Tambah Pegawai / Anak Magang Baru
            </h3>
            <button onclick="closeAddModal()" class="text-slate-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('operator.employees.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Kategori Pegawai / Magang -->
            <div>
                <label for="create_jenis_pegawai" class="block text-xs font-bold text-slate-700 mb-1">
                    Kategori / Status *
                </label>
                <select name="jenis_pegawai" id="create_jenis_pegawai" onchange="handleCategoryChange('create')" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="pegawai" selected>Pegawai (PNS / PPPK / Honorer)</option>
                    <option value="mahasiswa_magang">Mahasiswa Magang / PKL (Kuliah)</option>
                    <option value="siswa_magang">Siswa Magang / Prakerin (SMA / SMK)</option>
                </select>
            </div>

            <!-- Hidden / Select Tipe Identitas -->
            <input type="hidden" name="tipe_identitas" id="create_tipe_identitas" value="nip">

            <!-- Nomor Identitas (NIP / NIM / NISN) -->
            <div>
                <label for="create_nip" id="create_nip_label" class="block text-xs font-bold text-slate-700 mb-1">
                    Nomor Induk Pegawai (NIP) *
                </label>
                <input type="text" name="nip" id="create_nip" required placeholder="Contoh: 198507152010121002"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                <p id="create_nip_help" class="text-[10px] text-slate-400 mt-1">Digunakan sebagai username saat login presensi.</p>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label for="create_name" class="block text-xs font-bold text-slate-700 mb-1">
                    Nama Lengkap *
                </label>
                <input type="text" name="name" id="create_name" required placeholder="Contoh: Ahmad Pratama"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Asal Kampus / Sekolah (Untuk Magang) -->
            <div id="create_instansi_wrapper" class="hidden">
                <label for="create_asal_instansi" id="create_instansi_label" class="block text-xs font-bold text-slate-700 mb-1">
                    Asal Universitas / Kampus
                </label>
                <input type="text" name="asal_instansi" id="create_asal_instansi" placeholder="Contoh: Universitas Tanjungpura / SMKN 1 Pontianak"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Jabatan / Posisi -->
            <div>
                <label for="create_jabatan" class="block text-xs font-bold text-slate-700 mb-1">
                    Jabatan / Posisi Penempatan
                </label>
                <input type="text" name="jabatan" id="create_jabatan" placeholder="Contoh: Panitera Muda / IT Support Magang"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- No HP / WhatsApp -->
            <div>
                <label for="create_no_hp" class="block text-xs font-bold text-slate-700 mb-1">
                    No. Handphone / WhatsApp (Opsional)
                </label>
                <div class="relative">
                    <input type="text" name="no_hp" id="create_no_hp" placeholder="Contoh: 081234567890"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-phone text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="create_email" class="block text-xs font-bold text-slate-700 mb-1">
                    Email (Opsional)
                </label>
                <input type="email" name="email" id="create_email" placeholder="user@pt-pontianak.go.id"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Foto Profil (Opsional) -->
            <div>
                <label for="create_foto" class="block text-xs font-bold text-slate-700 mb-1">
                    Foto Profil Pegawai (Opsional)
                </label>
                <div class="flex items-center gap-3">
                    <div id="create_foto_preview_box" class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-300 flex items-center justify-center overflow-hidden shrink-0">
                        <i class="fa-solid fa-image text-slate-400 text-base" id="create_foto_placeholder"></i>
                        <img id="create_foto_preview" class="w-full h-full object-cover hidden">
                    </div>
                    <div class="flex-1 min-w-0">
                        <input type="file" name="foto" id="create_foto" accept="image/jpeg,image/png,image/jpg,image/webp"
                            onchange="previewEmployeeCreatePhoto(this)"
                            class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 border border-slate-300 rounded-xl bg-slate-50 p-1 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WEBP (Maksimal 2MB)</p>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-[11px] text-amber-900">
                <i class="fa-solid fa-shield-halved me-1 text-amber-700"></i> Password default akun baru: <strong class="text-amber-950 font-mono font-bold">password</strong>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 py-3 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition border border-emerald-700 flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-plus text-amber-300"></i> SIMPAN DATA
                </button>
                <button type="button" onclick="closeAddModal()" class="py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- EDIT EMPLOYEE / INTERN MODAL -->
<!-- ========================================== -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl max-h-[90vh] flex flex-col">
        <div class="bg-[#064e3b] text-white p-5 flex items-center justify-between border-b-2 border-amber-400 shrink-0">
            <h3 class="font-black text-base text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-300"></i> Edit Data Pegawai / Anak Magang
            </h3>
            <button onclick="closeEditModal()" class="text-slate-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="editForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
            @csrf
            @method('PUT')

            <!-- Foto Profil Pegawai -->
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2">
                <label class="block text-xs font-bold text-slate-700">
                    Foto Profil Pegawai
                </label>
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-2xl border-2 border-slate-300 overflow-hidden bg-white flex items-center justify-center shrink-0 shadow-xs relative">
                        <img id="edit_foto_preview" src="" alt="Foto" class="w-full h-full object-cover hidden">
                        <div id="edit_foto_initial" class="w-full h-full bg-emerald-100 text-[#064e3b] font-black text-sm flex items-center justify-center">
                            --
                        </div>
                    </div>
                    <div class="flex-1 min-w-0 space-y-1">
                        <input type="file" name="foto" id="edit_foto" accept="image/jpeg,image/png,image/jpg,image/webp"
                            onchange="previewEmployeeEditPhoto(this)"
                            class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 border border-slate-300 rounded-xl bg-white p-1 cursor-pointer">
                        <div id="edit_delete_foto_wrapper" class="hidden">
                            <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 font-bold cursor-pointer">
                                <input type="checkbox" name="hapus_foto" id="edit_hapus_foto" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                <span>Hapus foto profil saat ini</span>
                            </label>
                        </div>
                        <p class="text-[10px] text-slate-400">Pilih berkas baru untuk mengubah foto (Maks 2MB)</p>
                    </div>
                </div>
            </div>

            <!-- Kategori Pegawai / Magang -->
            <div>
                <label for="edit_jenis_pegawai" class="block text-xs font-bold text-slate-700 mb-1">
                    Kategori / Status *
                </label>
                <select name="jenis_pegawai" id="edit_jenis_pegawai" onchange="handleCategoryChange('edit')" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="pegawai">Pegawai (PNS / PPPK / Honorer)</option>
                    <option value="mahasiswa_magang">Mahasiswa Magang / PKL (Kuliah)</option>
                    <option value="siswa_magang">Siswa Magang / Prakerin (SMA / SMK)</option>
                </select>
            </div>

            <!-- Hidden Tipe Identitas -->
            <input type="hidden" name="tipe_identitas" id="edit_tipe_identitas" value="nip">

            <!-- Nomor Identitas (NIP / NIM / NISN) -->
            <div>
                <label for="edit_nip" id="edit_nip_label" class="block text-xs font-bold text-slate-700 mb-1">
                    Nomor Identitas *
                </label>
                <input type="text" name="nip" id="edit_nip" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label for="edit_name" class="block text-xs font-bold text-slate-700 mb-1">
                    Nama Lengkap *
                </label>
                <input type="text" name="name" id="edit_name" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Asal Kampus / Sekolah -->
            <div id="edit_instansi_wrapper">
                <label for="edit_asal_instansi" id="edit_instansi_label" class="block text-xs font-bold text-slate-700 mb-1">
                    Asal Kampus / Sekolah
                </label>
                <input type="text" name="asal_instansi" id="edit_asal_instansi" placeholder="Contoh: Universitas Tanjungpura / SMKN 1 Pontianak"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Jabatan / Posisi -->
            <div>
                <label for="edit_jabatan" class="block text-xs font-bold text-slate-700 mb-1">
                    Jabatan / Posisi Penempatan
                </label>
                <input type="text" name="jabatan" id="edit_jabatan"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- No HP / WhatsApp -->
            <div>
                <label for="edit_no_hp" class="block text-xs font-bold text-slate-700 mb-1">
                    No. Handphone / WhatsApp
                </label>
                <div class="relative">
                    <input type="text" name="no_hp" id="edit_no_hp" placeholder="Contoh: 081234567890"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-phone text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="edit_email" class="block text-xs font-bold text-slate-700 mb-1">
                    Email
                </label>
                <input type="email" name="email" id="edit_email"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 py-3 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition border border-emerald-700 flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-save text-amber-300"></i> SIMPAN PERUBAHAN
                </button>
                <button type="button" onclick="closeEditModal()" class="py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function handleCategoryChange(mode) {
        const catSelect = document.getElementById(mode + '_jenis_pegawai');
        const tipeInput = document.getElementById(mode + '_tipe_identitas');
        const nipLabel = document.getElementById(mode + '_nip_label');
        const nipInput = document.getElementById(mode + '_nip');
        const instansiWrapper = document.getElementById(mode + '_instansi_wrapper');
        const instansiLabel = document.getElementById(mode + '_instansi_label');
        const jabatanInput = document.getElementById(mode + '_jabatan');

        const cat = catSelect.value;

        if (cat === 'mahasiswa_magang') {
            tipeInput.value = 'nim';
            nipLabel.innerHTML = 'Nomor Induk Mahasiswa (NIM) *';
            nipInput.placeholder = 'Contoh: F1081191001';
            instansiWrapper.classList.remove('hidden');
            instansiLabel.innerHTML = 'Asal Universitas / Kampus';
            if (mode === 'create' && (!jabatanInput.value || jabatanInput.value === 'Pegawai' || jabatanInput.value === 'Siswa Magang')) {
                jabatanInput.value = 'Mahasiswa Magang';
            }
        } else if (cat === 'siswa_magang') {
            tipeInput.value = 'nisn';
            nipLabel.innerHTML = 'Nomor Induk Siswa Nasional (NISN) *';
            nipInput.placeholder = 'Contoh: 0051234567';
            instansiWrapper.classList.remove('hidden');
            instansiLabel.innerHTML = 'Asal Sekolah (SMA / SMK)';
            if (mode === 'create' && (!jabatanInput.value || jabatanInput.value === 'Pegawai' || jabatanInput.value === 'Mahasiswa Magang')) {
                jabatanInput.value = 'Siswa Magang';
            }
        } else {
            tipeInput.value = 'nip';
            nipLabel.innerHTML = 'Nomor Induk Pegawai (NIP) *';
            nipInput.placeholder = 'Contoh: 198507152010121002';
            instansiWrapper.classList.add('hidden');
            if (mode === 'create' && (!jabatanInput.value || jabatanInput.value === 'Mahasiswa Magang' || jabatanInput.value === 'Siswa Magang')) {
                jabatanInput.value = 'Pegawai';
            }
        }
    }

    function openAddModal() {
        document.getElementById('create_jenis_pegawai').value = 'pegawai';
        handleCategoryChange('create');
        document.getElementById('create_no_hp').value = '';
        document.getElementById('create_foto').value = '';
        document.getElementById('create_foto_preview').classList.add('hidden');
        document.getElementById('create_foto_placeholder').classList.remove('hidden');
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
    }

    function openEditModal(emp) {
        document.getElementById('edit_jenis_pegawai').value = emp.jenis_pegawai || 'pegawai';
        handleCategoryChange('edit');

        document.getElementById('edit_nip').value = emp.nip;
        document.getElementById('edit_name').value = emp.name;
        document.getElementById('edit_jabatan').value = emp.jabatan || '';
        document.getElementById('edit_asal_instansi').value = emp.asal_instansi || '';
        document.getElementById('edit_no_hp').value = emp.no_hp || '';
        document.getElementById('edit_email').value = emp.email || '';

        // Reset Foto field & previews
        document.getElementById('edit_foto').value = '';
        const delCheck = document.getElementById('edit_hapus_foto');
        if (delCheck) delCheck.checked = false;

        const previewImg = document.getElementById('edit_foto_preview');
        const initialBox = document.getElementById('edit_foto_initial');
        const delWrapper = document.getElementById('edit_delete_foto_wrapper');

        if (emp.foto_url) {
            previewImg.src = emp.foto_url;
            previewImg.classList.remove('hidden');
            initialBox.classList.add('hidden');
            delWrapper.classList.remove('hidden');
        } else {
            previewImg.src = '';
            previewImg.classList.add('hidden');
            initialBox.textContent = emp.inisial || '--';
            initialBox.classList.remove('hidden');
            delWrapper.classList.add('hidden');
        }

        document.getElementById('editForm').action = "/operator/employees/" + emp.id;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function previewEmployeeCreatePhoto(input) {
        const preview = document.getElementById('create_foto_preview');
        const placeholder = document.getElementById('create_foto_placeholder');
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran Terlalu Besar',
                    text: 'Ukuran foto maksimal adalah 2MB.',
                    confirmButtonColor: '#064e3b'
                });
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function previewEmployeeEditPhoto(input) {
        const preview = document.getElementById('edit_foto_preview');
        const initial = document.getElementById('edit_foto_initial');
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran Terlalu Besar',
                    text: 'Ukuran foto maksimal adalah 2MB.',
                    confirmButtonColor: '#064e3b'
                });
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                initial.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function showPhotoModal(url, name, nip, jabatan, downloadUrl) {
        document.getElementById('photoModalImg').src = url;
        document.getElementById('photoModalName').textContent = name;
        document.getElementById('photoModalNip').textContent = nip;
        document.getElementById('photoModalJabatan').textContent = jabatan;

        const downloadBtn = document.getElementById('photoModalDownloadBtn');
        if (downloadUrl) {
            downloadBtn.href = downloadUrl;
            downloadBtn.classList.remove('hidden');
        } else {
            downloadBtn.classList.add('hidden');
        }

        document.getElementById('photoModal').classList.remove('hidden');
    }

    function closePhotoModal() {
        document.getElementById('photoModal').classList.add('hidden');
    }

    function confirmReset(name, formId) {
        Swal.fire({
            title: 'Reset Password?',
            text: `Password "${name}" akan direset menjadi "password".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#064e3b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Reset Password'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }

    function confirmDelete(name, formId) {
        Swal.fire({
            title: 'Hapus Data?',
            text: `Data "${name}" dan riwayat presensinya akan dihapus permanen!`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>

<!-- ========================================== -->
<!-- PREVIEW PROFILE PHOTO MODAL -->
<!-- ========================================== -->
<div id="photoModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" onclick="closePhotoModal()">
    <div class="bg-white rounded-3xl max-w-sm w-full overflow-hidden shadow-2xl border-2 border-amber-400" onclick="event.stopPropagation()">
        <div class="bg-[#064e3b] text-white p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-id-badge text-amber-300"></i>
                <h3 class="font-black text-sm text-white">Foto Profil Pegawai</h3>
            </div>
            <button onclick="closePhotoModal()" class="text-slate-300 hover:text-white p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <div class="p-6 text-center space-y-4">
            <div class="w-48 h-48 mx-auto rounded-2xl overflow-hidden shadow-lg border-4 border-emerald-600/30 bg-slate-100 flex items-center justify-center">
                <img id="photoModalImg" src="" alt="Foto Profil" class="w-full h-full object-cover">
            </div>
            <div>
                <h4 id="photoModalName" class="font-black text-base text-slate-900 leading-tight">Nama Pegawai</h4>
                <p id="photoModalNip" class="text-xs font-mono font-bold text-amber-700 mt-0.5">NIP: -</p>
                <p id="photoModalJabatan" class="text-xs text-slate-500 font-semibold mt-0.5">Jabatan: -</p>
            </div>
            <div class="pt-2 flex gap-2">
                <a id="photoModalDownloadBtn" href="#" class="flex-1 py-2.5 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition border border-emerald-700 flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-download text-amber-300"></i> Download Foto
                </a>
                <button type="button" onclick="closePhotoModal()"
                    class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- IMPORT EXCEL MODAL -->
<!-- ========================================== -->
<div id="importModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border-2 border-emerald-600">
        <div class="bg-[#064e3b] text-white p-5 flex items-center justify-between border-b-2 border-amber-400">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-file-excel text-amber-300 text-lg"></i>
                <h3 class="font-black text-base text-white">Import Data Pegawai dari Excel</h3>
            </div>
            <button onclick="closeImportModal()" class="text-slate-300 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="p-6 space-y-5">
            <!-- Langkah 1: Unduh Format Template -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 space-y-2">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-700 text-white font-black text-sm flex items-center justify-center shrink-0 shadow-xs">
                        1
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-xs text-emerald-950">Unduh Format Template Resmi</h4>
                        <p class="text-[11px] text-emerald-800 mt-0.5">
                            Gunakan susunan kolom resmi agar NIP/NIM/NISN, Nama, Kategori, No HP, dan Jabatan terisi sempurna ke sistem.
                        </p>
                        <div class="mt-3">
                            <a href="{{ route('operator.employees.template-excel') }}"
                                class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#064e3b] hover:bg-[#043d2e] text-white rounded-xl font-bold text-xs shadow-xs transition border border-emerald-700 cursor-pointer">
                                <i class="fa-solid fa-file-arrow-down text-amber-300"></i> Unduh Template Excel (.xlsx)
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Langkah 2: Upload File Form -->
            <form action="{{ route('operator.employees.import-excel') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-[#064e3b] text-white font-black text-sm flex items-center justify-center shrink-0 shadow-xs">
                            2
                        </div>
                        <label for="excel_file" class="block text-xs font-bold text-slate-800">
                            Pilih Berkas Excel Pengisian (.xlsx / .xls / .csv) *
                        </label>
                    </div>

                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv" required
                        class="w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#064e3b] file:text-white hover:file:bg-[#043d2e] border border-slate-300 rounded-2xl bg-slate-50 p-2 cursor-pointer">
                    <p class="text-[10px] text-slate-400">Maksimal 5MB. Password akun baru otomatis dibuatkan "password".</p>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="submit" class="flex-1 py-3 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition border border-emerald-700 flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i> PROSES IMPORT DATA
                    </button>
                    <button type="button" onclick="closeImportModal()" class="py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

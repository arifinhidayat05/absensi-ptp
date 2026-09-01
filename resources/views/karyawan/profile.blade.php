@extends('layouts.app')

@section('title', 'Profil & Foto Profil Pegawai - Pengadilan Tinggi Pontianak')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header Banner with Avatar & Identity -->
    <div class="bg-gradient-to-r from-[#064e3b] via-[#046346] to-[#064e3b] p-6 rounded-3xl border-2 border-amber-400 shadow-xl text-white flex flex-col sm:flex-row items-center gap-5">
        <div class="relative group">
            @if($user->foto_url)
                <img src="{{ $user->foto_url }}" alt="{{ $user->name }}" id="headerAvatarImg"
                    class="w-20 h-20 rounded-2xl object-cover border-2 border-amber-400 shadow-md">
            @else
                <div id="headerAvatarFallback" class="w-20 h-20 rounded-2xl bg-white text-[#064e3b] flex items-center justify-center text-2xl font-black shadow-md border-2 border-amber-400">
                    {{ $user->inisial }}
                </div>
            @endif
            <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 border-2 border-[#064e3b] flex items-center justify-center text-white text-[10px]" title="Akun Aktif">
                <i class="fa-solid fa-check"></i>
            </span>
        </div>
        <div class="text-center sm:text-left flex-1 min-w-0">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-400 text-slate-950 shadow-sm">
                    {{ $user->jenis_pegawai_label }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/70 text-emerald-100 border border-emerald-700">
                    PT Pontianak
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-white truncate">{{ $user->name }}</h1>
            <p class="text-xs text-amber-200 font-mono font-bold mt-0.5">
                {{ $user->tipe_identitas_label }}: <span class="bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-700/60">{{ $user->nip }}</span>
            </p>
        </div>
    </div>

    <!-- Foto Profil (Profile Picture) Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-50 p-5 border-b border-slate-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-camera-retro"></i>
                </div>
                <div>
                    <h2 class="font-black text-sm text-slate-900">Foto Profil (Profile Picture)</h2>
                    <p class="text-[11px] text-slate-500">Unggah foto wajah resmi Anda agar dapat dikenali oleh Admin / Operator</p>
                </div>
            </div>
            @if($user->hasFoto())
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i> Terpasang
                </span>
            @else
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">
                    <i class="fa-solid fa-circle-info text-amber-600"></i> Menggunakan Inisial
                </span>
            @endif
        </div>

        <div class="p-6">
            <form action="{{ route('karyawan.profile.foto') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <!-- Live Image Preview Box -->
                    <div class="relative shrink-0 text-center">
                        <div class="w-28 h-28 rounded-2xl border-2 border-dashed border-slate-300 overflow-hidden bg-slate-100 flex items-center justify-center shadow-inner relative group">
                            <img id="imagePreview"
                                src="{{ $user->foto_url ?? '' }}"
                                alt="Preview Foto"
                                class="w-full h-full object-cover {{ $user->hasFoto() ? '' : 'hidden' }}">
                            <div id="imagePlaceholder" class="text-center p-3 {{ $user->hasFoto() ? 'hidden' : '' }}">
                                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#064e3b] font-black text-lg flex items-center justify-center mx-auto mb-1">
                                    {{ $user->inisial }}
                                </div>
                                <span class="text-[10px] font-bold text-slate-400">Belum ada foto</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 block mt-1.5">Tampilan Foto Profil</span>
                    </div>

                    <!-- Upload Controls -->
                    <div class="flex-1 min-w-0 w-full space-y-3">
                        <div>
                            <label for="fotoInput" class="block text-xs font-bold text-slate-700 mb-1.5">
                                Pilih File Foto Profil Baru:
                            </label>
                            <input type="file" name="foto" id="fotoInput" accept="image/jpeg,image/png,image/jpg,image/webp" required
                                onchange="previewSelectedImage(this)"
                                class="block w-full text-xs text-slate-500
                                file:mr-3 file:py-2.5 file:px-4
                                file:rounded-xl file:border-0
                                file:text-xs file:font-bold
                                file:bg-emerald-50 file:text-emerald-800
                                hover:file:bg-emerald-100
                                border border-slate-300 rounded-xl bg-slate-50 p-1 cursor-pointer">
                            <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                                <i class="fa-solid fa-circle-info text-emerald-600"></i>
                                Format: <strong>JPG, PNG, atau WEBP</strong> (Ukuran maksimal <strong>2MB</strong>)
                            </p>
                            @error('foto')
                                <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <button type="submit"
                                class="py-2.5 px-5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition duration-150 flex items-center gap-2 border border-emerald-700 cursor-pointer">
                                <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i> Simpan Foto Profil
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            @if($user->hasFoto())
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Ingin menghapus foto dan kembali menggunakan logo inisial?</span>
                    <form action="{{ route('karyawan.profile.foto.delete') }}" method="POST" id="deleteFotoForm">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="confirmDeleteFoto()"
                            class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg text-xs border border-rose-200 transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-trash-can"></i> Hapus Foto
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- User Information Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <i class="fa-solid fa-id-card-clip text-emerald-700"></i> Rincian Informasi Pegawai
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block">{{ $user->tipe_identitas_label }} (Nomor Identitas):</span>
                <span class="text-sm font-black text-slate-900 font-mono">{{ $user->nip }}</span>
            </div>
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block">Nama Lengkap:</span>
                <span class="text-sm font-bold text-slate-900">{{ $user->name }}</span>
            </div>
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block">Kategori &amp; Jabatan:</span>
                <span class="text-sm font-bold text-emerald-800">{{ $user->jabatan ?? 'Pegawai' }}</span>
                @if($user->asal_instansi)
                    <span class="text-[11px] text-slate-600 block mt-0.5">({{ $user->asal_instansi }})</span>
                @endif
            </div>
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block">No. HP / WhatsApp:</span>
                <span class="text-sm font-bold text-slate-900 font-mono">{{ $user->no_hp ?? '-' }}</span>
            </div>
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block">Alamat Email:</span>
                <span class="text-sm font-bold text-slate-900 truncate block">{{ $user->email ?? '-' }}</span>
            </div>
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block">Status Akun:</span>
                <span class="text-sm font-bold text-amber-700 capitalize">{{ $user->role }} (Aktif)</span>
            </div>
        </div>
    </div>

    <!-- Password Update Form -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-lock text-emerald-700"></i> Form Ubah Password
        </h2>

        <form action="{{ route('karyawan.profile.password') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1">
                    Password Saat Ini (Password Default: <code class="bg-emerald-50 px-1 py-0.5 rounded text-emerald-800 font-mono font-bold">password</code>)
                </label>
                <input type="password" name="current_password" id="current_password" required
                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-xs font-medium focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600">
                @error('current_password')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1">
                    Password Baru
                </label>
                <input type="password" name="password" id="password" required
                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-xs font-medium focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600">
                @error('password')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">
                    Konfirmasi Password Baru
                </label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-xs font-medium focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600">
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-lg shadow-emerald-900/30 transition border border-emerald-700 flex items-center justify-center gap-2 cursor-pointer">
                <i class="fa-solid fa-save text-amber-300"></i> Simpan Password Baru
            </button>
        </form>
    </div>
</div>

<script>
    function previewSelectedImage(input) {
        const preview = document.getElementById('imagePreview');
        const placeholder = document.getElementById('imagePlaceholder');
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Check size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran Terlalu Besar',
                    text: 'Ukuran foto maksimal adalah 2MB. Silakan pilih foto dengan ukuran lebih kecil.',
                    confirmButtonColor: '#064e3b'
                });
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function confirmDeleteFoto() {
        Swal.fire({
            title: 'Hapus Foto Profil?',
            text: 'Foto profil Anda akan dihapus dan tampilan akun akan kembali menggunakan inisial nama.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Foto',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteFotoForm').submit();
            }
        });
    }
</script>
@endsection

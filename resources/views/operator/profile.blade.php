@extends('layouts.app')

@section('title', 'Profil & Keamanan Akun Operator - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header Banner -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-emerald-700"></i> Profil &amp; Keamanan Akun Operator
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola Nomor Identitas (NIP), nama tampilan, dan kata sandi akun operator Anda.
            </p>
        </div>
        <div class="flex items-center gap-2 bg-emerald-50 px-3 py-2 rounded-xl border border-emerald-200 text-xs font-bold text-emerald-900 self-start sm:self-auto">
            <i class="fa-solid fa-shield-halved text-emerald-700"></i>
            <span>Hak Akses: <strong class="text-emerald-950">Operator Presensi</strong></span>
        </div>
    </div>

    <!-- Identity Summary Card -->
    <div class="bg-gradient-to-r from-[#064e3b] to-emerald-800 rounded-3xl p-6 text-white shadow-lg border-2 border-amber-400 relative overflow-hidden">
        <div class="absolute -right-6 -bottom-6 w-36 h-36 bg-white/5 rounded-full pointer-events-none"></div>
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 relative z-10 text-center sm:text-left">
            <div class="relative shrink-0">
                @if($user->foto_url)
                    <img src="{{ $user->foto_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-2xl object-cover shadow-md border-2 border-amber-400">
                @else
                    <div class="w-16 h-16 rounded-2xl bg-white text-[#064e3b] font-black text-2xl flex items-center justify-center shadow-md shrink-0 border-2 border-amber-400">
                        {{ $user->inisial }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-400 text-slate-950">
                        Akun Operator
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-900/80 text-emerald-100 border border-emerald-700">
                        Pengadilan Tinggi Pontianak
                    </span>
                </div>
                <h2 class="text-lg sm:text-xl font-black text-white mt-1 truncate">{{ $user->name }}</h2>
                <p class="text-xs text-amber-200 font-mono font-bold mt-0.5">
                    NIP / Identitas Login: <span class="bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-700/60">{{ $user->nip }}</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Foto Profil Operator Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center text-lg font-bold border border-emerald-100 shrink-0">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div>
                    <h3 class="font-black text-sm text-slate-900">Foto Profil Operator</h3>
                    <p class="text-[11px] text-slate-500">Unggah foto profil resmi untuk akun Operator Presensi</p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <form action="{{ route('operator.profile.foto') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 flex-wrap">
                    @csrf
                    <input type="file" name="foto" id="operator_foto_input" accept="image/jpeg,image/png,image/jpg,image/webp" required
                        class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 border border-slate-300 rounded-xl bg-slate-50 p-1 cursor-pointer">
                    <button type="submit" class="py-2 px-3.5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-sm transition border border-emerald-700 cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-upload text-amber-300"></i> Simpan
                    </button>
                </form>

                @if($user->hasFoto())
                    <form action="{{ route('operator.profile.foto.delete') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Hapus foto profil operator?')" class="py-2 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl text-xs border border-rose-200 transition cursor-pointer flex items-center gap-1">
                            <i class="fa-solid fa-trash-can"></i> Hapus
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @error('foto')
            <p class="text-xs text-rose-600 font-semibold mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Form 1: Ubah NIP & Nama -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-slate-50 p-5 border-b border-slate-200 flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-base font-bold shadow-xs">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h3 class="font-black text-sm text-slate-900">Ubah Nomor Identitas (NIP)</h3>
                    <p class="text-[11px] text-slate-500">Perbarui NIP yang digunakan untuk masuk ke portal</p>
                </div>
            </div>

            <form action="{{ route('operator.profile.nip') }}" method="POST" class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                @csrf

                <div class="space-y-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Lengkap *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-user text-xs"></i>
                            </div>
                            <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500 @error('name') border-rose-500 @enderror">
                        </div>
                        @error('name')
                            <p class="text-[11px] text-rose-600 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nomor Identitas (NIP) -->
                    <div>
                        <label for="nip" class="block text-xs font-bold text-slate-700 mb-1">
                            Nomor Identitas (NIP) Baru *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-id-badge text-xs"></i>
                            </div>
                            <input type="text" name="nip" id="nip" required value="{{ old('nip', $user->nip) }}"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-800 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500 @error('nip') border-rose-500 @enderror">
                        </div>
                        @error('nip')
                            <p class="text-[11px] text-rose-600 font-bold mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-[10px] text-slate-500 mt-1">Gunakan NIP ini pada form login saat membuka aplikasi.</p>
                    </div>

                    <!-- Password Saat Ini untuk Verifikasi -->
                    <div class="pt-2 border-t border-slate-100">
                        <label for="current_password_for_nip" class="block text-xs font-bold text-slate-700 mb-1">
                            Password Saat Ini (Konfirmasi Keamanan) *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </div>
                            <input type="password" name="current_password" id="current_password_for_nip" required placeholder="Masukkan password Anda untuk konfirmasi"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500 @error('current_password') border-rose-500 @enderror">
                        </div>
                        @error('current_password')
                            <p class="text-[11px] text-rose-600 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full py-2.5 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs shadow-md transition duration-150 flex items-center justify-center gap-2 border border-emerald-700 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk text-amber-300"></i> Simpan Perubahan NIP
                    </button>
                </div>
            </form>
        </div>

        <!-- Form 2: Ubah Password Akun -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-slate-50 p-5 border-b border-slate-200 flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-base font-bold shadow-xs">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h3 class="font-black text-sm text-slate-900">Ubah Password Akun</h3>
                    <p class="text-[11px] text-slate-500">Gunakan kombinasi kata sandi yang kuat dan aman</p>
                </div>
            </div>

            <form action="{{ route('operator.profile.password') }}" method="POST" class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                @csrf

                <div class="space-y-4">
                    <!-- Password Lama -->
                    <div>
                        <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1">
                            Password Saat Ini *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </div>
                            <input type="password" name="current_password" id="current_password" required placeholder="Masukkan password lama"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Password Baru -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1">
                            Password Baru *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-key text-xs"></i>
                            </div>
                            <input type="password" name="password" id="password" required placeholder="Minimal 6 karakter"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500 @error('password') border-rose-500 @enderror">
                        </div>
                        @error('password')
                            <p class="text-[11px] text-rose-600 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Konfirmasi Password Baru -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">
                            Konfirmasi Password Baru *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-circle-check text-xs"></i>
                            </div>
                            <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Ketik ulang password baru"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black rounded-xl text-xs shadow-md transition duration-150 flex items-center justify-center gap-2 border border-amber-400 cursor-pointer">
                        <i class="fa-solid fa-lock-open"></i> Perbarui Password
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
@endsection

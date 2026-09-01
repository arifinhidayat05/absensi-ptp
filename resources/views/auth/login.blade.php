@extends('layouts.app')

@section('title', 'Login Presensi - Pengadilan Tinggi Pontianak')

@section('content')
<div class="min-h-[82vh] flex flex-col justify-center items-center py-6">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        
        <!-- PPTK Deep Green & Gold Header with Official Logo -->
        <div class="bg-[#064e3b] p-8 text-center text-white relative border-b-4 border-amber-400">
            <div class="mb-4">
                <img src="{{ asset('LOGO-PPTK.png') }}" alt="Logo Pengadilan Tinggi Pontianak" class="w-24 h-24 mx-auto object-contain drop-shadow-xl hover:scale-105 transition-transform duration-300">
            </div>
            <h2 class="text-xl font-black tracking-tight text-white uppercase">PRESENSI ONLINE</h2>
            <h3 class="text-sm font-extrabold text-amber-300 tracking-wide mt-0.5">PENGADILAN TINGGI PONTIANAK</h3>
            <p class="text-[11px] text-emerald-100 font-semibold mt-2 bg-emerald-950/60 py-1.5 px-3 rounded-full inline-block border border-emerald-800/80">
                <i class="fa-solid fa-location-crosshairs text-amber-400 me-1"></i> Realtime GPS &amp; Foto Wajah Pegawai
            </p>
        </div>

        <!-- Form Body -->
        <div class="p-8 space-y-6 bg-white">
            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Nomor Identitas Field -->
                <div>
                    <label for="nip" class="block text-xs font-extrabold uppercase tracking-wider text-slate-800 mb-2">
                        Nomor Identitas
                    </label>
                    <div class="relative rounded-2xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-emerald-700">
                            <i class="fa-solid fa-id-card text-base"></i>
                        </div>
                        <input type="text" name="nip" id="nip" value="{{ old('nip') }}" required autofocus
                            class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-300 rounded-2xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 text-sm font-extrabold placeholder:text-slate-400 transition shadow-inner"
                            placeholder="Masukkan Nomor Identitas Anda">
                    </div>
                    @error('nip')
                        <p class="mt-1.5 text-xs text-rose-600 font-extrabold flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-xs font-extrabold uppercase tracking-wider text-slate-800 mb-2">
                        Password
                    </label>
                    <div class="relative rounded-2xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-emerald-700">
                            <i class="fa-solid fa-lock text-base"></i>
                        </div>
                        <input type="password" name="password" id="password" required
                            class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-300 rounded-2xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 text-sm font-extrabold placeholder:text-slate-400 transition shadow-inner"
                            placeholder="Masukkan Password">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-600 font-extrabold flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 w-4 h-4">
                        <span class="ml-2 text-xs text-slate-700 font-bold">Ingat Saya</span>
                    </label>
                    <span class="text-[11px] text-slate-500 font-medium">Bantuan: Hubungi Operator</span>
                </div>

                <!-- PPTK Green Button with Gold Focus Ring -->
                <button type="submit" class="w-full py-4 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-black rounded-2xl shadow-xl shadow-emerald-900/30 hover:shadow-emerald-900/50 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 transition duration-200 text-sm flex items-center justify-center gap-2 border border-emerald-700">
                    <span>MASUK KE SISTEM</span>
                    <i class="fa-solid fa-arrow-right text-xs text-amber-300"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

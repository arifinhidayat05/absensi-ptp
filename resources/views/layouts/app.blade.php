<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Presensi - Pengadilan Tinggi Pontianak')</title>

    <!-- Favicon using LOGO-PPTK.png -->
    <link rel="icon" type="image/png" href="{{ asset('LOGO-PPTK.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('LOGO-PPTK.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
        .leaflet-container {
            border-radius: 0.75rem;
            z-index: 10;
        }
        .pttk-gradient {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #065f46 100%);
        }
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    @auth
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" onclick="toggleSidebar()"
        class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-xs hidden md:hidden transition-opacity duration-300"></div>

    <!-- LEFT SIDEBAR -->
    <aside id="sidebarDrawer"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-[#064e3b] text-white flex flex-col border-r-2 border-amber-400 shadow-2xl transition-transform duration-300 transform -translate-x-full md:translate-x-0">
        
        <!-- Sidebar Brand Header -->
        <div class="p-4 border-b border-emerald-800/80 flex items-center justify-between">
            <a href="{{ Auth::user()->isOperator() ? route('operator.dashboard') : route('karyawan.dashboard') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('LOGO-PPTK.png') }}" alt="Logo" class="w-10 h-10 object-contain drop-shadow-md transition-transform group-hover:scale-105">
                <div>
                    <h1 class="font-black text-xs sm:text-sm tracking-tight text-white leading-tight">
                        PT PONTIANAK
                    </h1>
                    <span class="text-[10px] text-amber-300 font-extrabold block">
                        Presensi Pegawai
                    </span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button type="button" onclick="toggleSidebar()" class="md:hidden text-emerald-200 hover:text-white p-1.5 rounded-lg hover:bg-emerald-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Role Badge Pill -->
        <div class="px-4 pt-3 pb-1">
            <div class="bg-emerald-950/60 border border-emerald-700/60 rounded-xl px-3 py-1.5 flex items-center justify-between text-[11px]">
                <span class="text-slate-300 font-medium">Mode:</span>
                <span class="font-black text-amber-300 uppercase tracking-wider text-[10px]">
                    {{ Auth::user()->isOperator() ? 'Portal Operator' : 'Portal Pegawai' }}
                </span>
            </div>
        </div>

        <!-- Navigation Menu Links (Scrollable) -->
        <nav class="flex-1 overflow-y-auto sidebar-scroll px-3 py-3 space-y-1 text-xs">
            @if(Auth::user()->isOperator())
                <!-- MENU SECTION: UTAMA -->
                <div class="px-2 pt-2 pb-1 text-[10px] font-black uppercase tracking-wider text-emerald-300/60">
                    Menu Utama
                </div>

                <a href="{{ route('operator.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.dashboard') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-gauge w-5 text-center text-sm {{ request()->routeIs('operator.dashboard') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('operator.schedules.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.schedules.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-clock w-5 text-center text-sm {{ request()->routeIs('operator.schedules.*') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Jam Kerja</span>
                </a>

                <a href="{{ route('operator.holidays.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.holidays.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-calendar-xmark w-5 text-center text-sm {{ request()->routeIs('operator.holidays.*') ? 'text-amber-300' : 'text-rose-400' }}"></i>
                    <span>Hari Libur</span>
                </a>

                <!-- MENU SECTION: KEPEGAWAIAN -->
                <div class="px-2 pt-4 pb-1 text-[10px] font-black uppercase tracking-wider text-emerald-300/60">
                    Kepegawaian
                </div>

                <a href="{{ route('operator.employees.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.employees.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-users w-5 text-center text-sm {{ request()->routeIs('operator.employees.*') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Data Pegawai</span>
                </a>

                <a href="{{ route('operator.attendances.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.attendances.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-clipboard-user w-5 text-center text-sm {{ request()->routeIs('operator.attendances.*') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Input / Edit Presensi</span>
                </a>

                <a href="{{ route('operator.leaves.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.leaves.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-calendar-check w-5 text-center text-sm {{ request()->routeIs('operator.leaves.*') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Cuti Pegawai</span>
                </a>

                <!-- MENU SECTION: LAPORAN & SISTEM -->
                <div class="px-2 pt-4 pb-1 text-[10px] font-black uppercase tracking-wider text-emerald-300/60">
                    Laporan &amp; Sistem
                </div>

                <a href="{{ route('operator.reports.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.reports.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-file-invoice w-5 text-center text-sm {{ request()->routeIs('operator.reports.*') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Laporan Presensi</span>
                </a>

                <a href="{{ route('operator.location.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.location.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-sliders w-5 text-center text-sm {{ request()->routeIs('operator.location.*') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Pengaturan</span>
                </a>

                <a href="{{ route('operator.profile') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('operator.profile') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-user-shield w-5 text-center text-sm {{ request()->routeIs('operator.profile') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Profil &amp; Keamanan</span>
                </a>
            @else
                <!-- MENU KARYAWAN -->
                <div class="px-2 pt-2 pb-1 text-[10px] font-black uppercase tracking-wider text-emerald-300/60">
                    Menu Presensi
                </div>

                <a href="{{ route('karyawan.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('karyawan.dashboard') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-camera w-5 text-center text-sm {{ request()->routeIs('karyawan.dashboard') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Presensi Hari Ini</span>
                </a>

                <a href="{{ route('karyawan.riwayat') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('karyawan.riwayat') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-history w-5 text-center text-sm {{ request()->routeIs('karyawan.riwayat') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Riwayat Presensi</span>
                </a>

                <a href="{{ route('karyawan.cuti.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('karyawan.cuti.*') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-calendar-check w-5 text-center text-sm {{ request()->routeIs('karyawan.cuti.*') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Pengajuan Cuti</span>
                </a>

                <div class="px-2 pt-4 pb-1 text-[10px] font-black uppercase tracking-wider text-emerald-300/60">
                    Pengaturan Akun
                </div>

                <a href="{{ route('karyawan.profile') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition duration-150 {{ request()->routeIs('karyawan.profile') ? 'bg-emerald-800 text-amber-300 border border-amber-400/40 shadow-sm font-extrabold' : 'text-emerald-100 hover:bg-emerald-800/70 hover:text-white font-bold' }}">
                    <i class="fa-solid fa-key w-5 text-center text-sm {{ request()->routeIs('karyawan.profile') ? 'text-amber-300' : 'text-emerald-300' }}"></i>
                    <span>Ubah Password</span>
                </a>
            @endif
        </nav>

        <!-- Sidebar Footer: User Card & Logout -->
        <div class="p-3 border-t border-emerald-800/80 bg-emerald-950/40">
            <div class="flex items-center justify-between gap-2">
                <a href="{{ Auth::user()->isOperator() ? route('operator.profile') : route('karyawan.profile') }}" class="flex items-center gap-2.5 min-w-0 flex-1 group" title="Buka Profil">
                    @if(Auth::user()->foto_url)
                        <img src="{{ Auth::user()->foto_url }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-xl object-cover border border-amber-400 shrink-0 group-hover:scale-105 transition-transform">
                    @else
                        <div class="w-8 h-8 rounded-xl bg-emerald-800 text-amber-300 font-black text-xs flex items-center justify-center border border-emerald-700 shrink-0 group-hover:scale-105 transition-transform">
                            {{ Auth::user()->inisial }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-white truncate group-hover:text-amber-300 transition-colors">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-amber-300 font-mono font-medium truncate">
                            {{ Auth::user()->nip }}
                        </div>
                    </div>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 rounded-xl bg-emerald-800 hover:bg-rose-600 text-emerald-100 hover:text-white border border-emerald-700/60 transition duration-200 shadow-sm flex items-center justify-center cursor-pointer shrink-0" title="Keluar">
                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    @endauth

    <!-- WRAPPER: Main content area (Offset by sidebar width on desktop if authenticated) -->
    <div class="flex-1 flex flex-col {{ Auth::check() ? 'md:pl-64' : '' }} min-h-screen">
        
        <!-- TOP APP BAR -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Left: Mobile Menu Toggle Button & Page Header / Title -->
                    <div class="flex items-center gap-3">
                        @auth
                        <!-- Mobile Hamburger Button -->
                        <button type="button" onclick="toggleSidebar()"
                            class="md:hidden p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-bars text-base"></i>
                        </button>
                        @endauth

                        @guest
                        <a href="{{ route('login') }}" class="flex items-center gap-2.5">
                            <img src="{{ asset('LOGO-PPTK.png') }}" alt="Logo" class="w-9 h-9 object-contain">
                            <div>
                                <span class="font-black text-sm text-slate-900 tracking-tight block leading-tight">PRESENSI PT PONTIANAK</span>
                                <span class="text-[10px] text-emerald-700 font-bold block leading-none">Pengadilan Tinggi Pontianak</span>
                            </div>
                        </a>
                        @else
                        <div>
                            <span class="text-xs sm:text-sm font-black text-slate-900 tracking-tight block leading-tight">
                                Pengadilan Tinggi Pontianak
                            </span>
                            <span class="text-[10px] text-slate-500 font-medium hidden sm:block">
                                {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                            </span>
                        </div>
                        @endguest
                    </div>

                    <!-- Right: Live Clock & Profile -->
                    <div class="flex items-center gap-2 sm:gap-4">
                        <div class="hidden sm:flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-mono font-bold text-slate-800">
                            <i class="fa-regular fa-clock text-emerald-700"></i>
                            <span id="liveHeaderClock">{{ \Carbon\Carbon::now()->format('H:i:s') }}</span>
                            <span class="text-[10px] text-slate-500 font-sans font-bold">WIB</span>
                        </div>

                        @auth
                        <a href="{{ Auth::user()->isOperator() ? route('operator.profile') : route('karyawan.profile') }}"
                            class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-100 transition group" title="Buka Profil & Pengaturan Akun">
                            @if(Auth::user()->foto_url)
                                <img src="{{ Auth::user()->foto_url }}" alt="{{ Auth::user()->name }}" class="w-9 h-9 rounded-xl object-cover shadow-sm border-2 border-amber-400 group-hover:scale-105 transition-transform shrink-0">
                            @else
                                <div class="w-9 h-9 rounded-xl bg-[#064e3b] text-amber-300 flex items-center justify-center font-black text-xs shadow-sm border border-emerald-700 group-hover:scale-105 transition-transform shrink-0">
                                    {{ Auth::user()->inisial }}
                                </div>
                            @endif
                            <div class="hidden lg:block text-left">
                                <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[140px] group-hover:text-emerald-700 transition-colors">{{ Auth::user()->name }}</div>
                                <div class="text-[10px] text-emerald-700 font-semibold">{{ Auth::user()->isOperator() ? 'Operator Presensi' : 'Pegawai' }}</div>
                            </div>
                        </a>
                        @endauth
                    </div>

                </div>
            </div>
        </header>

        <!-- MAIN BODY CONTENT -->
        <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 md:pb-6">
            <!-- Toast Alerts -->
            @if(session('success'))
                <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between shadow-sm" role="alert">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-xl"></i>
                        <span class="font-bold text-xs sm:text-sm">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-5 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-900 flex items-center justify-between shadow-sm" role="alert">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-info text-sky-600 text-xl"></i>
                        <span class="font-bold text-xs sm:text-sm">{{ session('info') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-sky-600 hover:text-sky-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-5 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center justify-between shadow-sm" role="alert">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xl"></i>
                        <span class="font-bold text-xs sm:text-sm">{{ session('warning') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-amber-600 hover:text-amber-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between shadow-sm" role="alert">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-xl"></i>
                        <span class="font-bold text-xs sm:text-sm">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between shadow-sm" role="alert">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-xl"></i>
                        <div class="text-xs sm:text-sm font-bold">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- FOOTER -->
        <footer class="bg-white border-t border-slate-200 py-4 mt-auto mb-20 md:mb-0">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-600">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('LOGO-PPTK.png') }}" alt="Logo" class="w-5 h-5 object-contain">
                    <span class="font-bold text-slate-800">Pengadilan Tinggi Pontianak</span>
                    <span>&bull;</span>
                    <span>Sistem Presensi Pegawai</span>
                </div>
                <div class="text-slate-500 text-[11px]">
                    &copy; {{ date('Y') }} Pengadilan Tinggi Pontianak &bull; Realtime GPS &amp; Face Recognition
                </div>
            </div>
        </footer>

    </div>

    <!-- Fixed Mobile Bottom Navigation Bar (For Quick Thumbs-Reach on Phones) -->
    @auth
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-[#064e3b] border-t-2 border-amber-400 shadow-[0_-8px_30px_rgba(0,0,0,0.35)] pb-safe">
        <div class="flex items-center justify-around px-1 h-16">
            @if(Auth::user()->isOperator())
                <!-- Dashboard -->
                <a href="{{ route('operator.dashboard') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('operator.dashboard') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    <i class="fa-solid fa-gauge text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Dashboard</span>
                </a>
                <!-- Jam Kerja -->
                <a href="{{ route('operator.schedules.index') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('operator.schedules.*') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    <i class="fa-solid fa-clock text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Jam Kerja</span>
                </a>
                <!-- Pegawai -->
                <a href="{{ route('operator.employees.index') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('operator.employees.*') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    <i class="fa-solid fa-users text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Pegawai</span>
                </a>
                <!-- Cuti -->
                <a href="{{ route('operator.leaves.index') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('operator.leaves.*') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    <i class="fa-solid fa-calendar-check text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Cuti</span>
                </a>
                <!-- Menu / Sidebar Drawer -->
                <button type="button" onclick="toggleSidebar()" class="flex flex-col items-center justify-center flex-1 py-1 text-center text-emerald-200/70 hover:text-white cursor-pointer">
                    <i class="fa-solid fa-bars text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Menu</span>
                </button>
            @else
                <!-- Presensi -->
                <a href="{{ route('karyawan.dashboard') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('karyawan.dashboard') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    <i class="fa-solid fa-camera text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Presensi</span>
                </a>
                <!-- Riwayat -->
                <a href="{{ route('karyawan.riwayat') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('karyawan.riwayat') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    <i class="fa-solid fa-history text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Riwayat</span>
                </a>
                <!-- Cuti -->
                <a href="{{ route('karyawan.cuti.index') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('karyawan.cuti.*') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    <i class="fa-solid fa-calendar-check text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Cuti</span>
                </a>
                <!-- Profil / Akun -->
                <a href="{{ route('karyawan.profile') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-center {{ request()->routeIs('karyawan.profile') ? 'text-amber-300 font-bold' : 'text-emerald-200/70 hover:text-white' }}">
                    @if(Auth::user()->foto_url)
                        <img src="{{ Auth::user()->foto_url }}" alt="Foto" class="w-5 h-5 rounded-full object-cover mb-0.5 border border-amber-300">
                    @else
                        <i class="fa-solid fa-user-gear text-base mb-0.5"></i>
                    @endif
                    <span class="text-[9px] tracking-tight">Akun</span>
                </a>
                <!-- Menu / Sidebar Drawer -->
                <button type="button" onclick="toggleSidebar()" class="flex flex-col items-center justify-center flex-1 py-1 text-center text-emerald-200/70 hover:text-white cursor-pointer">
                    <i class="fa-solid fa-bars text-base mb-0.5"></i>
                    <span class="text-[9px] tracking-tight">Menu</span>
                </button>
            @endif
        </div>
    </nav>
    @endauth

    <!-- Pustaka Peta Leaflet JS untuk Geolokasi GPS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        /**
         * Membuka atau menutup bilah menu samping (Sidebar Drawer) pada layar ponsel / perangkat mobile.
         * Menampilkan efek latar belakang gelap (backdrop) saat menu samping aktif di HP.
         */
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarDrawer');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar) return;

            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                if (backdrop) backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'md:overflow-auto');
            } else {
                sidebar.classList.add('-translate-x-full');
                if (backdrop) backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'md:overflow-auto');
            }
        }

        /**
         * Jam Digital Waktu Nyata (WIB) pada bilah atas (Header Top Bar).
         * Diperbarui setiap 1 detik secara otomatis.
         */
        setInterval(() => {
            const el = document.getElementById('liveHeaderClock');
            if (el) {
                const now = new Date();
                const pad = n => n.toString().padStart(2, '0');
                el.innerText = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            }
        }, 1000);
    </script>

    @stack('scripts')
</body>
</html>

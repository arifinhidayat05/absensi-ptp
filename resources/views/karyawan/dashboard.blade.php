@extends('layouts.app')

@section('title', 'Presensi Pegawai Realtime - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-5">

    <!-- Welcome Header & Live Clock with PPTK Deep Green & Gold Theme -->
    <div class="bg-[#064e3b] rounded-3xl p-5 sm:p-7 text-white shadow-xl border border-emerald-800 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
            <div class="flex items-center gap-4">
                <a href="{{ route('karyawan.profile') }}" class="relative group shrink-0" title="Buka Profil & Foto">
                    @if($user->foto_url)
                        <img src="{{ $user->foto_url }}" alt="{{ $user->name }}" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border-2 border-amber-400 shadow-md group-hover:scale-105 transition-transform">
                    @else
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white text-[#064e3b] flex items-center justify-center text-xl sm:text-2xl font-black shadow-md border-2 border-amber-400 group-hover:scale-105 transition-transform">
                            {{ $user->inisial }}
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-black/30 rounded-2xl opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs transition">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                </a>
                <div class="min-w-0">
                    <span class="px-3.5 py-1 bg-amber-400 text-slate-950 text-xs font-black rounded-full uppercase tracking-wider mb-2 inline-block shadow">
                        <i class="fa-solid fa-shield-halved me-1 text-emerald-900"></i> Portal Pegawai &amp; Magang
                    </span>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black tracking-tight text-white flex items-center gap-2 truncate">
                        Halo, {{ $user->name }}
                    </h1>
                    <div class="text-xs text-emerald-100 font-bold mt-1.5 flex items-center gap-2 flex-wrap">
                        <span class="bg-emerald-950/80 px-3 py-1 rounded-xl border border-emerald-800 text-emerald-100">
                            {{ $user->tipe_identitas_label }}: <strong class="text-amber-300 font-mono">{{ $user->nip }}</strong>
                        </span>
                        <span class="bg-emerald-950/80 px-3 py-1 rounded-xl border border-emerald-800 text-emerald-100">
                            Status: <strong class="text-amber-300">{{ $user->jabatan ?? 'Pegawai' }}</strong>
                        </span>
                        @if($user->asal_instansi)
                            <span class="bg-emerald-950/80 px-3 py-1 rounded-xl border border-emerald-800 text-emerald-100">
                                <i class="fa-solid fa-building-columns text-amber-300 me-1"></i> <strong class="text-emerald-100">{{ $user->asal_instansi }}</strong>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Live Clock Card -->
            <div class="bg-emerald-950/90 border border-emerald-800/90 rounded-2xl p-3.5 text-center md:text-right min-w-[220px] shadow-lg">
                <div class="text-xs font-black text-amber-300 flex items-center justify-center md:justify-end gap-1.5 mb-0.5">
                    <i class="fa-regular fa-calendar-days text-amber-400"></i>
                    <span id="date-display">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                </div>
                <div class="text-2xl sm:text-3xl font-black font-mono tracking-wider text-amber-400" id="clock-display">
                    {{ \Carbon\Carbon::now()->format('H:i:s') }} <span class="text-xs text-emerald-200 font-sans font-extrabold">WIB</span>
                </div>
            </div>
        </div>
    </div>

    @php
        // Detect current active open session for Hero Spotlight
        $activeSessionKey = null;
        foreach(['masuk', 'istirahat', 'masuk_istirahat', 'pulang'] as $t) {
            if ($cards[$t]['window']['is_open'] && !$cards[$t]['has_attended']) {
                $activeSessionKey = $t;
                break;
            }
        }

        // Count attended today
        $totalAttendedToday = 0;
        foreach(['masuk', 'istirahat', 'masuk_istirahat', 'pulang'] as $t) {
            if ($cards[$t]['has_attended']) $totalAttendedToday++;
        }
    @endphp

    <!-- Mobile-Optimized Active Session Hero Spotlight Banner -->
    @if($activeSessionKey)
        @php
            $activeItem = $cards[$activeSessionKey];
            $activeWin = $activeItem['window'];
        @endphp
        <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 rounded-3xl p-4 sm:p-5 shadow-lg border-2 border-amber-300 text-slate-950 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3.5 text-center sm:text-left">
                <div class="w-12 h-12 rounded-2xl bg-slate-950 text-amber-300 flex items-center justify-center text-xl font-bold shadow-md shrink-0">
                    <i class="fa-solid fa-camera animate-pulse"></i>
                </div>
                <div>
                    <div class="flex items-center justify-center sm:justify-start gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-950 text-amber-300 animate-pulse">
                            Sesi Buka Sekarang
                        </span>
                        <span class="text-xs font-extrabold text-slate-900">{{ $activeWin['open_time'] }} - {{ $activeWin['close_time'] }} WIB</span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-black text-slate-950 mt-0.5">
                        {{ $activeItem['label'] }}
                    </h2>
                    <p class="text-xs font-bold text-slate-800">
                        Target Jam: <span class="font-mono underline">{{ $activeWin['target_time'] }} WIB</span>
                    </p>
                </div>
            </div>

            <button onclick="openCameraModal('{{ $activeSessionKey }}', '{{ $activeItem['label'] }}')"
                class="w-full sm:w-auto px-6 py-3.5 bg-[#064e3b] hover:bg-[#043d2e] text-white font-black rounded-2xl text-xs sm:text-sm shadow-xl shadow-emerald-950/40 transition duration-200 flex items-center justify-center gap-2 border-2 border-emerald-600 cursor-pointer shrink-0">
                <i class="fa-solid fa-camera text-amber-300 text-base"></i> ABSEN SEKARANG
            </button>
        </div>
    @endif

    <!-- Daily Schedule & Status Tracker Bar -->
    @if($schedule->is_libur)
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-lg font-bold shadow">
                    <i class="fa-solid fa-calendar-xmark"></i>
                </div>
                <div>
                    <h2 class="text-xs sm:text-sm font-bold text-slate-900">Hari Ini Libur ({{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }})</h2>
                    <p class="text-[11px] text-slate-600">Presensi tidak dibuka pada hari libur / akhir pekan.</p>
                </div>
            </div>
            <div class="text-xs font-bold text-rose-700 bg-white px-3 py-1.5 rounded-xl border border-rose-200 shadow-sm shrink-0">
                {{ $schedule->keterangan ?? 'Hari Libur' }}
            </div>
        </div>
    @else
        <!-- Daily Progress Bar (4 Sesi) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-list-check text-emerald-700"></i> Progres Presensi Hari Ini:
                </span>
                <span class="font-extrabold text-emerald-800 font-mono">{{ $totalAttendedToday }} / 4 Sesi Tercatat</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500" style="width: {{ ($totalAttendedToday / 4) * 100 }}%"></div>
            </div>
        </div>
    @endif

    <!-- 4 Attendance Cards Grid (Structured for Mobile & Desktop) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach(['masuk', 'istirahat', 'masuk_istirahat', 'pulang'] as $tipe)
            @php
                $item = $cards[$tipe];
                $win = $item['window'];
                $rec = $item['record'];
                $hasAttended = $item['has_attended'];
                $isRejected = $item['is_rejected'] ?? false;
                $isOpen = $win['is_open'];
                $isBefore = $win['is_before'];
                $isAfter = $win['is_after'];
                $isLibur = $win['is_libur'] ?? false;

                $iconMap = [
                    'masuk' => 'fa-right-to-bracket text-emerald-700 bg-emerald-50',
                    'istirahat' => 'fa-mug-hot text-amber-600 bg-amber-50',
                    'masuk_istirahat' => 'fa-briefcase text-teal-700 bg-teal-50',
                    'pulang' => 'fa-right-from-bracket text-emerald-700 bg-emerald-50',
                ];
            @endphp

            <div class="bg-white rounded-2xl border {{ $isOpen && !$hasAttended ? 'border-amber-400 ring-2 ring-amber-400/30' : ($isRejected ? 'border-rose-300 ring-2 ring-rose-500/20' : 'border-slate-200') }} shadow-sm hover:shadow-md transition-all p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden">
                
                <!-- Top Status Header -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-9 h-9 rounded-xl {{ $iconMap[$tipe] }} flex items-center justify-center text-base shadow-sm border border-slate-100">
                            <i class="fa-solid {{ explode(' ', $iconMap[$tipe])[0] }}"></i>
                        </div>
                        <div>
                            @if($hasAttended)
                                <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold flex items-center gap-1 border border-emerald-300">
                                    <i class="fa-solid fa-circle-check"></i> Tercatat
                                </span>
                            @elseif($isRejected)
                                <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white text-[11px] font-bold flex items-center gap-1 shadow-sm">
                                    <i class="fa-solid fa-circle-xmark"></i> Ditolak (ALFA)
                                </span>
                            @elseif($isLibur)
                                <span class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 text-[11px] font-bold border border-rose-200">
                                    Hari Libur
                                </span>
                            @elseif($isOpen)
                                <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white text-[11px] font-black animate-pulse flex items-center gap-1 shadow-sm">
                                    <i class="fa-solid fa-door-open text-amber-300"></i> DIBUKA
                                </span>
                            @elseif($isBefore)
                                <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-900 text-[11px] font-bold border border-amber-200">
                                    <i class="fa-solid fa-hourglass-start text-amber-600"></i> Belum Buka
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold">
                                    <i class="fa-solid fa-lock"></i> Ditutup
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Session Info -->
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 mb-1">{{ $item['label'] }}</h3>
                    <div class="text-xs text-slate-500 space-y-1 mb-3">
                        <div class="flex items-center justify-between">
                            <span>Target Jam:</span>
                            <span class="font-bold text-slate-900">{{ $win['target_time'] }} WIB</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                            <span>Jendela Buka:</span>
                            <span class="font-bold text-emerald-800">{{ $win['open_time'] }} - {{ $win['close_time'] }} WIB</span>
                        </div>
                    </div>

                    @if($hasAttended)
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-2.5 mb-3 space-y-1 text-xs text-emerald-900">
                            <div class="flex items-center justify-between font-bold">
                                <span><i class="fa-regular fa-clock me-1 text-emerald-700"></i> {{ \Carbon\Carbon::parse($rec->waktu)->format('H:i:s') }} WIB</span>
                                <span class="uppercase tracking-wider px-1.5 py-0.5 rounded text-[9px] {{ $rec->status === 'tepat_waktu' ? 'bg-emerald-700 text-white font-bold' : 'bg-amber-600 text-white font-bold' }}">
                                    {{ \App\Models\Attendance::getStatusLabel($rec->status) }}
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-600 truncate" title="{{ $rec->alamat }}">
                                <i class="fa-solid fa-location-dot text-rose-500 me-1"></i> {{ $rec->alamat }}
                            </div>
                            <button onclick="viewDetail('{{ asset($rec->foto) }}', '{{ $rec->latitude }}', '{{ $rec->longitude }}', '{{ $rec->alamat }}', '{{ $item['label'] }}', '{{ \Carbon\Carbon::parse($rec->waktu)->format('H:i:s') }} WIB')"
                                class="w-full py-1 bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-100 font-bold rounded-lg text-[10px] transition text-center block mt-1">
                                <i class="fa-solid fa-image me-1"></i> Lihat Foto &amp; Peta GPS
                            </button>
                        </div>
                    @elseif($isRejected)
                        <div class="bg-rose-50 border border-rose-200 rounded-xl p-2.5 mb-3 space-y-1 text-xs text-rose-900">
                            <div class="flex items-center justify-between font-bold">
                                <span><i class="fa-solid fa-circle-xmark me-1 text-rose-600"></i> Ditolak Operator</span>
                                <span class="uppercase tracking-wider px-1.5 py-0.5 rounded text-[9px] bg-rose-600 text-white font-bold">ALFA</span>
                            </div>
                            <div class="text-[10px] text-rose-700 font-medium leading-relaxed">
                                {{ $rec->catatan_operator ?? 'Foto tidak sesuai.' }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Action Button -->
                <div class="mt-2">
                    @if($hasAttended)
                        <button disabled class="w-full py-2.5 px-3 bg-slate-100 text-slate-400 font-bold rounded-xl text-xs cursor-not-allowed border border-slate-200 flex items-center justify-center gap-1">
                            <i class="fa-solid fa-check text-emerald-600"></i> Sudah Presensi
                        </button>
                    @elseif($isRejected && $isOpen)
                        <button onclick="openCameraModal('{{ $tipe }}', '{{ $item['label'] }}')"
                            class="w-full py-3 px-3 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md transition duration-200 flex items-center justify-center gap-1.5 animate-pulse">
                            <i class="fa-solid fa-camera"></i> ULANGI PRESENSI
                        </button>
                    @elseif($isRejected)
                        <button disabled class="w-full py-2.5 px-3 bg-rose-50 text-rose-500 font-bold rounded-xl text-xs border border-rose-200 cursor-not-allowed">
                            <i class="fa-solid fa-ban me-1"></i> Ditolak (ALFA)
                        </button>
                    @elseif($isLibur)
                        <button disabled class="w-full py-2.5 px-3 bg-rose-50 text-rose-600 font-bold rounded-xl text-xs border border-rose-200 cursor-not-allowed">
                            Hari Libur
                        </button>
                    @elseif($isOpen)
                        <button onclick="openCameraModal('{{ $tipe }}', '{{ $item['label'] }}')"
                            class="w-full py-3 px-3 bg-[#064e3b] hover:bg-[#043d2e] text-white font-black rounded-xl text-xs shadow-lg shadow-emerald-950/30 transition duration-200 flex items-center justify-center gap-2 border border-emerald-700">
                            <i class="fa-solid fa-camera text-amber-300"></i> ABSEN SEKARANG
                        </button>
                    @elseif($isBefore)
                        <button disabled class="w-full py-2.5 px-3 bg-amber-50 text-amber-800 font-bold rounded-xl text-xs border border-amber-200 cursor-not-allowed">
                            <i class="fa-solid fa-hourglass me-1 text-amber-600"></i> Buka {{ $win['open_time'] }} WIB
                        </button>
                    @else
                        <button disabled class="w-full py-2.5 px-3 bg-slate-100 text-slate-400 font-bold rounded-xl text-xs border border-slate-200 cursor-not-allowed">
                            <i class="fa-solid fa-lock me-1"></i> Ditutup
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

</div>

<!-- ========================================== -->
<!-- MOBILE-STRUCTURED CAMERA & GPS MODAL DIALOG -->
<!-- ========================================== -->
<div id="cameraModal" class="fixed inset-0 z-50 hidden bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 my-auto flex flex-col max-h-[96vh]">
        
        <!-- Modal Header -->
        <div class="bg-[#064e3b] text-white px-4 py-3.5 sm:px-5 sm:py-4 flex items-center justify-between border-b-2 border-amber-400 shrink-0">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-800 flex items-center justify-center text-amber-300 text-base border border-emerald-700">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div>
                    <h3 class="font-black text-sm sm:text-base text-white" id="modalTitle">Ambil Presensi Pegawai</h3>
                    <p class="text-[11px] text-amber-200 font-medium">Kamera Alami (Non-Mirror) &amp; Lokasi GPS</p>
                </div>
            </div>
            <button onclick="closeCameraModal()" class="w-8 h-8 rounded-full bg-emerald-900/80 hover:bg-rose-600 text-slate-200 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Content Body (Scrollable if needed on smaller phones) -->
        <div class="p-3.5 sm:p-5 space-y-3.5 overflow-y-auto">

            <!-- 1. Live Camera Stream Box (NO MIRRORING / NATURAL VIEW) -->
            <div class="bg-slate-950 rounded-2xl overflow-hidden relative h-[250px] sm:h-[280px] flex items-center justify-center shadow-inner border border-slate-800">
                
                <!-- HTML5 Natural Video Stream (Unmirrored) -->
                <video id="webcam" autoplay playsinline class="w-full h-full object-cover"></video>
                <canvas id="canvas" class="hidden"></canvas>

                <!-- Top Camera Controls Floating (Switch Lens & Mirror Mode) -->
                <div class="absolute top-2.5 right-2.5 z-20 flex items-center gap-1.5">
                    <!-- Toggle Mirror / Cermin -->
                    <button type="button" id="toggleMirrorBtn" onclick="toggleMirror()" title="Ganti Mode Cermin / Normal"
                        class="px-2.5 py-1.5 bg-slate-900/85 hover:bg-slate-800 text-white rounded-xl text-[11px] font-bold border border-slate-700 shadow-md backdrop-blur-sm flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-arrows-left-right text-amber-300"></i> <span id="mirrorBtnLabel">Cermin: OFF</span>
                    </button>

                    <!-- Switch Lens (Depan / Belakang) -->
                    <button type="button" id="toggleCameraBtn" onclick="toggleCameraFacing()" title="Ganti Kamera Depan/Belakang"
                        class="px-2.5 py-1.5 bg-slate-900/85 hover:bg-slate-800 text-white rounded-xl text-[11px] font-bold border border-slate-700 shadow-md backdrop-blur-sm flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-camera-rotate text-amber-300"></i> <span id="cameraBtnLabel">Lensa</span>
                    </button>
                </div>

                <!-- Top Left Status Badge -->
                <div class="absolute top-2.5 left-2.5 z-20 pointer-events-none">
                    <span id="livenessStepBadge" class="bg-slate-900/85 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow border border-slate-700 flex items-center gap-1 backdrop-blur-sm">
                        <i class="fa-solid fa-circle text-emerald-400 text-[8px] animate-ping"></i> Posisikan Wajah
                    </span>
                </div>

                <!-- Face Guide Oval SVG -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                    <svg class="w-44 h-52 sm:w-48 sm:h-56 transition-all duration-300 drop-shadow-md" viewBox="0 0 100 130">
                        <ellipse id="faceOval" cx="50" cy="65" rx="36" ry="46" fill="none" stroke="#ef4444" stroke-width="3" stroke-dasharray="4 2" />
                    </svg>
                </div>

                <!-- Bottom Status Banner Overlay -->
                <div class="absolute bottom-2 inset-x-2 pointer-events-none z-20">
                    <div class="bg-slate-950/90 backdrop-blur-md p-2 rounded-xl text-center border border-slate-800 shadow space-y-1">
                        <div class="text-[11px] font-black text-white flex items-center justify-center gap-1 truncate" id="livenessStatusText">
                            <i class="fa-solid fa-user-focus text-amber-400 me-1 animate-pulse"></i> Posisikan Wajah Anda di Dalam Lingkaran
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1 overflow-hidden">
                            <div id="livenessProgressBar" class="bg-amber-500 h-1 rounded-full transition-all duration-300" style="width: 25%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Compact GPS & Radius Status Card -->
            <div class="bg-slate-50 border border-slate-200 p-3 rounded-2xl text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <div class="font-bold text-slate-800 flex items-center text-[11px]">
                        <i class="fa-solid fa-location-dot text-emerald-700 me-1.5"></i> Status Lokasi GPS:
                    </div>
                    <!-- Toggle Map Accordion Button -->
                    <button type="button" onclick="toggleMapAccordion()" id="mapToggleBtn"
                        class="text-[10px] font-bold text-emerald-800 hover:text-emerald-950 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg border border-emerald-200 transition flex items-center gap-1">
                        <i class="fa-solid fa-map"></i> <span id="mapToggleText">Buka Peta</span>
                    </button>
                </div>

                <div class="text-[11px] font-mono font-bold text-slate-700" id="gps-coords">
                    Mendeteksi koordinat GPS...
                </div>
                <div class="text-[10px] text-slate-500 truncate" id="gps-address">
                    Mencari alamat lokasi...
                </div>

                <!-- Collapsible Map Container for Mobile -->
                <div id="mapContainer" class="hidden pt-1">
                    <div id="map" class="w-full h-[150px] bg-slate-200 rounded-xl shadow-inner border border-slate-300"></div>
                </div>
            </div>

            <!-- 3. Form & Big Submit Button -->
            <form id="attendanceForm" class="space-y-2">
                @csrf
                <input type="hidden" name="tipe" id="modalTipeInput">
                <input type="hidden" name="foto" id="modalFotoInput">
                <input type="hidden" name="latitude" id="modalLatInput">
                <input type="hidden" name="longitude" id="modalLngInput">
                <input type="hidden" name="alamat" id="modalAlamatInput">

                <button type="button" id="submitBtn" onclick="submitAttendance()"
                    class="w-full py-3.5 sm:py-4 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-black rounded-2xl text-xs sm:text-sm shadow-xl shadow-emerald-950/30 hover:shadow-emerald-950/50 transition duration-200 flex items-center justify-center gap-2 cursor-pointer border-2 border-emerald-700">
                    <i class="fa-solid fa-camera text-base text-amber-300"></i> AMBIL FOTO &amp; KIRIM PRESENSI
                </button>
            </form>
        </div>
    </div>
</div>

<!-- View Attendance Detail Modal -->
<div id="viewModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 my-auto">
        <div class="bg-[#064e3b] text-white p-4 flex items-center justify-between border-b-2 border-amber-400">
            <h3 class="font-black text-sm text-white" id="viewTitle">Detail Presensi</h3>
            <button onclick="closeViewModal()" class="w-8 h-8 rounded-full bg-emerald-900 text-slate-200 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <div class="rounded-2xl overflow-hidden shadow border border-slate-200 bg-slate-900 text-center">
                <img id="viewPhoto" src="" alt="Foto Absensi" class="w-full max-h-64 object-cover">
            </div>
            <div id="viewDetailMap" class="w-full h-44 rounded-xl border border-slate-200"></div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs space-y-1">
                <div class="font-bold text-slate-800" id="viewTimeInfo"></div>
                <div class="text-slate-600" id="viewAddressInfo"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Live Digital Clock
    function updateClock() {
        const now = new Date();
        const hrs = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        const secs = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('clock-display').innerHTML = `${hrs}:${mins}:${secs} <span class="text-xs text-amber-300 font-sans font-bold">WIB</span>`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Global variables
    let mediaStream = null;
    let leafletMap = null;
    let leafletMarker = null;
    let viewDetailLeafletMap = null;
    let viewDetailLeafletMarker = null;
    let currentLat = null;
    let currentLng = null;
    let livenessInterval = null;
    let isSubmitting = false;
    let isLocationInside = true;
    let prevFrameData = null;
    let validFaceTicks = 0;
    let motionHistory = [];

    let videoDevices = [];
    let currentDeviceIndex = 0;
    let currentFacingMode = 'user'; // 'user' (depan) or 'environment' (belakang)
    let isMirrored = false; // Default: OFF (Tidak mirror / Normal)

    function toggleMirror() {
        isMirrored = !isMirrored;
        const video = document.getElementById('webcam');
        const label = document.getElementById('mirrorBtnLabel');
        const btn = document.getElementById('toggleMirrorBtn');
        
        if (video) {
            video.style.transform = isMirrored ? 'scaleX(-1)' : 'none';
        }
        if (label) {
            label.innerText = isMirrored ? 'Cermin: ON' : 'Cermin: OFF';
        }
        if (btn) {
            if (isMirrored) {
                btn.className = 'px-2.5 py-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-xl text-[11px] font-black border border-amber-300 shadow-md backdrop-blur-sm flex items-center gap-1.5 transition active:scale-95 cursor-pointer';
            } else {
                btn.className = 'px-2.5 py-1.5 bg-slate-900/85 hover:bg-slate-800 text-white rounded-xl text-[11px] font-bold border border-slate-700 shadow-md backdrop-blur-sm flex items-center gap-1.5 transition active:scale-95 cursor-pointer';
            }
        }
    }

    async function getVideoDevices() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return [];
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            videoDevices = devices.filter(d => d.kind === 'videoinput');
            return videoDevices;
        } catch (e) {
            console.warn("enumerateDevices error:", e);
            return [];
        }
    }

    async function toggleCameraFacing() {
        const btn = document.getElementById('toggleCameraBtn');
        const label = document.getElementById('cameraBtnLabel');
        if (btn) {
            btn.disabled = true;
            if (label) label.innerText = 'Memutar...';
        }

        // Toggle facing mode state
        currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';

        // Also cycle device index if multiple physical devices exist
        if (videoDevices.length > 1) {
            currentDeviceIndex = (currentDeviceIndex + 1) % videoDevices.length;
        }

        await startCamera();

        if (btn) {
            btn.disabled = false;
            if (label) {
                label.innerText = (currentFacingMode === 'environment') ? 'Kamera Belakang' : 'Kamera Depan';
            }
        }
    }

    function toggleMapAccordion() {
        const mapCont = document.getElementById('mapContainer');
        const mapText = document.getElementById('mapToggleText');
        if (mapCont.classList.contains('hidden')) {
            mapCont.classList.remove('hidden');
            mapText.innerText = 'Tutup Peta';
            setTimeout(() => {
                if (leafletMap) leafletMap.invalidateSize();
            }, 200);
        } else {
            mapCont.classList.add('hidden');
            mapText.innerText = 'Buka Peta';
        }
    }

    /**
     * Membuka modal presensi, menginisialisasi kamera webcam, mendeteksi koordinat GPS,
     * serta memulai pemindaian keaslian wajah (face liveness detection).
     */
    function openCameraModal(tipe, label) {
        isSubmitting = false;
        isLocationInside = false; // Nilai awal false hingga GPS memverifikasi keberadaan dalam radius kantor
        validFaceTicks = 0;

        const officeLat = {{ $setting->latitude_kantor }};
        const officeLng = {{ $setting->longitude_kantor }};
        document.getElementById('modalLatInput').value = officeLat;
        document.getElementById('modalLngInput').value = officeLng;
        document.getElementById('modalAlamatInput').value = 'Mendeteksi lokasi GPS...';

        document.getElementById('modalTipeInput').value = tipe;
        document.getElementById('modalTitle').innerText = 'Ambil Presensi: ' + label;
        document.getElementById('cameraModal').classList.remove('hidden');

        startCamera();
        initGPSAndMap();
        startLivenessScan();
    }

    /**
     * Menutup modal presensi dan mematikan kamera serta scanner liveness untuk menghemat daya baterai.
     */
    function closeCameraModal() {
        stopCamera();
        stopLivenessScan();
        document.getElementById('cameraModal').classList.add('hidden');
    }

    /**
     * Memulai penangkapan video webcam WebRTC dengan konfigurasi resolusi ideal,
     * deteksi mode cermin (mirror mode), dan rotasi lensa kamera ponsel.
     */
    async function startCamera() {
        stopCamera();
        const video = document.getElementById('webcam');
        if (!video) return;

        video.style.transform = isMirrored ? 'scaleX(-1)' : 'none';

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            Swal.fire({
                icon: 'error',
                title: 'Browser Tidak Mendukung Kamera',
                text: 'Gunakan browser Google Chrome atau Safari pada HP Anda.',
                confirmButtonColor: '#064e3b'
            });
            return;
        }

        // Refresh device list if not yet populated
        if (videoDevices.length === 0) {
            await getVideoDevices();
        }

        // Ordered list of constraints to attempt
        const constraintCandidates = [];

        // 1. Try explicit deviceId if multiple devices are available
        if (videoDevices.length > 1 && videoDevices[currentDeviceIndex]) {
            constraintCandidates.push({
                video: {
                    deviceId: { exact: videoDevices[currentDeviceIndex].deviceId },
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
        }

        // 2. Try exact facingMode (Forces mobile browsers to switch physical lens)
        constraintCandidates.push({
            video: {
                facingMode: { exact: currentFacingMode },
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        });

        // 3. Try direct facingMode string
        constraintCandidates.push({
            video: {
                facingMode: currentFacingMode,
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        });

        // 4. Try ideal facingMode
        constraintCandidates.push({
            video: {
                facingMode: { ideal: currentFacingMode },
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        });

        // 5. Ultimate fallback: general video stream
        constraintCandidates.push({
            video: true
        });

        let activeStream = null;
        for (const constraints of constraintCandidates) {
            try {
                activeStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (activeStream) break;
            } catch (err) {
                // Try next constraint candidate
            }
        }

        if (activeStream) {
            mediaStream = activeStream;
            video.srcObject = activeStream;
            try {
                await video.play();
            } catch (playErr) {
                // Ignore autoplay policy error if user hasn't interacted yet
            }
            // Enumerate devices now that permission is definitely granted
            getVideoDevices();
        } else {
            console.error("Gagal mendapatkan akses stream kamera.");
            Swal.fire({
                icon: 'error',
                title: 'Akses Kamera Gagal',
                text: 'Izinkan browser mengakses kamera Anda untuk melakukan presensi.',
                confirmButtonColor: '#064e3b'
            });
        }
    }

    function stopCamera() {
        const video = document.getElementById('webcam');
        if (video) {
            video.srcObject = null;
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => {
                track.stop();
            });
            mediaStream = null;
        }
    }

    function getDistanceInMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    }

    /**
     * Menginisialisasi peta Leaflet dan membaca lokasi GPS akurat dari perangkat pegawai.
     * Menghitung jarak ke kantor menggunakan rumus Haversine serta memeriksa radius batas kehadiran.
     */
    function initGPSAndMap() {
        const officeLat = {{ $setting->latitude_kantor }};
        const officeLng = {{ $setting->longitude_kantor }};
        const officeRadius = {{ $setting->radius_meter }};
        const enforceRadius = {{ $setting->enforce_radius ? 'true' : 'false' }};

        if (!leafletMap) {
            leafletMap = L.map('map').setView([officeLat, officeLng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(leafletMap);

            L.circle([officeLat, officeLng], {
                color: '#047857',
                fillColor: '#10b981',
                fillOpacity: 0.2,
                radius: officeRadius
            }).addTo(leafletMap);

            L.marker([officeLat, officeLng]).addTo(leafletMap).bindPopup('{{ $setting->nama_kantor }}');

            leafletMarker = L.marker([officeLat, officeLng]).addTo(leafletMap)
                .bindPopup('Lokasi Anda')
                .openPopup();
        } else {
            setTimeout(() => { leafletMap.invalidateSize(); }, 300);
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    currentLat = position.coords.latitude;
                    currentLng = position.coords.longitude;

                    document.getElementById('modalLatInput').value = currentLat;
                    document.getElementById('modalLngInput').value = currentLng;

                    const dist = getDistanceInMeters(currentLat, currentLng, officeLat, officeLng);
                    const isInside = dist <= officeRadius;
                    isLocationInside = !enforceRadius || isInside;

                    const coordsStr = currentLat.toFixed(6) + ', ' + currentLng.toFixed(6) +
                        ` (Jarak: ${dist}m - ${isInside ? 'Dalam Radius' : 'Di Luar Radius'})`;

                    const coordsEl = document.getElementById('gps-coords');
                    coordsEl.innerText = coordsStr;
                    coordsEl.className = isInside ? 'text-[11px] font-mono font-bold text-emerald-800' : 'text-[11px] font-mono font-bold text-rose-700';

                    leafletMap.setView([currentLat, currentLng], 16);
                    leafletMarker.setLatLng([currentLat, currentLng]);

                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${currentLat}&lon=${currentLng}`)
                        .then(res => res.json())
                        .then(data => {
                            const address = data.display_name || (currentLat.toFixed(6) + ', ' + currentLng.toFixed(6));
                            document.getElementById('gps-address').innerText = address;
                            document.getElementById('modalAlamatInput').value = address;
                        })
                        .catch(() => {
                            document.getElementById('gps-address').innerText = 'Alamat berdasarkan koordinat GPS';
                            document.getElementById('modalAlamatInput').value = 'Lat: ' + currentLat + ', Lng: ' + currentLng;
                        });
                },
                function(error) {
                    console.warn("Gagal GPS:", error);
                    document.getElementById('gps-coords').innerText = 'Menggunakan koordinat standar kantor';
                    document.getElementById('modalLatInput').value = officeLat;
                    document.getElementById('modalLngInput').value = officeLng;
                    document.getElementById('modalAlamatInput').value = 'Lokasi default (GPS off)';
                    isLocationInside = !enforceRadius;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    }

    function stopLivenessScan() {
        if (livenessInterval) {
            clearInterval(livenessInterval);
            livenessInterval = null;
        }
        prevFrameData = null;
    }

    function analyzeFrameMetrics(video) {
        if (!video.videoWidth || !video.videoHeight) {
            return { isFaceInCircle: false, isGlareDetected: false, isPhonePhotoScreen: false, isHandOrObject: false };
        }

        const sampleCanvas = document.createElement('canvas');
        sampleCanvas.width = 120;
        sampleCanvas.height = 90;
        const ctx = sampleCanvas.getContext('2d');
        ctx.drawImage(video, 0, 0, 120, 90);

        const currentFrame = ctx.getImageData(0, 0, 120, 90).data;

        let totalOvalPixels = 0;
        let ovalLuminanceSum = 0;
        let specularGlarePixels = 0;
        let skinPixels = 0;
        let laplacianNoiseSum = 0;
        let luminanceValues = [];

        for (let y = 15; y < 75; y += 2) {
            for (let x = 30; x < 90; x += 2) {
                const dx = (x - 60) / 28;
                const dy = (y - 45) / 28;

                if (dx * dx + dy * dy <= 1) {
                    const idx = (y * 120 + x) * 4;
                    const r = currentFrame[idx];
                    const g = currentFrame[idx + 1];
                    const b = currentFrame[idx + 2];

                    const lum = 0.299 * r + 0.587 * g + 0.114 * b;
                    ovalLuminanceSum += lum;
                    luminanceValues.push(lum);
                    totalOvalPixels++;

                    if (r > 240 && g > 240 && b > 240) {
                        specularGlarePixels++;
                    }

                    const isSkin = (r > 45 && g > 25 && b > 15 && r > g && r > b && (r - g) >= 8 && (r - b) >= 10);
                    if (isSkin) {
                        skinPixels++;
                    }

                    if (x > 32 && x < 88 && y > 17 && y < 73) {
                        const idxR = (y * 120 + (x + 1)) * 4;
                        const idxL = (y * 120 + (x - 1)) * 4;
                        const idxD = ((y + 1) * 120 + x) * 4;
                        const idxU = ((y - 1) * 120 + x) * 4;

                        const lumR = 0.299 * currentFrame[idxR] + 0.587 * currentFrame[idxR+1] + 0.114 * currentFrame[idxR+2];
                        const lumL = 0.299 * currentFrame[idxL] + 0.587 * currentFrame[idxL+1] + 0.114 * currentFrame[idxL+2];
                        const lumD = 0.299 * currentFrame[idxD] + 0.587 * currentFrame[idxD+1] + 0.114 * currentFrame[idxD+2];
                        const lumU = 0.299 * currentFrame[idxU] + 0.587 * currentFrame[idxU+1] + 0.114 * currentFrame[idxU+2];

                        const lap = Math.abs(4 * lum - lumR - lumL - lumD - lumU);
                        laplacianNoiseSum += lap;
                    }
                }
            }
        }

        const avgLuminance = totalOvalPixels > 0 ? (ovalLuminanceSum / totalOvalPixels) : 0;
        const glareRatio = totalOvalPixels > 0 ? (specularGlarePixels / totalOvalPixels) : 0;
        const skinRatio = totalOvalPixels > 0 ? (skinPixels / totalOvalPixels) : 0;
        const screenMoiréScore = totalOvalPixels > 0 ? (laplacianNoiseSum / totalOvalPixels) : 0;

        let varSum = 0;
        for (let i = 0; i < luminanceValues.length; i++) {
            const diff = luminanceValues[i] - avgLuminance;
            varSum += diff * diff;
        }
        const luminanceVariance = totalOvalPixels > 0 ? (varSum / totalOvalPixels) : 0;

        function sampleRegionLum(cx, cy, radius) {
            let sum = 0, count = 0;
            for (let ry = cy - radius; ry <= cy + radius; ry++) {
                for (let rx = cx - radius; rx <= cx + radius; rx++) {
                    if (rx >= 0 && rx < 120 && ry >= 0 && ry < 90) {
                        const idx = (ry * 120 + rx) * 4;
                        sum += 0.299 * currentFrame[idx] + 0.587 * currentFrame[idx+1] + 0.114 * currentFrame[idx+2];
                        count++;
                    }
                }
            }
            return count > 0 ? (sum / count) : 0;
        }

        const lumLeftEye = sampleRegionLum(46, 36, 3);
        const lumRightEye = sampleRegionLum(74, 36, 3);
        const lumLeftCheek = sampleRegionLum(42, 54, 3);
        const lumRightCheek = sampleRegionLum(78, 54, 3);
        const lumMouth = sampleRegionLum(60, 64, 3);
        const lumChin = sampleRegionLum(60, 74, 3);

        const avgCheekLum = (lumLeftCheek + lumRightCheek) / 2;
        const leftEyeContrast = avgCheekLum - lumLeftEye;
        const rightEyeContrast = avgCheekLum - lumRightEye;
        const mouthContrast = lumChin - lumMouth;

        const isFacialTopologyValid = (leftEyeContrast >= 5.0 || rightEyeContrast >= 5.0) && (mouthContrast >= 2.0);

        let currentMotion = 0;
        if (prevFrameData) {
            let diffTotal = 0;
            for (let i = 0; i < currentFrame.length; i += 32) {
                diffTotal += Math.abs(currentFrame[i] - prevFrameData[i]);
            }
            currentMotion = diffTotal / (currentFrame.length / 32);
        }
        prevFrameData = currentFrame;

        motionHistory.push(currentMotion);
        if (motionHistory.length > 8) motionHistory.shift();

        const avgMotion = motionHistory.reduce((a, b) => a + b, 0) / motionHistory.length;

        const isGlareDetected = glareRatio > 0.05;
        const isPhonePhotoScreen = (screenMoiréScore > 26.0) || (isGlareDetected);
        const isStaticPhotoOnPhone = (avgMotion < 0.28 && motionHistory.length >= 5);
        const isHandOrObject = (!isFacialTopologyValid) || (skinRatio > 0.88) || (skinRatio < 0.25);

        const isFaceInCircle = (skinRatio >= 0.25 && skinRatio <= 0.88) && (luminanceVariance >= 90) && (avgLuminance > 30 && avgLuminance < 240) && isFacialTopologyValid && !isPhonePhotoScreen && !isStaticPhotoOnPhone;

        return {
            isFaceInCircle: isFaceInCircle,
            isGlareDetected: isGlareDetected,
            isPhonePhotoScreen: isPhonePhotoScreen || isStaticPhotoOnPhone,
            isStaticPhoto: isStaticPhotoOnPhone,
            isHandOrObject: isHandOrObject,
            screenMoiréScore: screenMoiréScore,
            avgMotion: avgMotion
        };
    }

    function startLivenessScan() {
        stopLivenessScan();
        validFaceTicks = 0;
        motionHistory = [];

        const video = document.getElementById('webcam');
        const faceOval = document.getElementById('faceOval');
        const statusText = document.getElementById('livenessStatusText');
        const stepBadge = document.getElementById('livenessStepBadge');
        const progressBar = document.getElementById('livenessProgressBar');

        livenessInterval = setInterval(() => {
            if (isSubmitting) return;

            if (!video.videoWidth || video.paused || video.ended) {
                statusText.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-amber-400 me-1"></i> Menyiapkan Kamera...';
                return;
            }

            if (!isLocationInside) {
                validFaceTicks = 0;
                faceOval.setAttribute('stroke', '#ef4444');
                faceOval.setAttribute('stroke-dasharray', '4 2');
                stepBadge.className = 'bg-rose-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow animate-pulse';
                stepBadge.innerText = 'Di Luar Radius';
                statusText.innerHTML = '<i class="fa-solid fa-location-dot text-rose-400 me-1"></i> Lokasi GPS Di Luar Radius Kantor!';
                progressBar.style.width = '10%';
                progressBar.className = 'bg-rose-500 h-1 rounded-full transition-all duration-300';
                return;
            }

            const metrics = analyzeFrameMetrics(video);

            if (metrics.isPhonePhotoScreen) {
                validFaceTicks = 0;
                faceOval.setAttribute('stroke', '#ef4444');
                faceOval.setAttribute('stroke-dasharray', '4 2');
                stepBadge.className = 'bg-rose-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow';
                stepBadge.innerText = 'Layar / Pantulan';
                statusText.innerHTML = '<i class="fa-solid fa-mobile-screen-button text-rose-400 me-1"></i> Layar HP Terdeteksi! Hadapkan Wajah Asli.';
                progressBar.style.width = '25%';
                progressBar.className = 'bg-rose-500 h-1 rounded-full transition-all duration-300';
                return;
            }

            if (metrics.isHandOrObject) {
                validFaceTicks = 0;
                faceOval.setAttribute('stroke', '#f59e0b');
                faceOval.setAttribute('stroke-dasharray', '4 2');
                stepBadge.className = 'bg-amber-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow';
                stepBadge.innerText = 'Sesuaikan Wajah';
                statusText.innerHTML = '<i class="fa-solid fa-user-focus text-amber-400 me-1"></i> Posisikan Wajah Tepat di Lingkaran';
                progressBar.style.width = '35%';
                progressBar.className = 'bg-amber-500 h-1 rounded-full transition-all duration-300';
                return;
            }

            if (!metrics.isFaceInCircle) {
                validFaceTicks = 0;
                faceOval.setAttribute('stroke', '#f59e0b');
                faceOval.setAttribute('stroke-dasharray', '4 2');
                stepBadge.className = 'bg-amber-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow';
                stepBadge.innerText = 'Posisikan Wajah';
                statusText.innerHTML = '<i class="fa-solid fa-user-focus text-amber-400 me-1 animate-pulse"></i> Posisikan Wajah di Dalam Lingkaran';
                progressBar.style.width = '50%';
                progressBar.className = 'bg-amber-500 h-1 rounded-full transition-all duration-300';
                return;
            }

            validFaceTicks++;
            const progressPercent = Math.min(100, 50 + (validFaceTicks * 17));
            progressBar.style.width = `${progressPercent}%`;

            if (validFaceTicks < 3) {
                faceOval.setAttribute('stroke', '#3b82f6');
                stepBadge.className = 'bg-blue-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow';
                stepBadge.innerText = 'Menahan Wajah...';
                statusText.innerHTML = '<i class="fa-solid fa-expand text-blue-400 me-1 animate-ping"></i> Tahan Posisi Wajah...';
            } else {
                faceOval.setAttribute('stroke', '#10b981');
                faceOval.setAttribute('stroke-dasharray', '0');
                stepBadge.className = 'bg-emerald-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow';
                stepBadge.innerText = 'Terverifikasi';

                statusText.innerHTML = '<i class="fa-solid fa-shield-halved text-emerald-400 me-1"></i> Wajah Terverifikasi! Mengirim Presensi...';
                progressBar.style.width = '100%';
                progressBar.className = 'bg-emerald-500 h-1 rounded-full transition-all duration-200';

                stopLivenessScan();
                submitAttendance();
            }
        }, 150);
    }

    /**
     * Mengambil cuplikan foto dari canvas kamera, menerapkan pencerminan jika mode cermin aktif,
     * lalu mengirimkan data presensi lengkap (foto wajah + GPS) ke server via AJAX Fetch API.
     */
    function submitAttendance() {
        if (isSubmitting) return;

        if (!isLocationInside) {
            Swal.fire({
                icon: 'error',
                title: 'Presensi Ditolak!',
                text: 'Lokasi GPS Anda berada di luar radius kantor yang diizinkan.',
                confirmButtonColor: '#ef4444'
            });
            return;
        }

        isSubmitting = true;

        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');

        if (!video.videoWidth || !video.videoHeight) {
            isSubmitting = false;
            Swal.fire({ icon: 'warning', title: 'Kamera Belum Siap', text: 'Tunggu beberapa detik hingga gambar kamera muncul.', confirmButtonColor: '#064e3b' });
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        if (isMirrored) {
            context.save();
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            context.restore();
        } else {
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
        }
        
        const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
        document.getElementById('modalFotoInput').value = dataUrl;

        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses Presensi...';
        }

        canvas.toBlob(function(blob) {
            const formData = new FormData(document.getElementById('attendanceForm'));
            if (blob) {
                formData.append('foto_file', blob, 'foto.jpg');
            }
            formData.set('foto', dataUrl);

            fetch("{{ route('karyawan.attendance.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const errorMsg = data.message || ("Gagal terhubung ke server (HTTP Status: " + res.status + ")");
                    throw new Error(errorMsg);
                }
                return data;
            })
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-camera text-base text-amber-300"></i> AMBIL FOTO &amp; KIRIM PRESENSI';
                }

                if (data.success) {
                    closeCameraModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Presensi Berhasil!',
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    isSubmitting = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Presensi',
                        text: data.message || 'Terjadi kesalahan.',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(err => {
                isSubmitting = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-camera me-1"></i> Coba Lagi';
                }
                console.error("Gagal mengirim presensi:", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Presensi Gagal',
                    text: err.message || 'Gagal terhubung ke server. Periksa koneksi internet.',
                    confirmButtonColor: '#ef4444'
                });
            });
        }, 'image/jpeg', 0.88);
    }

    function viewDetail(photoUrl, lat, lng, alamat, label, waktu) {
        document.getElementById('viewTitle').innerText = 'Detail Presensi: ' + label;
        document.getElementById('viewPhoto').src = photoUrl;
        document.getElementById('viewTimeInfo').innerText = 'Waktu Record: ' + waktu;
        document.getElementById('viewAddressInfo').innerText = 'Lokasi: ' + alamat + ' (' + lat + ', ' + lng + ')';
        document.getElementById('viewModal').classList.remove('hidden');

        setTimeout(() => {
            if (!viewDetailLeafletMap) {
                viewDetailLeafletMap = L.map('viewDetailMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(viewDetailLeafletMap);
                viewDetailLeafletMarker = L.marker([lat, lng]).addTo(viewDetailLeafletMap);
            } else {
                viewDetailLeafletMap.invalidateSize();
                viewDetailLeafletMap.setView([lat, lng], 16);
                viewDetailLeafletMarker.setLatLng([lat, lng]);
            }
        }, 300);
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
</script>
@endpush

@extends('layouts.app')

@section('title', 'Pengaturan Instansi, Ketua & Lokasi - Pengadilan Tinggi Pontianak')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-emerald-700"></i> Pengaturan Instansi, Ketua &amp; Lokasi Presensi
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola nama Ketua Pengadilan / Pejabat penandatangan laporan, informasi Satker, serta koordinat lokasi kantor dan radius presensi GPS.
            </p>
        </div>

        <div class="flex items-center gap-2 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 text-xs font-bold text-emerald-950">
            <i class="fa-solid fa-compass text-emerald-700"></i>
            <span>Koordinat Aktif: <span id="current-coords-badge" class="font-mono text-emerald-800 font-bold">{{ $setting->latitude_kantor }}, {{ $setting->longitude_kantor }}</span></span>
        </div>
    </div>

    <form action="{{ route('operator.location.update') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left / Form Columns (2 cols or 1 col) -->
            <div class="space-y-6 lg:col-span-1">

                <!-- 1. Pejabat & Ketua Pengadilan Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-user-tie text-emerald-700"></i> Pejabat Penandatangan Laporan
                    </h2>

                    <!-- Nama Ketua -->
                    <div>
                        <label for="nama_ketua" class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Ketua / Pimpinan *
                        </label>
                        <input type="text" name="nama_ketua" id="nama_ketua" value="{{ old('nama_ketua', $setting->nama_ketua ?? 'Isnurul Syamsyul Arif') }}" required
                            placeholder="Contoh: Isnurul Syamsyul Arif"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                        <p class="text-[11px] text-slate-500 mt-1">Nama ini akan tercetak pada bagian tanda tangan dokumen Excel.</p>
                    </div>

                    <!-- Jabatan Ketua -->
                    <div>
                        <label for="jabatan_ketua" class="block text-xs font-bold text-slate-700 mb-1">
                            Jabatan Penandatangan *
                        </label>
                        <input type="text" name="jabatan_ketua" id="jabatan_ketua" value="{{ old('jabatan_ketua', $setting->jabatan_ketua ?? 'Ketua Pengadilan Tinggi Pontianak') }}" required
                            placeholder="Contoh: Ketua Pengadilan Tinggi Pontianak"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <!-- NIP Ketua -->
                    <div>
                        <label for="nip_ketua" class="block text-xs font-bold text-slate-700 mb-1">
                            NIP Ketua / Pejabat <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="text" name="nip_ketua" id="nip_ketua" value="{{ old('nip_ketua', $setting->nip_ketua) }}"
                            placeholder="Contoh: 196501011990031001"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-medium text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <!-- Satuan Kerja & Kota -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                        <div>
                            <label for="satker_name" class="block text-xs font-bold text-slate-700 mb-1">
                                Nama Satker *
                            </label>
                            <input type="text" name="satker_name" id="satker_name" value="{{ old('satker_name', $setting->satker_name ?? 'PENGADILAN TINGGI PONTIANAK') }}" required
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>

                        <div>
                            <label for="kota_surat" class="block text-xs font-bold text-slate-700 mb-1">
                                Kota Dokumen *
                            </label>
                            <input type="text" name="kota_surat" id="kota_surat" value="{{ old('kota_surat', $setting->kota_surat ?? 'Pontianak') }}" required
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                </div>

                <!-- 2. Parameter Lokasi Kantor Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-map-pin text-emerald-700"></i> Parameter Lokasi &amp; Radius GPS
                    </h2>

                    <!-- Nama Kantor -->
                    <div>
                        <label for="nama_kantor" class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Kantor / Titik Presensi *
                        </label>
                        <input type="text" name="nama_kantor" id="nama_kantor" value="{{ old('nama_kantor', $setting->nama_kantor) }}" required
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <!-- Latitude Kantor -->
                    <div>
                        <label for="latitude_kantor" class="block text-xs font-bold text-slate-700 mb-1">
                            Latitude Kantor *
                        </label>
                        <input type="text" name="latitude_kantor" id="latitude_kantor" value="{{ old('latitude_kantor', $setting->latitude_kantor) }}" required
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500"
                            oninput="updateMapFromInputs()">
                    </div>

                    <!-- Longitude Kantor -->
                    <div>
                        <label for="longitude_kantor" class="block text-xs font-bold text-slate-700 mb-1">
                            Longitude Kantor *
                        </label>
                        <input type="text" name="longitude_kantor" id="longitude_kantor" value="{{ old('longitude_kantor', $setting->longitude_kantor) }}" required
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500"
                            oninput="updateMapFromInputs()">
                    </div>

                    <!-- Radius Meter -->
                    <div>
                        <label for="radius_meter" class="block text-xs font-bold text-slate-700 mb-1">
                            Batas Radius Presensi (Meter) *
                        </label>
                        <div class="relative rounded-xl">
                            <input type="number" name="radius_meter" id="radius_meter" value="{{ old('radius_meter', $setting->radius_meter) }}" required min="10" max="10000"
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-emerald-800 focus:ring-emerald-500 focus:border-emerald-500"
                                oninput="updateMapFromInputs()">
                            <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs text-emerald-700 font-black">
                                METER
                            </span>
                        </div>
                    </div>

                    <!-- Enable/Disable Enforce Radius -->
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">Validasi Radius GPS</span>
                            <span class="text-[11px] text-slate-500">Tolak presensi jika di luar radius.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enforce_radius" value="1" {{ old('enforce_radius', $setting->enforce_radius) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#064e3b]"></div>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3.5 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-extrabold rounded-xl shadow-lg shadow-emerald-900/30 transition text-xs flex items-center justify-center gap-2 border border-emerald-700">
                        <i class="fa-solid fa-floppy-disk text-amber-300 text-base"></i> SIMPAN SEMUA PENGATURAN
                    </button>
                </div>

            </div>

            <!-- Right Column: Interactive Leaflet Map Column -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-map text-emerald-700"></i> Peta Titik Pusat &amp; Lingkaran Radius Kantor
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Geser pin marker atau klik pada peta untuk menentukan pusat kantor</p>
                    </div>
                    <button type="button" onclick="getCurrentDeviceLocation()" class="px-3 py-1.5 bg-emerald-50 border border-emerald-300 text-emerald-800 font-bold rounded-xl hover:bg-emerald-100 transition text-[11px] shadow-sm flex items-center gap-1">
                        <i class="fa-solid fa-crosshairs text-emerald-700"></i> GPS Perangkat
                    </button>
                </div>

                <!-- Leaflet Map Container -->
                <div id="officeMap" class="w-full h-[580px] rounded-2xl shadow-inner border border-slate-200"></div>

                <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-xs text-emerald-950 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-emerald-700 text-base"></i>
                    <span>Lingkaran hijau transparan pada peta menunjukkan batas jangkauan radius presensi pegawai di kantor.</span>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
    let officeMap = null;
    let officeMarker = null;
    let radiusCircle = null;

    const initialLat = {{ $setting->latitude_kantor }};
    const initialLng = {{ $setting->longitude_kantor }};
    const initialRadius = {{ $setting->radius_meter }};

    document.addEventListener('DOMContentLoaded', function() {
        officeMap = L.map('officeMap').setView([initialLat, initialLng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(officeMap);

        // Marker pin draggable
        officeMarker = L.marker([initialLat, initialLng], { draggable: true }).addTo(officeMap)
            .bindPopup('Pusat Lokasi Kantor')
            .openPopup();

        // Radius circle (PPTK Emerald & Amber)
        radiusCircle = L.circle([initialLat, initialLng], {
            color: '#047857',
            fillColor: '#10b981',
            fillOpacity: 0.25,
            radius: initialRadius
        }).addTo(officeMap);

        // Drag marker event listener
        officeMarker.on('dragend', function(e) {
            const position = officeMarker.getLatLng();
            updateInputsAndCircle(position.lat, position.lng);
        });

        // Map click event listener
        officeMap.on('click', function(e) {
            officeMarker.setLatLng(e.latlng);
            updateInputsAndCircle(e.latlng.lat, e.latlng.lng);
        });
    });

    function updateInputsAndCircle(lat, lng) {
        document.getElementById('latitude_kantor').value = lat.toFixed(7);
        document.getElementById('longitude_kantor').value = lng.toFixed(7);
        document.getElementById('current-coords-badge').innerText = lat.toFixed(6) + ', ' + lng.toFixed(6);

        const r = parseFloat(document.getElementById('radius_meter').value) || 200;

        if (radiusCircle) {
            radiusCircle.setLatLng([lat, lng]);
            radiusCircle.setRadius(r);
        }
    }

    function updateMapFromInputs() {
        const lat = parseFloat(document.getElementById('latitude_kantor').value);
        const lng = parseFloat(document.getElementById('longitude_kantor').value);
        const r = parseFloat(document.getElementById('radius_meter').value) || 200;

        if (!isNaN(lat) && !isNaN(lng)) {
            officeMarker.setLatLng([lat, lng]);
            radiusCircle.setLatLng([lat, lng]);
            radiusCircle.setRadius(r);
            officeMap.setView([lat, lng]);
            document.getElementById('current-coords-badge').innerText = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }
    }

    function getCurrentDeviceLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                officeMarker.setLatLng([lat, lng]);
                officeMap.setView([lat, lng], 17);
                updateInputsAndCircle(lat, lng);
            });
        }
    }
</script>
@endpush
@endsection

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if(isset($singleEmployee) && $singleEmployee)
            Laporan Presensi Harian - {{ $singleEmployee->name }}
        @else
            Laporan Rekapitulasi Presensi Pegawai - {{ $setting->satker_name ?? 'Pengadilan Tinggi Pontianak' }}
        @endif
    </title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        @page {
            @if(isset($singleEmployee) && $singleEmployee)
                size: A4 portrait;
                margin: 10mm 12mm 10mm 12mm;
            @else
                size: A4 landscape;
                margin: 10mm 12mm 10mm 12mm;
            @endif
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Instrument Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.3;
        }

        .paper-container {
            @if(isset($singleEmployee) && $singleEmployee)
                max-width: 210mm;
            @else
                max-width: 297mm;
            @endif
            min-height: 210mm;
            margin: 20px auto;
            padding: 12mm 15mm;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Toolbar Top (Hidden in Print) */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #064e3b;
            color: #ffffff;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid #fbbf24;
        }

        .toolbar-title {
            font-size: 13px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-toolbar {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-print {
            background-color: #fbbf24;
            color: #022c22;
            border-color: #f59e0b;
        }

        .btn-print:hover {
            background-color: #f59e0b;
        }

        .btn-excel {
            background-color: #047857;
            color: #ffffff;
            border-color: #059669;
        }

        .btn-excel:hover {
            background-color: #065f46;
        }

        .btn-back {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-back:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }

        /* Kop Surat Resmi */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 12px;
            border-bottom: 3px double #0f172a;
        }

        .kop-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .kop-text {
            text-align: center;
            width: 100%;
            padding: 0 75px;
        }

        .kop-text h2 {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #022c22;
        }

        .kop-text h1 {
            margin: 2px 0 3px 0;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #064e3b;
        }

        .kop-text p {
            margin: 0;
            font-size: 9px;
            color: #334155;
            line-height: 1.35;
        }

        /* Document Title */
        .doc-title-box {
            text-align: center;
            margin-bottom: 10px;
        }

        .doc-title {
            margin: 0;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #0f172a;
            text-decoration: underline;
        }

        .doc-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            margin-bottom: 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        /* Employee Info Box (Single Employee Mode) */
        .emp-info-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 10px;
            font-size: 10px;
        }

        .emp-info-col table {
            width: 100%;
            border-collapse: collapse;
        }

        .emp-info-col table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .emp-info-col table td.label {
            width: 110px;
            font-weight: 700;
            color: #475569;
        }

        .emp-info-col table td.val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Summary Stats Row for Single Employee */
        .emp-stats-bar {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
            margin-bottom: 10px;
            font-size: 9px;
            text-align: center;
        }

        .emp-stat-item {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            border-radius: 6px;
            padding: 4px 6px;
        }

        .emp-stat-item .num {
            font-size: 13px;
            font-weight: 800;
            color: #064e3b;
            font-family: 'JetBrains Mono', monospace;
        }

        .emp-stat-item .lbl {
            font-size: 8.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }

        /* Tables (Matrix & Daily) */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-bottom: 14px;
        }

        .table-custom th, 
        .table-custom td {
            border: 1px solid #334155;
            padding: 3.5px 5px;
            vertical-align: middle;
        }

        .table-custom thead tr th {
            background-color: #92d050 !important;
            color: #000000;
            font-weight: 800;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
        }

        .table-custom tbody tr td {
            color: #0f172a;
        }

        .table-custom tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .row-libur {
            background-color: #f1f5f9 !important;
            color: #64748b !important;
        }

        .row-cuti {
            background-color: #f0fdf4 !important;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .font-bold { font-weight: 700; }
        .text-slate-500 { color: #64748b; }
        .text-rose-700 { color: #b91c1c; }
        .text-emerald-800 { color: #065f46; }
        .text-amber-800 { color: #92400e; }

        /* Footer / Signature & Legend Section */
        .footer-grid {
            display: grid;
            @if(isset($singleEmployee) && $singleEmployee)
                grid-template-columns: 1fr 1fr;
            @else
                grid-template-columns: 1.4fr 1fr;
            @endif
            gap: 20px;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .legend-box {
            font-size: 8.5px;
            color: #334155;
            line-height: 1.4;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
        }

        .legend-box h4 {
            margin: 0 0 3px 0;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
        }

        .legend-box ul {
            margin: 0;
            padding-left: 12px;
        }

        .signature-box {
            text-align: center;
            font-size: 10px;
        }

        .signature-date {
            margin-bottom: 3px;
            font-weight: 600;
        }

        .signature-title {
            font-weight: 800;
            margin-bottom: 50px;
        }

        .signature-name {
            font-size: 10.5px;
            font-weight: 800;
            text-decoration: underline;
            color: #0f172a;
        }

        .signature-nip {
            font-size: 9px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: #334155;
            margin-top: 1px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background-color: #ffffff;
            }

            .toolbar {
                display: none !important;
            }

            .paper-container {
                max-width: 100%;
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }

            .table-custom thead tr th {
                background-color: #92d050 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .footer-grid {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    @php
        $startMonth = \Carbon\Carbon::parse($tanggal_mulai)->locale('id')->isoFormat('MMMM Y');
        $endMonth = \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('MMMM Y');
        $bulanText = ($startMonth === $endMonth) ? strtoupper($startMonth) : strtoupper($startMonth . ' - ' . $endMonth);
    @endphp

    <!-- Floating Top Toolbar (Hidden when printing) -->
    <div class="toolbar">
        <div class="toolbar-title">
            <i class="fa-solid fa-print text-amber-300"></i>
            <span>
                @if(isset($singleEmployee) && $singleEmployee)
                    Laporan Rincian Presensi Harian: {{ $singleEmployee->name }}
                @else
                    Laporan Rekapitulasi Presensi Pegawai
                @endif
            </span>
        </div>
        <div class="toolbar-actions">
            <button onclick="window.print()" class="btn-toolbar btn-print">
                <i class="fa-solid fa-print"></i> Cetak Dokumen (Print)
            </button>
            <a href="{{ route('operator.reports.export', ['tanggal_mulai' => $tanggal_mulai, 'tanggal_selesai' => $tanggal_selesai, 'user_id' => $user_id]) }}" class="btn-toolbar btn-excel">
                <i class="fa-solid fa-file-excel"></i> Unduh Excel (.xlsx)
            </a>
            <a href="{{ route('operator.reports.index', ['tanggal_mulai' => $tanggal_mulai, 'tanggal_selesai' => $tanggal_selesai, 'user_id' => $user_id]) }}" class="btn-toolbar btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Official Paper Container -->
    <div class="paper-container">

        <!-- Kop Surat Resmi -->
        <div class="kop-surat">
            <img src="{{ asset('LOGO-PPTK.png') }}" alt="Logo" class="kop-logo" onerror="this.src='{{ asset('images/logo.png') }}'">
            <div class="kop-text">
                <h2>MAHKAMAH AGUNG REPUBLIK INDONESIA</h2>
                <h1>{{ strtoupper($setting->satker_name ?? 'PENGADILAN TINGGI PONTIANAK') }}</h1>
                <p>
                    Jl. Jenderal Ahmad Yani No. 64, Pontianak, Kalimantan Barat 78124<br>
                    Telepon: (0561) 732442 &bull; Pos-el: info@pt-pontianak.go.id &bull; Laman: https://pt-pontianak.go.id
                </p>
            </div>
        </div>

        @if(isset($singleEmployee) && $singleEmployee)
            <!-- ========================================== -->
            <!-- MODE 1: DETAIL PER HARI UNTUK 1 ORANG PEGAWAI -->
            <!-- ========================================== -->

            <div class="doc-title-box">
                <h2 class="doc-title">LAPORAN RINCIAN PRESENSI HARIAN PEGAWAI</h2>
            </div>

            <!-- Employee Info Box -->
            <div class="emp-info-card">
                <div class="emp-info-col">
                    <table>
                        <tr>
                            <td class="label">Nama Lengkap</td>
                            <td class="val">: {{ $singleEmployee->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ $singleEmployee->tipe_identitas_label }}</td>
                            <td class="val font-mono">: {{ $singleEmployee->nip ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Status / Jabatan</td>
                            <td class="val">: {{ $singleEmployee->jabatan ?? $singleEmployee->jenis_pegawai_label }}</td>
                        </tr>
                        @if($singleEmployee->asal_instansi)
                            <tr>
                                <td class="label">Asal Instansi / Kampus</td>
                                <td class="val">: {{ $singleEmployee->asal_instansi }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
                <div class="emp-info-col">
                    <table>
                        <tr>
                            <td class="label">Satuan Kerja</td>
                            <td class="val">: {{ $setting->satker_name ?? 'PENGADILAN TINGGI PONTIANAK' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Periode Laporan</td>
                            <td class="val">: {{ \Carbon\Carbon::parse($tanggal_mulai)->locale('id')->isoFormat('D MMMM Y') }} s/d {{ \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('D MMMM Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Bulan</td>
                            <td class="val">: {{ $bulanText }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @php
                $stat = $employeeStats[0] ?? null;
            @endphp

            @if($stat)
                <!-- Attendance Summary Stats Bar -->
                <div class="emp-stats-bar">
                    <div class="emp-stat-item">
                        <div class="num">{{ $stat['total_hari_kerja'] ?? 0 }}</div>
                        <div class="lbl">Hari Kerja</div>
                    </div>
                    <div class="emp-stat-item">
                        <div class="num text-emerald-800">{{ $stat['tepat_waktu'] ?? 0 }}</div>
                        <div class="lbl">Tepat Waktu</div>
                    </div>
                    <div class="emp-stat-item">
                        <div class="num text-amber-800">{{ $stat['terlambat'] ?? 0 }}</div>
                        <div class="lbl">Terlambat</div>
                    </div>
                    <div class="emp-stat-item">
                        <div class="num">{{ $stat['lebih_awal'] ?? 0 }}</div>
                        <div class="lbl">Lebih Awal</div>
                    </div>
                    <div class="emp-stat-item">
                        <div class="num text-emerald-800">{{ $stat['cuti_total'] ?? (($stat['cuti_tahunan'] ?? 0) + ($stat['cuti_sakit'] ?? 0) + ($stat['cuti_luar_negeri'] ?? 0) + ($stat['cuti_lainnya'] ?? 0)) }}</div>
                        <div class="lbl">Total Cuti</div>
                    </div>
                    <div class="emp-stat-item">
                        <div class="num text-rose-700">{{ $stat['tanpa_keterangan'] ?? 0 }}</div>
                        <div class="lbl">Tanpa Ket. (ALFA)</div>
                    </div>
                </div>
            @endif

            <!-- Daily Attendance Details Table -->
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 25px;">No.</th>
                        <th style="width: 120px;">Hari &amp; Tanggal</th>
                        <th style="width: 75px;">Masuk</th>
                        <th style="width: 75px;">Istirahat</th>
                        <th style="width: 75px;">Masuk Ist.</th>
                        <th style="width: 75px;">Pulang</th>
                        <th>Status &amp; Keterangan Harian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyRecords as $index => $rec)
                        @php
                            $rowClass = '';
                            if ($rec['is_libur']) {
                                $rowClass = 'row-libur';
                            } elseif ($rec['leave']) {
                                $rowClass = 'row-cuti';
                            }
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="text-center font-mono font-bold">{{ $index + 1 }}</td>
                            <td class="text-left font-mono">
                                <strong>{{ $rec['hari'] }}</strong>, {{ \Carbon\Carbon::parse($rec['date_str'])->format('d/m/Y') }}
                            </td>

                            <!-- Masuk -->
                            <td class="text-center font-mono">
                                @if($rec['masuk'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['masuk']->waktu)->format('H:i') }}</strong></div>
                                    <div class="text-[8.5px] {{ $rec['masuk']->status === 'terlambat' ? 'text-amber-800 font-bold' : 'text-slate-500' }}">
                                        {{ $rec['masuk']->status === 'terlambat' ? 'Terlambat' : 'Tepat Waktu' }}
                                    </div>
                                @elseif($rec['leave'])
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[8.5px] border border-emerald-300">Cuti / Izin</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Istirahat -->
                            <td class="text-center font-mono">
                                @if($rec['istirahat'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['istirahat']->waktu)->format('H:i') }}</strong></div>
                                    <div class="text-[8.5px] text-slate-500">
                                        {{ $rec['istirahat']->status === 'lebih_awal' ? 'Lebih Awal' : 'Tepat' }}
                                    </div>
                                @elseif($rec['leave'])
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[8.5px] border border-emerald-300">Cuti / Izin</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Masuk Istirahat -->
                            <td class="text-center font-mono">
                                @if($rec['masuk_istirahat'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['masuk_istirahat']->waktu)->format('H:i') }}</strong></div>
                                    <div class="text-[8.5px] {{ $rec['masuk_istirahat']->status === 'terlambat' ? 'text-amber-800 font-bold' : 'text-slate-500' }}">
                                        {{ $rec['masuk_istirahat']->status === 'terlambat' ? 'Terlambat' : 'Tepat' }}
                                    </div>
                                @elseif($rec['leave'])
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[8.5px] border border-emerald-300">Cuti / Izin</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Pulang -->
                            <td class="text-center font-mono">
                                @if($rec['pulang'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['pulang']->waktu)->format('H:i') }}</strong></div>
                                    <div class="text-[8.5px] {{ $rec['pulang']->status === 'lebih_awal' ? 'text-amber-800 font-bold' : 'text-slate-500' }}">
                                        {{ $rec['pulang']->status === 'lebih_awal' ? 'Lebih Awal' : 'Tepat' }}
                                    </div>
                                @elseif($rec['leave'])
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[8.5px] border border-emerald-300">Cuti / Izin</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Status Harian -->
                            <td class="text-left">
                                @if($rec['is_libur'])
                                    <span class="text-slate-500 font-semibold">{{ $rec['status_harian'] }}</span>
                                @elseif($rec['leave'])
                                    <span class="text-emerald-800 font-bold">
                                        {{ $rec['status_harian'] }}
                                        @if($rec['leave']->alasan) ({{ $rec['leave']->alasan }}) @endif
                                    </span>
                                @elseif($rec['status_badge_class'] === 'alfa')
                                    <span class="text-rose-700 font-bold">Tanpa Keterangan (ALFA)</span>
                                @elseif($rec['status_badge_class'] === 'ditolak')
                                    <span class="text-rose-700 font-bold">{{ $rec['status_harian'] }}</span>
                                @elseif($rec['status_badge_class'] === 'terlambat')
                                    <span class="text-amber-800 font-bold">{{ $rec['status_harian'] }}</span>
                                @elseif($rec['status_badge_class'] === 'hadir')
                                    <span class="text-emerald-800 font-semibold">{{ $rec['status_harian'] }}</span>
                                @else
                                    <span class="text-slate-400">{{ $rec['status_harian'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 15px;">
                                Tidak ada rekaman presensi pada rentang tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Dual Signature Section (Ketua di Kiri dengan Mengetahui, Mahasiswa di Kanan) -->
            <div class="footer-grid">
                <!-- Kiri: Pejabat yang Mengetahui (Ketua) -->
                <div class="signature-box">
                    <div class="signature-date">&nbsp;</div>
                    <div class="signature-title">
                        Mengetahui,<br>
                        {{ $setting->jabatan_ketua ?? 'Ketua Pengadilan Tinggi Pontianak' }},
                    </div>
                    <div class="signature-name">
                        {{ $setting->nama_ketua ?? 'Isnurul Syamsyul Arif' }}
                    </div>
                    @if(!empty($setting->nip_ketua))
                        <div class="signature-nip">
                            NIP. {{ $setting->nip_ketua }}
                        </div>
                    @endif
                </div>

                <!-- Kanan: Mahasiswa Magang / Pegawai yang Bersangkutan -->
                <div class="signature-box">
                    <div class="signature-date">
                        {{ $setting->kota_surat ?? 'Pontianak' }}, {{ \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('D MMMM Y') }}
                    </div>
                    <div class="signature-title">
                        <br>
                        {{ $singleEmployee->isMagang() ? 'Mahasiswa / Siswa Magang,' : 'Pegawai yang Bersangkutan,' }}
                    </div>
                    <div class="signature-name">{{ $singleEmployee->name }}</div>
                    @if(!empty($singleEmployee->nip))
                        <div class="signature-nip">{{ $singleEmployee->tipe_identitas_label }}. {{ $singleEmployee->nip }}</div>
                    @endif
                </div>
            </div>

        @else
            <!-- ========================================== -->
            <!-- MODE 2: REKAPITULASI SEMUA PEGAWAI (BOOK1) -->
            <!-- ========================================== -->

            <div class="doc-title-box">
                <h2 class="doc-title">LAPORAN REKAPITULASI PRESENSI PEGAWAI &amp; MAGANG</h2>
            </div>

            <!-- Metadata Bar (Satker & Bulan) -->
            <div class="doc-meta">
                <span>SATKER : {{ strtoupper($setting->satker_name ?? 'PENGADILAN TINGGI PONTIANAK') }}</span>
                <span>BULAN : {{ $bulanText }}</span>
            </div>

            <!-- Matrix Table Matching Book1 Format -->
            <table class="table-custom">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 25px;">NO.</th>
                        <th rowspan="2" style="width: 170px;">NAMA / IDENTITAS</th>
                        <th rowspan="2" style="width: 120px;">JABATAN</th>
                        <th rowspan="2" style="width: 90px;">SATUAN KERJA</th>
                        <th colspan="4">REKAPITULASI SESI PRESENSI</th>
                        <th colspan="5">STATUS &amp; EVALUASI PRESENSI</th>
                        <th rowspan="2" style="width: 140px;">KETERANGAN</th>
                    </tr>
                    <tr>
                        <th style="width: 38px;">Masuk</th>
                        <th style="width: 38px;">Istirahat</th>
                        <th style="width: 38px;">Masuk Ist.</th>
                        <th style="width: 38px;">Pulang</th>

                        <th style="width: 44px;">Tepat Waktu</th>
                        <th style="width: 44px;">Terlambat</th>
                        <th style="width: 44px;">Lebih Awal</th>
                        <th style="width: 40px;">Ditolak</th>
                        <th style="width: 40px;">Tanpa Ket.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employeeStats as $index => $stat)
                        <tr>
                            <td class="text-center font-mono font-bold">{{ $index + 1 }}</td>
                            <td class="text-left">
                                <div class="font-bold">{{ $stat['user']->name }}</div>
                                @if(!empty($stat['user']->nip))
                                    <div class="font-mono text-[9px] text-slate-600">{{ $stat['user']->tipe_identitas_label }}. {{ $stat['user']->nip }}</div>
                                @endif
                                @if($stat['user']->asal_instansi)
                                    <div class="text-[8.5px] text-slate-500 font-sans">{{ $stat['user']->asal_instansi }}</div>
                                @endif
                            </td>
                            <td class="text-left">{{ $stat['user']->jabatan ?? 'Pegawai' }}</td>
                            <td class="text-center">PT. Pontianak</td>

                            <!-- Sesi Presensi -->
                            <td class="text-center font-mono font-bold">{{ $stat['masuk'] > 0 ? $stat['masuk'] : '' }}</td>
                            <td class="text-center font-mono font-bold">{{ $stat['istirahat'] > 0 ? $stat['istirahat'] : '' }}</td>
                            <td class="text-center font-mono font-bold">{{ $stat['masuk_istirahat'] > 0 ? $stat['masuk_istirahat'] : '' }}</td>
                            <td class="text-center font-mono font-bold">{{ $stat['pulang'] > 0 ? $stat['pulang'] : '' }}</td>

                            <!-- Status & Evaluasi -->
                            <td class="text-center font-mono font-bold">{{ $stat['tepat_waktu'] > 0 ? $stat['tepat_waktu'] : '' }}</td>
                            <td class="text-center font-mono font-bold">{{ $stat['terlambat'] > 0 ? $stat['terlambat'] : '' }}</td>
                            <td class="text-center font-mono font-bold">{{ $stat['lebih_awal'] > 0 ? $stat['lebih_awal'] : '' }}</td>
                            <td class="text-center font-mono font-bold">{{ $stat['ditolak'] > 0 ? $stat['ditolak'] : '' }}</td>
                            <td class="text-center font-mono font-bold">{{ $stat['tanpa_keterangan'] > 0 ? $stat['tanpa_keterangan'] : '' }}</td>

                            <!-- Keterangan Cuti -->
                            <td class="text-left text-[9px]">{{ $stat['keterangan'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center" style="padding: 20px;">
                                Tidak ada data presensi pegawai pada rentang tanggal yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Footer Section: Legend & Official Signature Block -->
            <div class="footer-grid">
                <!-- Left: Keterangan Simbol / Status -->
                <div class="legend-box">
                    <h4>KETERANGAN SIMBOL / STATUS:</h4>
                    <ul>
                        <li><strong>Masuk</strong> = Jam Masuk (Sesi Pagi)</li>
                        <li><strong>Istirahat</strong> = Jam Istirahat (Keluar Siang)</li>
                        <li><strong>Masuk Ist.</strong> = Jam Masuk Kembali dari Istirahat</li>
                        <li><strong>Pulang</strong> = Jam Pulang (Selesai Kerja)</li>
                        <li><strong>Tepat Waktu</strong> = Presensi Masuk Tepat Waktu</li>
                        <li><strong>Terlambat</strong> = Presensi Masuk / Masuk Istirahat Terlambat</li>
                        <li><strong>Lebih Awal</strong> = Presensi Pulang / Istirahat Sebelum Waktunya</li>
                        <li><strong>Ditolak</strong> = Presensi Ditolak Operator (Foto Tidak Sesuai/Invalid)</li>
                        <li><strong>Tanpa Ket.</strong> = Hari Kerja Tanpa Rekaman Presensi (ALFA)</li>
                        <li><strong>Cuti</strong> = Cuti Tahunan, Cuti Sakit, Cuti Luar Negeri yang disetujui tidak dihitung Tanpa Keterangan.</li>
                    </ul>
                </div>

                <!-- Right: Signature Block -->
                <div class="signature-box">
                    <div class="signature-date">
                        {{ $setting->kota_surat ?? 'Pontianak' }}, {{ \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('D MMMM Y') }}
                    </div>
                    <div class="signature-title">
                        Mengetahui,<br>
                        {{ $setting->jabatan_ketua ?? 'Ketua Pengadilan Tinggi Pontianak' }},
                    </div>
                    <div class="signature-name">
                        {{ $setting->nama_ketua ?? 'Isnurul Syamsyul Arif' }}
                    </div>
                    @if(!empty($setting->nip_ketua))
                        <div class="signature-nip">
                            NIP. {{ $setting->nip_ketua }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>

</body>
</html>

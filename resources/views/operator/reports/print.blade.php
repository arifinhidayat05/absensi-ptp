<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if(isset($singleEmployee) && $singleEmployee)
            Laporan Presensi Harian - {{ ucwords(strtolower($singleEmployee->name)) }}
        @else
            Laporan Rekapitulasi Presensi Pegawai - {{ ucwords(strtolower($setting->satker_name ?? 'Pengadilan Tinggi Pontianak')) }}
        @endif
    </title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        @page {
            @if(isset($singleEmployee) && $singleEmployee)
                size: A4 portrait;
                margin: 12mm 15mm 12mm 15mm;
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
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            color: #000000;
            font-size: 11pt;
            line-height: 1.35;
        }

        .paper-container,
        .paper-container table,
        .paper-container th,
        .paper-container td,
        .paper-container p,
        .paper-container h1,
        .paper-container h2,
        .paper-container h3,
        .paper-container h4,
        .paper-container div,
        .paper-container span,
        .paper-container ul,
        .paper-container li {
            font-family: 'Times New Roman', Times, serif;
        }

        /* Toolbar Navigasi di Layar */
        .toolbar, .toolbar button, .toolbar a, .toolbar span {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* Pastikan Ikon FontAwesome Berfungsi Normal dan Tidak Tertimpa Font */
        i.fa-solid, i.fa-regular, i.fa-brands, .fa-solid, .fa-regular, .fa-brands, .fa, .fas, .far {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
            font-style: normal !important;
            font-weight: 900 !important;
        }
        i.fa-regular, .fa-regular, .far {
            font-weight: 400 !important;
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Toolbar Navigasi (Hanya di Layar, Tersembunyi saat Cetak) */
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid #fbbf24;
        }

        .toolbar-title {
            font-size: 12pt;
            font-weight: bold;
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
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 10pt;
            font-weight: bold;
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

        /* Kop Surat Resmi Mahkamah Agung RI */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding-bottom: 8px;
            margin-bottom: 14px;
            border-bottom: 3px double #000000;
        }

        .kop-logo {
            position: absolute;
            left: 5px;
            top: 0;
            width: 75px;
            height: 75px;
            object-fit: contain;
        }

        .kop-text {
            text-align: center;
            width: 100%;
            padding: 0 80px;
        }

        .kop-text h2 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            color: #000000;
        }

        .kop-text h1 {
            margin: 2px 0;
            font-size: 15pt;
            font-weight: bold;
            color: #000000;
        }

        .kop-text p {
            margin: 0;
            font-size: 9.5pt;
            color: #000000;
            line-height: 1.35;
        }

        /* Judul Dokumen Resmi */
        .doc-title-box {
            text-align: center;
            margin-bottom: 14px;
        }

        .doc-title {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            color: #000000;
            text-decoration: underline;
        }

        .doc-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
            margin-bottom: 8px;
            font-size: 10.5pt;
            font-weight: bold;
        }

        /* Informasi Pegawai (Bentuk Rapi Bersih Tanpa Kotak Aneh) */
        .emp-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10.5pt;
        }

        .emp-info-table td {
            padding: 3px 4px;
            vertical-align: top;
            border: none;
            color: #000000;
        }

        .emp-info-table td.lbl {
            width: 150px;
            font-weight: normal;
        }

        .emp-info-table td.sep {
            width: 12px;
            text-align: center;
        }

        .emp-info-table td.val {
            font-weight: bold;
        }

        /* Tabel Ringkasan Presensi Resmi (Bukan Kotak-Kotak Modern) */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10pt;
            text-align: center;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000000;
            padding: 5px 6px;
            vertical-align: middle;
        }

        .summary-table th {
            background-color: #f2f2f2 !important;
            font-weight: bold;
        }

        /* Tabel Detail Presensi Resmi */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 16px;
        }

        .table-custom th, 
        .table-custom td {
            border: 1px solid #000000;
            padding: 4px 6px;
            vertical-align: middle;
            color: #000000;
        }

        .table-custom thead tr th {
            background-color: #f2f2f2 !important;
            color: #000000;
            font-weight: bold;
            text-align: center;
            font-size: 10pt;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Blok Tanda Tangan */
        .footer-grid {
            display: grid;
            @if(isset($singleEmployee) && $singleEmployee)
                grid-template-columns: 1fr 1fr;
            @else
                grid-template-columns: 1.3fr 1fr;
            @endif
            gap: 24px;
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .legend-box {
            font-size: 9pt;
            color: #000000;
            line-height: 1.4;
            border: 1px solid #000000;
            padding: 6px 10px;
        }

        .legend-box h4 {
            margin: 0 0 4px 0;
            font-size: 9.5pt;
            font-weight: bold;
            color: #000000;
        }

        .legend-box ul {
            margin: 0;
            padding-left: 14px;
        }

        .signature-box {
            text-align: center;
            font-size: 11pt;
            line-height: 1.35;
        }

        .signature-date {
            margin-bottom: 4px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 55px; /* Ruang untuk tanda tangan basah */
        }

        .signature-name {
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            color: #000000;
        }

        .signature-nip {
            font-size: 10pt;
            color: #000000;
            margin-top: 2px;
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
            }

            .table-custom thead tr th,
            .summary-table th {
                background-color: #f2f2f2 !important;
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
        $bulanText = ($startMonth === $endMonth) ? $startMonth : ($startMonth . ' - ' . $endMonth);
        $bulanTitle = ucwords(strtolower($bulanText));
    @endphp

    <!-- Toolbar Navigasi Atas (Disembunyikan saat dicetak) -->
    <div class="toolbar">
        <div class="toolbar-title">
            <i class="fa-solid fa-print text-amber-300"></i>
            <span>
                @if(isset($singleEmployee) && $singleEmployee)
                    Laporan Rincian Presensi Harian: {{ ucwords(strtolower($singleEmployee->name)) }}
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

    <!-- Container Lembar Kertas Resmi -->
    <div class="paper-container">

        <!-- Kop Surat Resmi -->
        <div class="kop-surat">
            <img src="{{ asset('LOGO-PPTK.png') }}" alt="Logo" class="kop-logo" onerror="this.src='{{ asset('images/logo.png') }}'">
            <div class="kop-text">
                <h2>Mahkamah Agung Republik Indonesia</h2>
                <h1>{{ ucwords(strtolower($setting->satker_name ?? 'Pengadilan Tinggi Pontianak')) }}</h1>
                <p>
                    Jl. Jenderal Ahmad Yani No. 64, Pontianak, Kalimantan Barat 78124<br>
                    Telepon: (0561) 732442 &bull; Pos-el: info@pt-pontianak.go.id &bull; Laman: https://pt-pontianak.go.id
                </p>
            </div>
        </div>

        @if(isset($singleEmployee) && $singleEmployee)
            <!-- ========================================== -->
            <!-- MODE 1: RINCIAN PRESENSI 1 PEGAWAI / SISWA -->
            <!-- ========================================== -->

            <div class="doc-title-box">
                <h2 class="doc-title">Laporan Rincian Presensi Harian Pegawai</h2>
            </div>

            <!-- Informasi Identitas Pegawai (Format Resmi Bersih Tanpa Bentuk Kotak Aneh) -->
            <table class="emp-info-table">
                <tr>
                    <td class="lbl">Nama Lengkap</td>
                    <td class="sep">:</td>
                    <td class="val">{{ ucwords(strtolower($singleEmployee->name)) }}</td>
                    <td class="lbl" style="width: 130px;">Satuan Kerja</td>
                    <td class="sep">:</td>
                    <td class="val">{{ ucwords(strtolower($setting->satker_name ?? 'Pengadilan Tinggi Pontianak')) }}</td>
                </tr>
                <tr>
                    <td class="lbl">{{ ucwords(strtolower($singleEmployee->tipe_identitas_label ?? 'NIP / Identitas')) }}</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $singleEmployee->nip ?? '-' }}</td>
                    <td class="lbl">Periode Laporan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ \Carbon\Carbon::parse($tanggal_mulai)->locale('id')->isoFormat('D MMMM Y') }} s.d. {{ \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('D MMMM Y') }}</td>
                </tr>
                <tr>
                    <td class="lbl">Status / Jabatan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ ucwords(strtolower($singleEmployee->jabatan ?? $singleEmployee->jenis_pegawai_label)) }}</td>
                    <td class="lbl">Bulan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $bulanTitle }}</td>
                </tr>
                @if($singleEmployee->asal_instansi)
                    <tr>
                        <td class="lbl">Asal Instansi / Kampus</td>
                        <td class="sep">:</td>
                        <td class="val" colspan="4">{{ ucwords(strtolower($singleEmployee->asal_instansi)) }}</td>
                    </tr>
                @endif
            </table>

            @php
                $stat = $employeeStats[0] ?? null;
            @endphp

            @if($stat)
                <!-- Tabel Ringkasan Presensi Resmi (Format Standar Birokrasi) -->
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th style="width: 16%;">Hari Kerja</th>
                            <th style="width: 17%;">Tepat Waktu</th>
                            <th style="width: 17%;">Terlambat</th>
                            <th style="width: 16%;">Lebih Awal</th>
                            <th style="width: 17%;">Izin / Cuti</th>
                            <th style="width: 17%;">Tanpa Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>{{ $stat['total_hari_kerja'] ?? 0 }}</strong> Hari</td>
                            <td><strong>{{ $stat['tepat_waktu'] ?? 0 }}</strong> Kali</td>
                            <td><strong>{{ $stat['terlambat'] ?? 0 }}</strong> Kali</td>
                            <td><strong>{{ $stat['lebih_awal'] ?? 0 }}</strong> Kali</td>
                            <td><strong>{{ $stat['cuti_total'] ?? (($stat['cuti_tahunan'] ?? 0) + ($stat['cuti_sakit'] ?? 0) + ($stat['cuti_luar_negeri'] ?? 0) + ($stat['cuti_lainnya'] ?? 0)) }}</strong> Hari</td>
                            <td><strong>{{ $stat['tanpa_keterangan'] ?? 0 }}</strong> Hari</td>
                        </tr>
                    </tbody>
                </table>
            @endif

            <!-- Tabel Detail Harian Sesi Presensi -->
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 35px;">No.</th>
                        <th style="width: 135px;">Hari &amp; Tanggal</th>
                        <th style="width: 75px;">Jam Masuk</th>
                        <th style="width: 75px;">Jam Istirahat</th>
                        <th style="width: 75px;">Masuk Ist.</th>
                        <th style="width: 75px;">Jam Pulang</th>
                        <th>Status &amp; Keterangan Harian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyRecords as $index => $rec)
                        <tr>
                            <!-- No -->
                            <td class="text-center font-bold">{{ $index + 1 }}</td>

                            <!-- Hari & Tanggal -->
                            <td class="text-left">
                                <strong>{{ ucwords(strtolower($rec['hari'])) }}</strong>, {{ \Carbon\Carbon::parse($rec['date_str'])->format('d/m/Y') }}
                            </td>

                            <!-- Jam Masuk -->
                            <td class="text-center">
                                @if($rec['masuk'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['masuk']->waktu)->format('H:i') }}</strong></div>
                                    <div style="font-size: 8.5pt; color: #444;">
                                        @if($rec['masuk']->status === 'terlambat')
                                            Terlambat
                                        @elseif($rec['masuk']->status === 'izin')
                                            Izin
                                        @elseif($rec['masuk']->status === 'sakit')
                                            Sakit
                                        @else
                                            Tepat Waktu
                                        @endif
                                    </div>
                                @elseif($rec['leave'])
                                    <span style="font-size: 9pt;">Izin / Cuti</span>
                                @else
                                    <span style="color: #888;">-</span>
                                @endif
                            </td>

                            <!-- Jam Istirahat -->
                            <td class="text-center">
                                @if($rec['istirahat'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['istirahat']->waktu)->format('H:i') }}</strong></div>
                                    <div style="font-size: 8.5pt; color: #444;">
                                        @if($rec['istirahat']->status === 'lebih_awal')
                                            Lebih Awal
                                        @elseif($rec['istirahat']->status === 'izin')
                                            Izin
                                        @elseif($rec['istirahat']->status === 'sakit')
                                            Sakit
                                        @else
                                            Tepat
                                        @endif
                                    </div>
                                @elseif($rec['leave'])
                                    <span style="font-size: 9pt;">Izin / Cuti</span>
                                @else
                                    <span style="color: #888;">-</span>
                                @endif
                            </td>

                            <!-- Masuk Istirahat -->
                            <td class="text-center">
                                @if($rec['masuk_istirahat'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['masuk_istirahat']->waktu)->format('H:i') }}</strong></div>
                                    <div style="font-size: 8.5pt; color: #444;">
                                        @if($rec['masuk_istirahat']->status === 'terlambat')
                                            Terlambat
                                        @elseif($rec['masuk_istirahat']->status === 'izin')
                                            Izin
                                        @elseif($rec['masuk_istirahat']->status === 'sakit')
                                            Sakit
                                        @else
                                            Tepat
                                        @endif
                                    </div>
                                @elseif($rec['leave'])
                                    <span style="font-size: 9pt;">Izin / Cuti</span>
                                @else
                                    <span style="color: #888;">-</span>
                                @endif
                            </td>

                            <!-- Jam Pulang -->
                            <td class="text-center">
                                @if($rec['pulang'])
                                    <div><strong>{{ \Carbon\Carbon::parse($rec['pulang']->waktu)->format('H:i') }}</strong></div>
                                    <div style="font-size: 8.5pt; color: #444;">
                                        @if($rec['pulang']->status === 'lebih_awal')
                                            Lebih Awal
                                        @elseif($rec['pulang']->status === 'izin')
                                            Izin
                                        @elseif($rec['pulang']->status === 'sakit')
                                            Sakit
                                        @else
                                            Tepat
                                        @endif
                                    </div>
                                @elseif($rec['leave'])
                                    <span style="font-size: 9pt;">Izin / Cuti</span>
                                @else
                                    <span style="color: #888;">-</span>
                                @endif
                            </td>

                            <!-- Status & Keterangan Harian -->
                            <td class="text-left">
                                @if($rec['is_libur'])
                                    <span style="color: #555;">{{ ucwords(strtolower($rec['status_harian'])) }}</span>
                                @elseif($rec['leave'])
                                    <strong>
                                        {{ ucwords(strtolower($rec['status_harian'])) }}
                                        @if($rec['leave']->alasan) ({{ ucwords(strtolower($rec['leave']->alasan)) }}) @endif
                                    </strong>
                                @elseif($rec['status_badge_class'] === 'alfa')
                                    <span style="color: #000; font-weight: bold;">Tanpa Keterangan (Alfa)</span>
                                @elseif($rec['status_badge_class'] === 'ditolak')
                                    <strong>{{ ucwords(strtolower($rec['status_harian'])) }}</strong>
                                @elseif($rec['status_badge_class'] === 'terlambat')
                                    <strong>{{ ucwords(strtolower($rec['status_harian'])) }}</strong>
                                @elseif($rec['status_badge_class'] === 'hadir')
                                    <span>{{ ucwords(strtolower($rec['status_harian'])) }}</span>
                                @else
                                    <span>{{ ucwords(strtolower($rec['status_harian'])) }}</span>
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

            <!-- Blok Tanda Tangan Ganda (Ketua di Kiri dengan Mengetahui, Mahasiswa di Kanan) -->
            <div class="footer-grid">
                <!-- Sebelah Kiri: Pejabat Yang Mengetahui (Ketua Pengadilan) -->
                <div class="signature-box">
                    <div class="signature-date">&nbsp;</div>
                    <div class="signature-title">
                        Mengetahui,<br>
                        {{ ucwords(strtolower($setting->jabatan_ketua ?? 'Ketua Pengadilan Tinggi Pontianak')) }},
                    </div>
                    <div class="signature-name">
                        {{ ucwords(strtolower($setting->nama_ketua ?? 'Isnurul Syamsyul Arif')) }}
                    </div>
                    @if(!empty($setting->nip_ketua))
                        <div class="signature-nip">
                            NIP. {{ $setting->nip_ketua }}
                        </div>
                    @endif
                </div>

                <!-- Sebelah Kanan: Mahasiswa / Pegawai Yang Bersangkutan -->
                <div class="signature-box">
                    <div class="signature-date">
                        {{ ucwords(strtolower($setting->kota_surat ?? 'Pontianak')) }}, {{ \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('D MMMM Y') }}
                    </div>
                    <div class="signature-title">
                        <br>
                        {{ $singleEmployee->isMagang() ? 'Mahasiswa / Siswa Magang,' : 'Pegawai Yang Bersangkutan,' }}
                    </div>
                    <div class="signature-name">
                        {{ ucwords(strtolower($singleEmployee->name)) }}
                    </div>
                    @if(!empty($singleEmployee->nip))
                        <div class="signature-nip">
                            {{ $singleEmployee->tipe_identitas_label }}. {{ $singleEmployee->nip }}
                        </div>
                    @endif
                </div>
            </div>

        @else
            <!-- ========================================== -->
            <!-- MODE 2: REKAPITULASI SEMUA PEGAWAI -->
            <!-- ========================================== -->

            <div class="doc-title-box">
                <h2 class="doc-title">Laporan Rekapitulasi Presensi Pegawai Dan Magang</h2>
            </div>

            <!-- Baris Metadata Resmi -->
            <div class="doc-meta">
                <span>Satuan Kerja : {{ ucwords(strtolower($setting->satker_name ?? 'Pengadilan Tinggi Pontianak')) }}</span>
                <span>Bulan : {{ $bulanTitle }}</span>
            </div>

            <!-- Tabel Rekapitulasi Matriks Seluruh Pegawai -->
            <table class="table-custom">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 30px;">No.</th>
                        <th rowspan="2" style="width: 170px;">Nama Pegawai / Mahasiswa</th>
                        <th rowspan="2" style="width: 120px;">Jabatan</th>
                        <th rowspan="2" style="width: 90px;">Satuan Kerja</th>
                        <th colspan="4">Rekapitulasi Sesi Presensi</th>
                        <th colspan="5">Status &amp; Evaluasi Presensi</th>
                        <th rowspan="2" style="width: 140px;">Keterangan</th>
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
                            <td class="text-center font-bold">{{ $index + 1 }}</td>
                            <td class="text-left">
                                <div class="font-bold">{{ ucwords(strtolower($stat['user']->name)) }}</div>
                                @if(!empty($stat['user']->nip))
                                    <div style="font-size: 9pt; color: #333;">{{ $stat['user']->tipe_identitas_label }}. {{ $stat['user']->nip }}</div>
                                @endif
                                @if($stat['user']->asal_instansi)
                                    <div style="font-size: 8.5pt; color: #555;">{{ ucwords(strtolower($stat['user']->asal_instansi)) }}</div>
                                @endif
                            </td>
                            <td class="text-left">{{ ucwords(strtolower($stat['user']->jabatan ?? 'Pegawai')) }}</td>
                            <td class="text-center">PT. Pontianak</td>

                            <!-- Sesi Presensi -->
                            <td class="text-center font-bold">{{ $stat['masuk'] > 0 ? $stat['masuk'] : '' }}</td>
                            <td class="text-center font-bold">{{ $stat['istirahat'] > 0 ? $stat['istirahat'] : '' }}</td>
                            <td class="text-center font-bold">{{ $stat['masuk_istirahat'] > 0 ? $stat['masuk_istirahat'] : '' }}</td>
                            <td class="text-center font-bold">{{ $stat['pulang'] > 0 ? $stat['pulang'] : '' }}</td>

                            <!-- Status & Evaluasi -->
                            <td class="text-center font-bold">{{ $stat['tepat_waktu'] > 0 ? $stat['tepat_waktu'] : '' }}</td>
                            <td class="text-center font-bold">{{ $stat['terlambat'] > 0 ? $stat['terlambat'] : '' }}</td>
                            <td class="text-center font-bold">{{ $stat['lebih_awal'] > 0 ? $stat['lebih_awal'] : '' }}</td>
                            <td class="text-center font-bold">{{ $stat['ditolak'] > 0 ? $stat['ditolak'] : '' }}</td>
                            <td class="text-center font-bold">{{ $stat['tanpa_keterangan'] > 0 ? $stat['tanpa_keterangan'] : '' }}</td>

                            <!-- Keterangan Cuti -->
                            <td class="text-left" style="font-size: 9pt;">{{ $stat['keterangan'] ? ucwords(strtolower($stat['keterangan'])) : '-' }}</td>
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

            <!-- Blok Bawah: Keterangan Simbol & Tanda Tangan Resmi -->
            <div class="footer-grid">
                <!-- Sebelah Kiri: Keterangan Simbol / Status -->
                <div class="legend-box">
                    <h4>Keterangan Simbol / Status:</h4>
                    <ul>
                        <li><strong>Masuk</strong> = Jam Masuk (Sesi Pagi)</li>
                        <li><strong>Istirahat</strong> = Jam Istirahat (Keluar Siang)</li>
                        <li><strong>Masuk Ist.</strong> = Jam Masuk Kembali Dari Istirahat</li>
                        <li><strong>Pulang</strong> = Jam Pulang (Selesai Kerja)</li>
                        <li><strong>Tepat Waktu</strong> = Presensi Masuk Tepat Waktu</li>
                        <li><strong>Terlambat</strong> = Presensi Masuk / Masuk Istirahat Terlambat</li>
                        <li><strong>Lebih Awal</strong> = Presensi Pulang / Istirahat Sebelum Waktunya</li>
                        <li><strong>Ditolak</strong> = Presensi Ditolak Operator (Foto Tidak Sesuai/Invalid)</li>
                        <li><strong>Tanpa Ket.</strong> = Hari Kerja Tanpa Rekaman Presensi (Alfa)</li>
                        <li><strong>Cuti</strong> = Cuti Tahunan, Cuti Sakit, Cuti Alasan Penting Yang Disetujui.</li>
                    </ul>
                </div>

                <!-- Sebelah Kanan: Blok Tanda Tangan Ketua -->
                <div class="signature-box">
                    <div class="signature-date">
                        {{ ucwords(strtolower($setting->kota_surat ?? 'Pontianak')) }}, {{ \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->isoFormat('D MMMM Y') }}
                    </div>
                    <div class="signature-title">
                        Mengetahui,<br>
                        {{ ucwords(strtolower($setting->jabatan_ketua ?? 'Ketua Pengadilan Tinggi Pontianak')) }},
                    </div>
                    <div class="signature-name">
                        {{ ucwords(strtolower($setting->nama_ketua ?? 'Isnurul Syamsyul Arif')) }}
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

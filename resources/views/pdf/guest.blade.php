<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Buku Tamu Digital</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 4px 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h3 {
            margin: 0 0 4px 0;
            font-size: 13px;
            font-weight: normal;
        }
        .header p {
            margin: 0;
            font-size: 10px;
            color: #666;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .signature-section {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>PEMERINTAH KABUPATEN / KOTA</h2>
        <h3>DINAS KOMUNIKASI DAN INFORMATIKA</h3>
        <p>Rekapitulasi Kunjungan Kedinasan & Tamu Kantor (Digital Guestbook)</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 75px;">Tanggal</th>
                <th style="width: 110px;">Nama Tamu</th>
                <th style="width: 110px;">Asal Instansi / OPD</th>
                <th style="width: 65px; text-align: center;">Masuk</th>
                <th style="width: 65px; text-align: center;">Keluar</th>
                <th>Maksud & Keperluan Kunjungan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lists as $idx => $item)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td>{{ $item->tanggal }}</td>
                <td><strong>{{ $item->nama }}</strong></td>
                <td>{{ $item->instansi }}</td>
                <td style="text-align: center;">{{ $item->jam_masuk }}</td>
                <td style="text-align: center;">{{ $item->jam_keluar }}</td>
                <td>{{ $item->keperluan }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #94a3b8;">Tidak ada data kunjungan tamu.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    <strong>Kasubag Umum & Kepegawaian</strong>
                    <br><br><br><br>
                    ( ..................................................... )<br>
                    NIP. ...............................................
                </td>
                <td>
                    Dicetak pada: {{ date('d F Y, H:i') }}<br>
                    <strong>Petugas Resepsionis / Penerima Tamu</strong>
                    <br><br><br><br>
                    ( <strong>{{ Auth::user()->name ?? 'Petugas Resepsionis' }}</strong> )<br>
                    NIP / ID. {{ Auth::user()->uuid ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pemeliharaan Infrastruktur Jaringan</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm 1.5cm 2cm 1.5cm;
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
        <p>Laporan Resmi Pemeliharaan Preventif & Perawatan Perangkat Jaringan (Maintenance Log)</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 80px;">Tanggal Mulai</th>
                <th style="width: 120px;">Judul Pemeliharaan</th>
                <th style="width: 100px;">Lokasi / OPD</th>
                <th>Alur & Prosedur Perawatan</th>
                <th>Kendala / Problem Ditemukan</th>
                <th style="width: 90px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lists as $idx => $item)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $item->tanggal_mulai }}</strong><br>
                    <span style="color: #64748b;">{{ $item->jam_mulai }}</span>
                </td>
                <td><strong>{{ $item->judul }}</strong></td>
                <td>{{ $item->lokasi }}</td>
                <td>{{ $item->alur_perawatan ?? '-' }}</td>
                <td>{{ $item->problem ?? 'Tidak ada kendala / Normal' }}</td>
                <td>{{ $item->petugas }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #94a3b8;">Tidak ada data pemeliharaan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    <strong>Kepala Bidang Infrastruktur Jaringan</strong>
                    <br><br><br><br>
                    ( ..................................................... )<br>
                    NIP. ...............................................
                </td>
                <td>
                    Dicetak pada: {{ date('d F Y, H:i') }}<br>
                    <strong>Petugas Pelaksana Pemeliharaan</strong>
                    <br><br><br><br>
                    ( <strong>{{ Auth::user()->name ?? 'Administrator IT' }}</strong> )<br>
                    NIP / ID. {{ Auth::user()->uuid ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Inventaris Perangkat & Aset Jaringan</title>
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
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-info { background-color: #e0f2fe; color: #075985; }
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
        <p>Buku Inventaris Perangkat & Aset Jaringan Infrastruktur Komunikasi</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 100px;">Kategori</th>
                <th style="width: 90px;">Merek / Brand</th>
                <th style="width: 110px;">Tipe / Model</th>
                <th style="width: 110px;">Nomor Seri (SN)</th>
                <th style="width: 100px;">Lokasi Penempatan</th>
                <th style="width: 80px;">Pengadaan</th>
                <th style="width: 75px; text-align: center;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lists as $idx => $item)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td>{{ $item->category->name ?? '-' }}</td>
                <td>{{ $item->brand->name ?? '-' }}</td>
                <td><strong>{{ $item->type ?? '-' }}</strong></td>
                <td><code>{{ $item->serial ?? '-' }}</code></td>
                <td>{{ $item->location->name ?? '-' }}</td>
                <td>{{ ucfirst($item->method ?? '-') }}</td>
                <td style="text-align: center;">
                    @if($item->status == 'terpasang')
                        <span class="badge badge-success">TERPASANG</span>
                    @elseif($item->status == 'backup')
                        <span class="badge badge-info">BACKUP</span>
                    @else
                        <span class="badge badge-warning">{{ strtoupper($item->status ?? 'IDLE') }}</span>
                    @endif
                </td>
                <td>{{ $item->notes ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; color: #94a3b8;">Tidak ada data inventaris.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    <strong>Pengurus Barang Pengguna</strong>
                    <br><br><br><br>
                    ( ..................................................... )<br>
                    NIP. ...............................................
                </td>
                <td>
                    Dicetak pada: {{ date('d F Y, H:i') }}<br>
                    <strong>Pengelola Aset & Infrastruktur TIK</strong>
                    <br><br><br><br>
                    ( <strong>{{ Auth::user()->name ?? 'Administrator IT' }}</strong> )<br>
                    NIP / ID. {{ Auth::user()->uuid ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

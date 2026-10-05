<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penanganan Gangguan Jaringan</title>
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
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 4px 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .meta-table td {
            font-size: 11px;
            padding: 3px 0;
        }
        .metrics-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 15px;
        }
        .metrics-grid {
            width: 100%;
        }
        .metrics-grid td {
            text-align: center;
            padding: 4px 8px;
        }
        .metric-title {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
        }
        .metric-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
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
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
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
        <p>Laporan Resmi Rekapitulasi Penanganan Gangguan Jaringan & Insiden IT (Trouble Ticket)</p>
    </div>

    <div class="metrics-box">
        <table class="metrics-grid">
            <tr>
                <td>
                    <div class="metric-title">Total Tiket Selesai</div>
                    <div class="metric-value">{{ $metrics['total_resolved'] ?? count($lists) }}</div>
                </td>
                <td>
                    <div class="metric-title">Mean Time to Resolve (MTTR)</div>
                    <div class="metric-value">{{ $metrics['mttr_formatted'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="metric-title">SLA Kepatuhan (&le; 4 Jam)</div>
                    <div class="metric-value">{{ $metrics['sla_rate'] ?? 100 }}%</div>
                </td>
                <td>
                    <div class="metric-title">Tiket Tepat Waktu (SLA Compliant)</div>
                    <div class="metric-value">{{ $metrics['sla_compliant'] ?? 0 }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 80px;">Waktu Masuk</th>
                <th style="width: 80px;">Waktu Selesai</th>
                <th style="width: 90px;">Lokasi</th>
                <th style="width: 70px;">Kategori</th>
                <th>Permasalahan / Problem</th>
                <th>Tindakan & Solusi</th>
                <th style="width: 60px; text-align: center;">Durasi</th>
                <th style="width: 70px; text-align: center;">Status SLA</th>
                <th style="width: 80px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lists as $idx => $item)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $item->tgl_trouble }}</strong><br>
                    <span style="color: #64748b;">{{ $item->jam_trouble }}</span>
                </td>
                <td>
                    <strong>{{ $item->tgl_selesai ?? '-' }}</strong><br>
                    <span style="color: #64748b;">{{ $item->jam_selesai ?? '-' }}</span>
                </td>
                <td>{{ $item->lokasi }}</td>
                <td><span class="badge badge-info">{{ strtoupper($item->kategori) }}</span></td>
                <td>{{ $item->problem }}</td>
                <td>{{ $item->solusi ?? '-' }}</td>
                <td style="text-align: center;">
                    @if($item->durasi_menit !== null)
                        {{ floor($item->durasi_menit / 60) }}j {{ $item->durasi_menit % 60 }}m
                    @else
                        -
                    @endif
                </td>
                <td style="text-align: center;">
                    @if($item->sla_status === 'compliant' || ($item->durasi_menit !== null && $item->durasi_menit <= 240))
                        <span class="badge badge-success">COMPLIANT</span>
                    @elseif($item->sla_status === 'breached' || ($item->durasi_menit !== null && $item->durasi_menit > 240))
                        <span class="badge badge-danger">BREACHED</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->petugas }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; color: #94a3b8;">Tidak ada data gangguan pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    <strong>Kepala Bidang TIK / Jaringan</strong>
                    <br><br><br><br>
                    ( ..................................................... )<br>
                    NIP. ...............................................
                </td>
                <td>
                    Dicetak pada: {{ date('d F Y, H:i') }}<br>
                    <strong>Petugas / Penanggung Jawab IT</strong>
                    <br><br><br><br>
                    ( <strong>{{ Auth::user()->name ?? 'Administrator IT' }}</strong> )<br>
                    NIP / ID. {{ Auth::user()->uuid ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

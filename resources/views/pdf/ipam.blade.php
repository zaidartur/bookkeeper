<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Alokasi IP Address (IPAM)</title>
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
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 6px;
            font-size: 11px;
        }
        .meta-table td.label {
            font-weight: bold;
            color: #475569;
            width: 18%;
        }
        .meta-table td.value {
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
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-info { background-color: #e0f2fe; color: #0369a1; }
        .badge-warning { background-color: #fef9c3; color: #a16207; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .footer-sign {
            margin-top: 30px;
            width: 100%;
        }
        .sign-box {
            float: right;
            width: 250px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>PEMERINTAH KABUPATEN / KOTA</h2>
        <h3>DINAS KOMUNIKASI DAN INFORMATIKA</h3>
        <p>Bidang Pengelolaan Infrastruktur Jaringan & Tata Kelola IP Address (IPAM)</p>
    </div>

    <div style="text-align: center; margin-bottom: 15px;">
        <strong style="font-size: 14px; text-decoration: underline;">LAPORAN ALOKASI & TATA KELOLA SUBNET IP ADDRESS</strong><br>
        <span style="font-size: 10px; color: #64748b;">Dicetak pada: {{ $printed_at }} | Oleh: {{ $printed_by }}</span>
    </div>

    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="label">Alamat Jaringan (Network):</td>
                <td class="value"><strong>{{ $subnet->network_ip }} / {{ $subnet->cidr }}</strong></td>
                <td class="label">Total Kapasitas IP:</td>
                <td class="value">{{ number_format($subnet->total_ip, 0, ',', '.') }} Host</td>
            </tr>
            <tr>
                <td class="label">Subnet Mask:</td>
                <td class="value">{{ $subnet->subnet_mask }}</td>
                <td class="label">IP Terpakai / Assigned:</td>
                <td class="value"><strong>{{ $stats['used'] }} IP ({{ $stats['utilization'] }}%)</strong></td>
            </tr>
            <tr>
                <td class="label">Keterangan / Lokasi:</td>
                <td class="value">{{ $subnet->keterangan ?: 'Infrastruktur Jaringan Lokal' }}</td>
                <td class="label">IP Tersedia / Bebas:</td>
                <td class="value"><span style="color: #16a34a; font-weight: bold;">{{ $stats['free'] }} IP</span></td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 15%;">IP Address</th>
                <th style="width: 22%;">Nama Perangkat / Host</th>
                <th style="width: 14%;">Kategori</th>
                <th style="width: 10%;">Tipe / Status</th>
                <th style="width: 16%;">MAC Address</th>
                <th style="width: 18%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignments as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td><strong style="color: #0284c7;">{{ $item->assigned_ip }}</strong></td>
                    <td><strong>{{ $item->device }}</strong></td>
                    <td>{{ $item->kategori }}</td>
                    <td>
                        @if($item->status == 'Static')
                            <span class="badge badge-info">Static</span>
                        @elseif($item->status == 'DHCP')
                            <span class="badge badge-success">DHCP</span>
                        @else
                            <span class="badge badge-warning">{{ $item->status ?: 'Assigned' }}</span>
                        @endif
                    </td>
                    <td><code style="font-size: 10px;">{{ $item->mac_address ?: '-' }}</code></td>
                    <td>{{ $item->keterangan ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">
                        Belum ada IP address yang dialokasikan pada subnet ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-sign">
        <div class="sign-box">
            <p style="margin-bottom: 50px;">
                Dicetak pada {{ date('d F Y') }}<br>
                Penanggung Jawab Jaringan & Server,
            </p>
            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 2px;">( {{ $printed_by }} )</p>
            <p style="font-size: 10px; color: #64748b; margin: 0;">Administrator Jaringan Dinas</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
        }
        h1 {
            text-align: center;
            font-size: 16px;
            margin: 0 0 4px 0;
        }
        .subtitle {
            text-align: center;
            font-size: 11px;
            color: #555;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #999;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #F05D24;
            color: #fff;
            font-size: 9.5px;
        }
        tr:nth-child(even) td {
            background-color: #f3f4f6;
        }
        .text-center { text-align: center; }
        .footer {
            margin-top: 10px;
            font-size: 9px;
            color: #555;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="subtitle">PT Banyan Koalindo Lestari &mdash; Dicetak: {{ $tanggal }}</div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th>Kode Kontrak</th>
                <th>Vendor</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Berakhir</th>
                <th>Durasi</th>
                <th>Biaya Sewa</th>
                <th>Status Kontrak</th>
                <th>Jumlah Aset</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kontrakList as $i => $k)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $k->kode_kontrak }}</td>
                <td>{{ $k->vendor }}</td>
                <td>{{ $k->tanggal_mulai ? $k->tanggal_mulai->format('d/m/Y') : '-' }}</td>
                <td>{{ $k->tanggal_berakhir ? $k->tanggal_berakhir->format('d/m/Y') : '-' }}</td>
                <td>{{ $k->durasi ? $k->durasi . ' Bulan' : '-' }}</td>
                <td>Rp {{ number_format($k->biaya_sewa, 0, ',', '.') }}</td>
                <td>{{ $k->status_kontrak }}</td>
                <td class="text-center">{{ $k->inventaris_count ?? $k->inventaris()->count() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">Tidak ada data kontrak.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Total: {{ $kontrakList->count() }} kontrak</div>
</body>
</html>

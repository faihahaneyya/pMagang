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
                <th>Kode Inventaris</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Status Aset</th>
                <th>Status Kontrak</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventaris as $i => $inv)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $inv->kode_inventaris }}</td>
                <td>{{ $inv->nama_barang }}</td>
                <td>{{ $inv->kategori ?: '-' }}</td>
                <td>{{ $inv->lokasi ?: '-' }}</td>
                <td>{{ $inv->status_aset }}</td>
                <td>{{ $inv->status_kontrak }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada data inventaris.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Total: {{ count($inventaris) }} item</div>
</body>
</html>

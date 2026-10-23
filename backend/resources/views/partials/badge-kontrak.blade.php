@php
    $badgeClass = match($status) {
        'AKTIF', 'Sedang Berjalan' => 'badge-berjalan',
        'AKAN BERAKHIR', 'Akan Berakhir' => 'badge-akan-berakhir',
        'BERAKHIR', 'Habis' => 'badge-habis',
        '-' => 'badge-no-contract',
        default => 'badge-no-contract',
    };
@endphp
<span class="badge {{ $badgeClass }}">{{ $status }}</span>

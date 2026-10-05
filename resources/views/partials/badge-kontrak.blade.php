@php
    $badgeClass = match($status) {
        'Sedang Berjalan' => 'badge-berjalan',
        'Akan Berakhir' => 'badge-akan-berakhir',
        'Habis' => 'badge-habis',
        '-' => 'badge-no-contract',
        default => 'badge-no-contract',
    };
@endphp
<span class="badge {{ $badgeClass }}">{{ $status }}</span>

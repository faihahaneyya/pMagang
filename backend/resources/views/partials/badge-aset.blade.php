@php
    $badgeClass = match($status) {
        'Ada' => 'badge-ada',
        'Hilang' => 'badge-hilang',
        'Rusak' => 'badge-rusak',
        'Tidak Ditemukan' => 'badge-tidak-ditemukan',
        default => 'badge-tidak-ditemukan',
    };
@endphp
<span class="badge {{ $badgeClass }}">{{ $status }}</span>

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Kontrak extends Model
{
    protected $table = 'kontrak';

    protected $fillable = [
        'kode_kontrak',
        'vendor',
        'tanggal_mulai',
        'tanggal_berakhir',
        'durasi',
        'biaya_sewa',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
        'biaya_sewa' => 'decimal:2',
    ];

    /**
     * Hitung status kontrak berdasarkan aturan bisnis:
     * - Sisa > 30 hari: AKTIF
     * - Sisa 0 s/d 30 hari (termasuk hari ini): AKAN BERAKHIR
     * - Tanggal berakhir sudah lewat: BERAKHIR
     */
    public function getStatusKontrakAttribute(): string
    {
        if (!$this->tanggal_berakhir) {
            return '-';
        }

        $today = Carbon::today();
        $berakhir = Carbon::parse($this->tanggal_berakhir)->startOfDay();

        // Tanggal berakhir sudah lewat (kemarin atau sebelumnya)
        if ($today->gt($berakhir)) {
            return 'BERAKHIR';
        }

        // Sisa hari (positif atau 0 jika hari ini)
        $daysLeft = $today->diffInDays($berakhir, false);
        if ($daysLeft <= 30) {
            return 'AKAN BERAKHIR';
        }

        return 'AKTIF';
    }

    public function getSisaHariAttribute(): int
    {
        if (!$this->tanggal_berakhir) {
            return 0;
        }

        $today = Carbon::today();
        $berakhir = Carbon::parse($this->tanggal_berakhir)->startOfDay();
        return (int) $today->diffInDays($berakhir, false);
    }

    public function getSisaKontrakAttribute(): string
    {
        if (!$this->tanggal_berakhir) {
            return '-';
        }

        $today = Carbon::today();
        $berakhir = Carbon::parse($this->tanggal_berakhir)->startOfDay();

        if ($today->gt($berakhir)) {
            $days = $berakhir->diffInDays($today);
            return "Berakhir {$days} hari lalu";
        }

        $days = (int) $today->diffInDays($berakhir, false);
        if ($days == 0) {
            return 'Berakhir hari ini';
        }
        if ($days <= 30) {
            return "{$days} hari lagi";
        }
        $months = floor($days / 30);
        $remDays = $days % 30;
        if ($remDays > 0) {
            return "{$months} bulan {$remDays} hari lagi";
        }
        return "{$months} bulan lagi";
    }

    public function getDurasiAttribute($value): string
    {
        if ($value) return $value;

        if ($this->tanggal_mulai && $this->tanggal_berakhir) {
            $months = $this->tanggal_mulai->diffInMonths($this->tanggal_berakhir);
            if ($months > 0) {
                return "{$months} Bulan";
            }
            $days = $this->tanggal_mulai->diffInDays($this->tanggal_berakhir);
            return "{$days} Hari";
        }

        return '-';
    }

    public function getTotalBiayaAttribute(): float
    {
        if (!$this->tanggal_mulai || !$this->tanggal_berakhir) return 0;
        $months = $this->tanggal_mulai->diffInMonths($this->tanggal_berakhir);
        return $this->biaya_sewa * max($months, 1);
    }

    public function getNilaiKontrakAttribute(): float
    {
        return $this->getTotalBiayaAttribute();
    }

    public function inventaris(): HasMany
    {
        return $this->hasMany(Inventaris::class, 'kontrak_id');
    }

    public function getJumlahInventarisAttribute(): int
    {
        return $this->inventaris()->count();
    }
}

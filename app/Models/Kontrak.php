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
     * Get status kontrak based on dates.
     * Thresholds: Akan Berakhir = within 30 days
     */
    public function getStatusKontrakAttribute(): string
    {
        $today = Carbon::today();
        $berakhir = $this->tanggal_berakhir;

        if ($today->gt($berakhir)) {
            return 'Habis';
        }

        $daysLeft = $today->diffInDays($berakhir, false);
        if ($daysLeft <= 30) {
            return 'Akan Berakhir';
        }

        return 'Sedang Berjalan';
    }

    public function getSisaKontrakAttribute(): string
    {
        $today = Carbon::today();
        $berakhir = $this->tanggal_berakhir;

        if ($today->gt($berakhir)) {
            $days = $berakhir->diffInDays($today);
            return "Habis {$days} hari lalu";
        }

        $days = $today->diffInDays($berakhir);
        if ($days == 0) {
            return 'Berakhir hari ini';
        }
        if ($days < 30) {
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

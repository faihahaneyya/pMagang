<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventaris extends Model
{
    protected $table = 'inventaris';

    protected $fillable = [
        'kode_inventaris',
        'nama_barang',
        'kategori',
        'merek',
        'spesifikasi',
        'vendor',
        'lokasi',
        'kondisi',
        'status_aset',
        'keterangan',
        'foto',
        'kontrak_id',
    ];

    protected $casts = [
        'status_aset' => 'string',
    ];

    public function kontrak(): BelongsTo
    {
        return $this->belongsTo(Kontrak::class, 'kontrak_id');
    }

    public function getStatusKontrakAttribute(): string
    {
        if (!$this->kontrak_id || !$this->kontrak) {
            return '-';
        }
        return $this->kontrak->status_kontrak;
    }

    public function getKodeKontrakAttribute(): string
    {
        if (!$this->kontrak_id || !$this->kontrak) {
            return '-';
        }
        return $this->kontrak->kode_kontrak;
    }

    public function getSisaKontrakAttribute(): string
    {
        if (!$this->kontrak_id || !$this->kontrak) {
            return '-';
        }
        return $this->kontrak->sisa_kontrak;
    }

    public function getBarcodeUrlAttribute(): string
    {
        return url('/inventaris/' . $this->kode_inventaris);
    }
}

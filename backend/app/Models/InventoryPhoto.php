<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPhoto extends Model
{
    protected $table = 'inventory_photos';

    protected $fillable = [
        'inventaris_id',
        'foto_path',
        'keterangan_foto',
    ];

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class, 'inventaris_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->foto_path);
    }
}

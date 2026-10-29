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

    protected $appends = [
        'photo_id',
        'file_name',
        'file_path',
        'description',
        'url',
    ];

    public function getPhotoIdAttribute(): int
    {
        return $this->id;
    }

    public function getFileNameAttribute(): string
    {
        return basename($this->foto_path);
    }

    public function getFilePathAttribute(): string
    {
        return (string) $this->foto_path;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->keterangan_foto;
    }

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class, 'inventaris_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->foto_path);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    protected $table = 'contract_items';

    protected $fillable = [
        'contract_id',
        'inventaris_id',
    ];

    /**
     * Setiap contract_item milik satu kontrak.
     */
    public function kontrak(): BelongsTo
    {
        return $this->belongsTo(Kontrak::class, 'contract_id');
    }

    /**
     * Setiap contract_item merujuk ke satu inventaris.
     */
    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class, 'inventaris_id');
    }
}

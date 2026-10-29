<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
    ];

    protected $appends = [
        'vendor_id',
    ];

    /**
     * Alias vendor_id accessor sesuai ERD
     */
    public function getVendorIdAttribute(): int
    {
        return $this->id;
    }

    /**
     * Satu vendor dapat memiliki banyak kontrak.
     */
    public function kontraks(): HasMany
    {
        return $this->hasMany(Kontrak::class, 'vendor_id');
    }

    /**
     * Jumlah kontrak yang dimiliki vendor.
     */
    public function getJumlahKontrakAttribute(): int
    {
        return $this->kontraks()->count();
    }
}

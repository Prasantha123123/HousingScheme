<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shop extends Model
{
    protected $table = 'Shops';                 // from your migration
    protected $primaryKey = 'shopNumber';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'shopNumber', 'MerchantId', 'leaseEnd', 'rentalAmount', 'timestamp',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'MerchantId'); // users.id
    }
}

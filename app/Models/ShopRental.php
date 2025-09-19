<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopRental extends Model
{
    protected $table = 'ShopRental';
    public $timestamps = false;

    protected $fillable = [
        'shopNumber','billAmount','month','paidAmount','paymentMethod',
        'recipt','status','timestamp','approved_at','customer_paid_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'timestamp' => 'datetime',
        'customer_paid_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        // Shop primary key is shopNumber (string)
        return $this->belongsTo(Shop::class, 'shopNumber', 'shopNumber');
    }
}

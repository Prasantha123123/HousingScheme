<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseRental extends Model
{
    protected $table = 'HouseRental';
    public $timestamps = false;

    protected $fillable = [
        'houseNo','readingUnit','month','openingReadingUnit','billAmount',
        'paidAmount','paymentMethod','recipt','status','timestamp','customer_paid_at','approved_at'
    ];

    protected $casts = [
        'customer_paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'timestamp' => 'datetime',
    ];
}

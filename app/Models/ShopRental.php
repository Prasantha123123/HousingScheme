<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopRental extends Model
{
    protected $table = 'ShopRental';
    public $timestamps = false;

    protected $fillable = [
        'shopNumber','billAmount','month','paidAmount','paymentMethod',
        'recipt','status','timestamp'
    ];
}

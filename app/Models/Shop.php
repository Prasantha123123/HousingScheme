<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = 'Shops';
    protected $primaryKey = 'shopNumber';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['shopNumber','MerchantId','leaseEnd','rentalAmount','timestamp'];
}

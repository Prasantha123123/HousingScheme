<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = 'Shops';                 // capital S from your migration
    protected $primaryKey = 'shopNumber';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;                 // you use a single "timestamp" column

    protected $fillable = [
        'shopNumber', 'MerchantId', 'leaseEnd', 'rentalAmount', 'timestamp',
    ];

    public function merchant()
    {
        return $this->belongsTo(User::class, 'MerchantId');
    }
}

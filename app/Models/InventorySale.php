<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySale extends Model
{
    public $timestamps = false;
    protected $fillable = ['date','item','qty','unit_price','total','note','timestamp'];
}

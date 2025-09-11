<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    protected $table = 'houses';
    protected $primaryKey = 'houseNo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // only custom "timestamp" column in table

    protected $fillable = ['houseNo', 'HouseOwneId'];

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'HouseOwneId');
    }
}

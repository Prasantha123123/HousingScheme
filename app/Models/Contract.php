<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'Contract';
    public $timestamps = false;

    protected $fillable = ['EmployeeId','contractType','waheAmount','timestamp'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'Payroll';
    public $timestamps = false;

    protected $fillable = [
        'EmployeeId','workdays','wage_net','deduction','files','paidType','status','timestamp'
    ];
}

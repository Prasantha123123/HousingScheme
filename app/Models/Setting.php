<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    protected $fillable = ['key','value'];

    public static function get(string $key, $default = null) {
        $row = static::where('key',$key)->first();
        return $row ? $row->value : $default;
    }
    public static function setVal(string $key, $value) {
        return static::updateOrCreate(['key'=>$key], ['value'=>$value]);
    }
}

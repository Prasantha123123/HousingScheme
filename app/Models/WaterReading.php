<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterReading extends Model
{
    // Match the capitalized naming you already use (e.g., ShopRental, Houses)
    protected $table = 'WaterReadings';

    protected $fillable = [
        'houseNo',
        'month',
        'openingReadingUnit',
        'readingUnit',
        'source',
        'note',
        'status', 
    ];

    // Convenient computed attribute (no DB column needed)
    public function getUsageAttribute(): int
    {
        return max(0, (int)$this->readingUnit - (int)$this->openingReadingUnit);
    }

    // Relationships (optional, if you want them)
    // public function house()
    // {
    //     return $this->belongsTo(House::class, 'houseNo', 'houseNo');
    // }
}

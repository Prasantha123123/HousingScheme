<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WaterReading;

class WaterReadingSeeder extends Seeder
{
    public function run(): void
    {
        $month = now()->format('Y-m'); // e.g., 2025-09

        WaterReading::updateOrCreate(
            [
                'houseNo' => 'H 1',
                'month'   => $month,
            ],
            [
                'openingReadingUnit' => 100,
                'readingUnit'        => 200,
                'source'             => 'manual', // <-- use an allowed value
                'note'               => 'Initial reading for current month (seed)',
            ]
        );
    }
}

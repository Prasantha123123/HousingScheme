<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WaterReading;
use Carbon\Carbon;

class WaterReadingSeeder extends Seeder
{
    public function run(): void
    {
        $houseNo = 'H 1';

        // Current month
        $this->seedReading($houseNo, now()->format('Y-m'), 100, 200);

        // Previous month
        $this->seedReading($houseNo, now()->subMonth()->format('Y-m'), 50, 100);

        // Two months ago
        $this->seedReading($houseNo, now()->subMonths(2)->format('Y-m'), 20, 50);
    }

    private function seedReading(string $houseNo, string $month, int $opening, int $closing): void
    {
        WaterReading::updateOrCreate(
            [
                'houseNo' => $houseNo,
                'month'   => $month,
            ],
            [
                'openingReadingUnit' => $opening,
                'readingUnit'        => $closing,
                'source'             => 'manual',
                'note'               => "Seeded reading for $month",
            ]
        );
    }
}


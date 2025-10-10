<?php

namespace App\Console\Commands;

use App\Models\House;
use App\Models\Shop;
use App\Models\HouseRental;
use App\Models\ShopRental;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyBills extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bills:generate 
                           {--month= : The month to generate bills for (YYYY-MM format)}
                           {--force : Force generation even if bills already exist}
                           {--dry-run : Show what would be generated without creating records}';

    /**
     * The console command description.
     */
    protected $description = 'Generate monthly rental bills for houses and shops';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $month = $this->option('month') ?: Carbon::now()->format('Y-m');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("Generating monthly bills for: {$month}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No records will be created");
        }

        try {
            // Validate month format
            $billDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $this->error("Invalid month format. Please use YYYY-MM format (e.g., 2024-01)");
            return 1;
        }

        $houseBillsGenerated = $this->generateHouseBills($billDate, $force, $dryRun);
        $shopBillsGenerated = $this->generateShopBills($billDate, $force, $dryRun);

        $totalBills = $houseBillsGenerated + $shopBillsGenerated;

        if ($dryRun) {
            $this->info("DRY RUN SUMMARY:");
            $this->info("Would generate {$houseBillsGenerated} house bills");
            $this->info("Would generate {$shopBillsGenerated} shop bills");
            $this->info("Total bills that would be generated: {$totalBills}");
        } else {
            $this->info("Bills generation completed!");
            $this->info("House bills generated: {$houseBillsGenerated}");
            $this->info("Shop bills generated: {$shopBillsGenerated}");
            $this->info("Total bills generated: {$totalBills}");
        }

        return 0;
    }

    /**
     * Generate house rental bills for the given month.
     */
    protected function generateHouseBills(Carbon $billDate, bool $force, bool $dryRun): int
    {
        $this->info("Generating house bills...");
        
        $houses = House::whereNotNull('user_id')->get();
        $billsGenerated = 0;

        foreach ($houses as $house) {
            // Check if bill already exists for this month
            $existingBill = HouseRental::where('house_no', $house->houseNo)
                ->whereYear('bill_date', $billDate->year)
                ->whereMonth('bill_date', $billDate->month)
                ->first();

            if ($existingBill && !$force) {
                $this->warn("Bill already exists for House {$house->houseNo} in {$billDate->format('Y-m')} - skipping");
                continue;
            }

            if ($existingBill && $force) {
                $this->warn("Bill exists for House {$house->houseNo} in {$billDate->format('Y-m')} - forcing recreation");
                if (!$dryRun) {
                    $existingBill->delete();
                }
            }

            if ($dryRun) {
                $this->line("Would create bill for House {$house->houseNo} - Amount: LKR " . number_format($house->rental, 2));
            } else {
                HouseRental::create([
                    'house_no' => $house->houseNo,
                    'user_id' => $house->user_id,
                    'bill_date' => $billDate->copy(),
                    'due_date' => $billDate->copy()->addDays(15), // 15 days from bill date
                    'rental_amount' => $house->rental,
                    'electricity_amount' => 0.00, // Can be updated later
                    'water_amount' => 0.00, // Can be updated later
                    'other_amount' => 0.00, // Can be updated later
                    'total_amount' => $house->rental,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->line("Created bill for House {$house->houseNo} - Amount: LKR " . number_format($house->rental, 2));
            }

            $billsGenerated++;
        }

        return $billsGenerated;
    }

    /**
     * Generate shop rental bills for the given month.
     */
    protected function generateShopBills(Carbon $billDate, bool $force, bool $dryRun): int
    {
        $this->info("Generating shop bills...");
        
        $shops = Shop::whereNotNull('user_id')->get();
        $billsGenerated = 0;

        foreach ($shops as $shop) {
            // Check if bill already exists for this month
            $existingBill = ShopRental::where('shop_no', $shop->shopNo)
                ->whereYear('bill_date', $billDate->year)
                ->whereMonth('bill_date', $billDate->month)
                ->first();

            if ($existingBill && !$force) {
                $this->warn("Bill already exists for Shop {$shop->shopNo} in {$billDate->format('Y-m')} - skipping");
                continue;
            }

            if ($existingBill && $force) {
                $this->warn("Bill exists for Shop {$shop->shopNo} in {$billDate->format('Y-m')} - forcing recreation");
                if (!$dryRun) {
                    $existingBill->delete();
                }
            }

            if ($dryRun) {
                $this->line("Would create bill for Shop {$shop->shopNo} - Amount: LKR " . number_format($shop->rental, 2));
            } else {
                ShopRental::create([
                    'shop_no' => $shop->shopNo,
                    'user_id' => $shop->user_id,
                    'bill_date' => $billDate->copy(),
                    'due_date' => $billDate->copy()->addDays(15), // 15 days from bill date
                    'rental_amount' => $shop->rental,
                    'electricity_amount' => 0.00, // Can be updated later
                    'water_amount' => 0.00, // Can be updated later
                    'other_amount' => 0.00, // Can be updated later
                    'total_amount' => $shop->rental,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->line("Created bill for Shop {$shop->shopNo} - Amount: LKR " . number_format($shop->rental, 2));
            }

            $billsGenerated++;
        }

        return $billsGenerated;
    }
}
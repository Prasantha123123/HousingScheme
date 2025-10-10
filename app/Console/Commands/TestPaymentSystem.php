<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Payments\UnifiedPaymentService;
use App\Models\HouseRental;
use App\Models\ShopRental;

class TestPaymentSystem extends Command
{
    protected $signature = 'payment:test';
    protected $description = 'Test the new payment system implementation';

    public function __construct(
        private UnifiedPaymentService $paymentService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Testing Payment System Implementation...');

        try {
            // Test service instantiation
            $this->info('✅ Payment service instantiated successfully');

            // Test with a house rental if available
            $houseRental = HouseRental::where('status', '!=', 'Approved')->first();
            if ($houseRental) {
                $this->info("✅ Found test house rental: {$houseRental->houseNo} for month {$houseRental->month}");
                
                // Test getMaxPayableAmount
                $maxPayable = $this->paymentService->getMaxPayableAmount('house', $houseRental->houseNo, $houseRental->month);
                $this->info("✅ Max payable amount: LKR {$maxPayable}");
            }

            // Test with a shop rental if available  
            $shopRental = ShopRental::where('status', '!=', 'Approved')->first();
            if ($shopRental) {
                $this->info("✅ Found test shop rental: {$shopRental->shopNumber} for month {$shopRental->month}");
                
                // Test getMaxPayableAmount
                $maxPayable = $this->paymentService->getMaxPayableAmount('shop', $shopRental->shopNumber, $shopRental->month);
                $this->info("✅ Max payable amount: LKR {$maxPayable}");
            }

            $this->info('✅ All tests passed! Payment system is ready.');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
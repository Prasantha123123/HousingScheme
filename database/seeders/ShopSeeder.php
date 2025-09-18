<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure a few Merchant users exist
        $merchant1 = User::firstOrCreate(
            ['email' => 'merchant1@example.com'],
            [
                'name' => 'Merchant One',
                'role' => 'Merchant',
                'password' => Hash::make('password123'), // demo password
            ]
        );

        $merchant2 = User::firstOrCreate(
            ['email' => 'merchant2@example.com'],
            [
                'name' => 'Merchant Two',
                'role' => 'Merchant',
                'password' => Hash::make('password123'),
            ]
        );

        // Assigned shops (no shop_password needed when MerchantId is set)
        Shop::updateOrCreate(
            ['shopNumber' => 'S-101'],
            [
                'MerchantId'   => $merchant1->id,
                'leaseEnd'     => Carbon::now()->addYear()->toDateString(),
                'rentalAmount' => 25000,
                'shop_password'=> null,
            ]
        );

        Shop::updateOrCreate(
            ['shopNumber' => 'S-102'],
            [
                'MerchantId'   => $merchant2->id,
                'leaseEnd'     => Carbon::now()->addMonths(18)->toDateString(),
                'rentalAmount' => 32000,
                'shop_password'=> null,
            ]
        );

        // Unassigned shops (set a shop_password so they can log in by Shop No)
        Shop::updateOrCreate(
            ['shopNumber' => 'S-201'],
            [
                'MerchantId'   => null,
                'leaseEnd'     => Carbon::now()->addMonths(9)->toDateString(),
                'rentalAmount' => 18000,
                'shop_password'=> Hash::make('shop201pass'),
            ]
        );

        Shop::updateOrCreate(
            ['shopNumber' => 'S-202'],
            [
                'MerchantId'   => null,
                'leaseEnd'     => Carbon::now()->addMonths(6)->toDateString(),
                'rentalAmount' => 20000,
                'shop_password'=> Hash::make('shop202pass'),
            ]
        );
    }
}

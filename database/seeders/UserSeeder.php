<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'role' => 'Admin',
                'address' => 'HQ',
                'NIC' => '000000000V',
                'email_verified_at' => now(),
                'password' => Hash::make('password'), // change in production
            ]
        );

        // Houseowner
        User::updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Demo Houseowner',
                'role' => 'Houseowner',
                'address' => 'Lot 12',
                'NIC' => '111111111V',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // Merchant
        User::updateOrCreate(
            ['email' => 'merchant@example.com'],
            [
                'name' => 'Demo Merchant',
                'role' => 'Merchant',
                'address' => 'Shop A1',
                'NIC' => '222222222V',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // Employee
        User::updateOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Demo Employee',
                'role' => 'Employee',
                'address' => 'Maintenance Office',
                'NIC' => '333333333V',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
    }
}

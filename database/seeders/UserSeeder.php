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
                'password' => Hash::make('password'), 
            ]
        );

    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RegularUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Regular User',
            'email' => 'user@falai.com',
            'password' => Hash::make('user123'),
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);
    }
} 
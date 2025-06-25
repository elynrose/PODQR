<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class VerifyAdminUser extends Command
{
    protected $signature = 'admin:verify';
    protected $description = 'Verify and fix admin user privileges';

    public function handle()
    {
        $admin = User::where('email', 'admin@falai.com')->first();

        if (!$admin) {
            $this->info('Admin user not found. Creating...');
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@falai.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]);
            $this->info('Admin user created successfully.');
        } else {
            $this->info('Admin user found. Verifying privileges...');
            if (!$admin->is_admin) {
                $admin->is_admin = true;
                $admin->save();
                $this->info('Admin privileges restored.');
            } else {
                $this->info('Admin privileges are correct.');
            }
        }

        // Verify the user can actually perform admin actions
        if ($admin->isAdmin()) {
            $this->info('Admin privileges verified successfully.');
        } else {
            $this->error('Admin privileges verification failed!');
        }
    }
} 
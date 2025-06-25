<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class AddUsernamesToUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNull('username')->get();
        
        foreach ($users as $user) {
            // Generate a username based on the user's name
            $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
            
            // If the base username is empty, use 'user' + id
            if (empty($baseUsername)) {
                $baseUsername = 'user' . $user->id;
            }
            
            // Check if username exists, if so add a number
            $username = $baseUsername;
            $counter = 1;
            
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            
            $user->update(['username' => $username]);
        }
    }
}

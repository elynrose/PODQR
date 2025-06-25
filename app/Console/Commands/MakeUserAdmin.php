<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user an admin by their email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("No user found with email: {$email}");
            return Command::FAILURE;
        }
        
        $user->is_admin = true;
        $user->save();
        
        $this->info("User {$email} is now an admin!");
        
        return Command::SUCCESS;
    }
}

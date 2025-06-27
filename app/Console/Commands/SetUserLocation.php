<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SetUserLocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-location {email} {country_code} {--state=} {--city=} {--zip=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set user location for better product filtering';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $countryCode = $this->argument('country_code');
        $state = $this->option('state');
        $city = $this->option('city');
        $zip = $this->option('zip');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $user->update([
            'country_code' => $countryCode,
            'state_code' => $state,
            'city' => $city,
            'zip_code' => $zip,
        ]);

        $this->info("Location updated for user {$email}:");
        $this->line("Country: {$countryCode}");
        if ($state) $this->line("State: {$state}");
        if ($city) $this->line("City: {$city}");
        if ($zip) $this->line("ZIP: {$zip}");

        return 0;
    }
} 
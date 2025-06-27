<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Design;
use App\Models\User;

class SeedTestDesigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:test-designs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed test designs for cloud deployment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding test designs...');

        // Get or create a test user
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return;
        }

        // Get first clothes type
        $clothesType = \App\Models\ClothesType::first();
        if (!$clothesType) {
            $this->error('No clothes types found. Please run clothes type seeder first.');
            return;
        }

        // Get first shirt size
        $shirtSize = \App\Models\ShirtSize::first();
        if (!$shirtSize) {
            $this->error('No shirt sizes found. Please run shirt size seeder first.');
            return;
        }

        $designs = [
            [
                'name' => 'Test Design 1',
                'description' => 'A test design for ordering',
                'user_id' => $user->id,
                'clothes_type_id' => $clothesType->id,
                'shirt_size_id' => $shirtSize->id,
                'color_code' => '#ff0000',
                'status' => 'published',
                'is_public' => true,
            ],
            [
                'name' => 'Test Design 2',
                'description' => 'Another test design for ordering',
                'user_id' => $user->id,
                'clothes_type_id' => $clothesType->id,
                'shirt_size_id' => $shirtSize->id,
                'color_code' => '#0000ff',
                'status' => 'published',
                'is_public' => true,
            ],
            [
                'name' => 'Test Design 3',
                'description' => 'Third test design for ordering',
                'user_id' => $user->id,
                'clothes_type_id' => $clothesType->id,
                'shirt_size_id' => $shirtSize->id,
                'color_code' => '#00ff00',
                'status' => 'published',
                'is_public' => true,
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($designs as $index => $designData) {
            $designId = $index + 1; // Start from ID 1
            
            $existing = Design::find($designId);
            
            if ($existing) {
                $existing->update($designData);
                $updated++;
                $this->line("Updated: {$designData['name']} (ID: {$designId})");
            } else {
                $designData['id'] = $designId;
                Design::create($designData);
                $created++;
                $this->line("Created: {$designData['name']} (ID: {$designId})");
            }
        }

        $this->info("Seeding completed! Created: {$created}, Updated: {$updated}");
        
        $totalDesigns = Design::count();
        $this->info("Total designs: {$totalDesigns}");
        
        // Show available designs
        $this->info("Available designs:");
        Design::all(['id', 'name'])->each(function($design) {
            $this->line("- ID: {$design->id}, Name: {$design->name}");
        });
    }
} 
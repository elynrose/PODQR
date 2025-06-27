<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder is now empty since we're using real Printful data
        // The products are synced from Printful API
        $this->command->info('ProductSeeder: Using real Printful data instead of seeded products.');
    }
}

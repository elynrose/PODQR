<?php

namespace Database\Seeders;

use App\Models\ShirtSize;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShirtSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = [
            // Men's Sizes
            ['name' => 'XS', 'description' => 'Extra Small - Men', 'sort_order' => 1],
            ['name' => 'S', 'description' => 'Small - Men', 'sort_order' => 2],
            ['name' => 'M', 'description' => 'Medium - Men', 'sort_order' => 3],
            ['name' => 'L', 'description' => 'Large - Men', 'sort_order' => 4],
            ['name' => 'XL', 'description' => 'Extra Large - Men', 'sort_order' => 5],
            ['name' => 'XXL', 'description' => '2X Large - Men', 'sort_order' => 6],
            ['name' => '3XL', 'description' => '3X Large - Men', 'sort_order' => 7],
            
            // Women's Sizes
            ['name' => 'XS-W', 'description' => 'Extra Small - Women', 'sort_order' => 8],
            ['name' => 'S-W', 'description' => 'Small - Women', 'sort_order' => 9],
            ['name' => 'M-W', 'description' => 'Medium - Women', 'sort_order' => 10],
            ['name' => 'L-W', 'description' => 'Large - Women', 'sort_order' => 11],
            ['name' => 'XL-W', 'description' => 'Extra Large - Women', 'sort_order' => 12],
            ['name' => 'XXL-W', 'description' => '2X Large - Women', 'sort_order' => 13],
            
            // Unisex Sizes
            ['name' => 'XS-U', 'description' => 'Extra Small - Unisex', 'sort_order' => 14],
            ['name' => 'S-U', 'description' => 'Small - Unisex', 'sort_order' => 15],
            ['name' => 'M-U', 'description' => 'Medium - Unisex', 'sort_order' => 16],
            ['name' => 'L-U', 'description' => 'Large - Unisex', 'sort_order' => 17],
            ['name' => 'XL-U', 'description' => 'Extra Large - Unisex', 'sort_order' => 18],
            ['name' => 'XXL-U', 'description' => '2X Large - Unisex', 'sort_order' => 19],
            
            // Kids Sizes
            ['name' => '2T', 'description' => 'Toddler 2T', 'sort_order' => 20],
            ['name' => '3T', 'description' => 'Toddler 3T', 'sort_order' => 21],
            ['name' => '4T', 'description' => 'Toddler 4T', 'sort_order' => 22],
            ['name' => '5T', 'description' => 'Toddler 5T', 'sort_order' => 23],
            ['name' => 'YS', 'description' => 'Youth Small', 'sort_order' => 24],
            ['name' => 'YM', 'description' => 'Youth Medium', 'sort_order' => 25],
            ['name' => 'YL', 'description' => 'Youth Large', 'sort_order' => 26],
            ['name' => 'YXL', 'description' => 'Youth Extra Large', 'sort_order' => 27],
        ];

        foreach ($sizes as $size) {
            ShirtSize::create($size);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClothesCategory;

class ClothesCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'T-Shirts',
                'description' => 'Classic t-shirts for men, women, and children',
                'is_active' => true,
            ],
            [
                'name' => 'Hoodies',
                'description' => 'Comfortable hooded sweatshirts for all ages',
                'is_active' => true,
            ],
            [
                'name' => 'Tank Tops',
                'description' => 'Sleeveless tops perfect for summer',
                'is_active' => true,
            ],
            [
                'name' => 'Long Sleeve Shirts',
                'description' => 'Long sleeve t-shirts and casual shirts',
                'is_active' => true,
            ],
            [
                'name' => 'Polo Shirts',
                'description' => 'Collared polo shirts for a more formal look',
                'is_active' => true,
            ],
            [
                'name' => 'Sweatshirts',
                'description' => 'Comfortable crew neck sweatshirts',
                'is_active' => true,
            ],
            [
                'name' => 'V-Neck Shirts',
                'description' => 'V-neck t-shirts for a stylish look',
                'is_active' => true,
            ],
            [
                'name' => 'Kids Clothing',
                'description' => 'Clothing items specifically for children',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ClothesCategory::create($category);
        }

        $this->command->info('Clothes categories seeded successfully!');
    }
}

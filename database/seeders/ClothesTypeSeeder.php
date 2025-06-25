<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClothesType;
use App\Models\ClothesCategory;

class ClothesTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $tShirtsCategory = ClothesCategory::where('name', 'T-Shirts')->first();
        $hoodiesCategory = ClothesCategory::where('name', 'Hoodies')->first();
        $tankTopsCategory = ClothesCategory::where('name', 'Tank Tops')->first();
        $longSleeveCategory = ClothesCategory::where('name', 'Long Sleeve Shirts')->first();
        $poloCategory = ClothesCategory::where('name', 'Polo Shirts')->first();
        $sweatshirtsCategory = ClothesCategory::where('name', 'Sweatshirts')->first();
        $vNeckCategory = ClothesCategory::where('name', 'V-Neck Shirts')->first();
        $kidsCategory = ClothesCategory::where('name', 'Kids Clothing')->first();

        $clothesTypes = [
            // T-Shirts
            [
                'name' => 'Classic Cotton T-Shirt',
                'description' => 'Premium cotton t-shirt with a comfortable fit',
                'category_id' => $tShirtsCategory->id,
                'colors' => ['White', 'Black', 'Navy', 'Gray', 'Red'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Premium T-Shirt',
                'description' => 'High-quality t-shirt with enhanced durability',
                'category_id' => $tShirtsCategory->id,
                'colors' => ['White', 'Black', 'Charcoal', 'Burgundy'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            
            // Hoodies
            [
                'name' => 'Classic Pullover Hoodie',
                'description' => 'Comfortable pullover hoodie with kangaroo pocket',
                'category_id' => $hoodiesCategory->id,
                'colors' => ['Black', 'Gray', 'Navy', 'Burgundy'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Zip-Up Hoodie',
                'description' => 'Modern zip-up hoodie with adjustable hood',
                'category_id' => $hoodiesCategory->id,
                'colors' => ['Black', 'Gray', 'Navy', 'Olive'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            
            // Tank Tops
            [
                'name' => 'Classic Tank Top',
                'description' => 'Comfortable tank top perfect for summer',
                'category_id' => $tankTopsCategory->id,
                'colors' => ['White', 'Black', 'Gray', 'Navy'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            
            // Long Sleeve Shirts
            [
                'name' => 'Long Sleeve T-Shirt',
                'description' => 'Comfortable long sleeve t-shirt for cooler weather',
                'category_id' => $longSleeveCategory->id,
                'colors' => ['White', 'Black', 'Gray', 'Navy', 'Burgundy'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            
            // Polo Shirts
            [
                'name' => 'Classic Polo Shirt',
                'description' => 'Professional polo shirt with collar',
                'category_id' => $poloCategory->id,
                'colors' => ['White', 'Black', 'Navy', 'Burgundy', 'Forest Green'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            
            // Sweatshirts
            [
                'name' => 'Crew Neck Sweatshirt',
                'description' => 'Comfortable crew neck sweatshirt',
                'category_id' => $sweatshirtsCategory->id,
                'colors' => ['Black', 'Gray', 'Navy', 'Burgundy'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            
            // V-Neck Shirts
            [
                'name' => 'Classic V-Neck T-Shirt',
                'description' => 'Stylish v-neck t-shirt for a modern look',
                'category_id' => $vNeckCategory->id,
                'colors' => ['White', 'Black', 'Gray', 'Navy'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            
            // Kids Clothing
            [
                'name' => 'Kids T-Shirt',
                'description' => 'Comfortable t-shirt designed for children',
                'category_id' => $kidsCategory->id,
                'colors' => ['White', 'Black', 'Blue', 'Red', 'Green'],
                'is_active' => true,
                'sort_order' => 1,
            ],
        ];

        foreach ($clothesTypes as $clothesType) {
            ClothesType::create($clothesType);
        }

        $this->command->info('Clothes types seeded successfully!');
    }
}

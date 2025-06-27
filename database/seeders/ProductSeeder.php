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
        $products = [
            [
                'printful_id' => '1',
                'name' => 'Unisex T-Shirt',
                'description' => 'Classic unisex t-shirt made from 100% cotton',
                'type' => 't-shirt',
                'brand' => 'Gildan',
                'model' => '5000',
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'],
                'colors' => ['White', 'Black', 'Navy', 'Gray', 'Red', 'Blue'],
                'base_price' => 15.99,
                'image_url' => 'https://via.placeholder.com/300x400/cccccc/666666?text=T-Shirt',
                'is_active' => true,
            ],
            [
                'printful_id' => '2',
                'name' => 'Premium T-Shirt',
                'description' => 'Premium quality t-shirt with enhanced comfort',
                'type' => 't-shirt',
                'brand' => 'Bella+Canvas',
                'model' => '3001',
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'colors' => ['White', 'Black', 'Navy', 'Gray', 'Heather Gray'],
                'base_price' => 19.99,
                'image_url' => 'https://via.placeholder.com/300x400/cccccc/666666?text=Premium+T-Shirt',
                'is_active' => true,
            ],
            [
                'printful_id' => '3',
                'name' => 'Hooded Sweatshirt',
                'description' => 'Comfortable hooded sweatshirt perfect for cooler weather',
                'type' => 'hoodie',
                'brand' => 'Gildan',
                'model' => '18500',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL', '3XL'],
                'colors' => ['Black', 'Navy', 'Gray', 'Charcoal', 'Red'],
                'base_price' => 29.99,
                'image_url' => 'https://via.placeholder.com/300x400/cccccc/666666?text=Hoodie',
                'is_active' => true,
            ],
            [
                'printful_id' => '4',
                'name' => 'Long Sleeve T-Shirt',
                'description' => 'Long sleeve t-shirt for added coverage and style',
                'type' => 'long-sleeve',
                'brand' => 'Gildan',
                'model' => '2400',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => ['White', 'Black', 'Navy', 'Gray'],
                'base_price' => 18.99,
                'image_url' => 'https://via.placeholder.com/300x400/cccccc/666666?text=Long+Sleeve',
                'is_active' => true,
            ],
            [
                'printful_id' => '5',
                'name' => 'Tank Top',
                'description' => 'Sleeveless tank top for warm weather',
                'type' => 'tank-top',
                'brand' => 'Gildan',
                'model' => '2200',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => ['White', 'Black', 'Gray', 'Navy'],
                'base_price' => 12.99,
                'image_url' => 'https://via.placeholder.com/300x400/cccccc/666666?text=Tank+Top',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['printful_id' => $product['printful_id']],
                $product
            );
        }
    }
}

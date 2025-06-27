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
                'printful_id' => '17054',
                'printful_product_id' => 679,
                'name' => 'Unisex Performance Crew Neck T-Shirt | A4 N3142',
                'description' => 'Stay cool, dry, and confident even during the most intense activities in this unisex performance crew neck t-shirt.',
                'type' => 'T-shirt',
                'brand' => 'A4',
                'model' => 'N3142',
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Navy', 'color_codes' => ['#000080']]
                ],
                'base_price' => 17.29,
                'image_url' => 'https://files.cdn.printful.com/o/upload/product-catalog-img/e2/e239b78e54f77c63f3ec3bcda3be8e62_l',
                'is_active' => true,
            ],
            [
                'printful_id' => '17055',
                'printful_product_id' => 679,
                'name' => 'Classic Cotton T-Shirt | Gildan 5000',
                'description' => 'Classic unisex t-shirt made from 100% cotton for everyday comfort.',
                'type' => 'T-shirt',
                'brand' => 'Gildan',
                'model' => '5000',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL', '3XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Gray', 'color_codes' => ['#808080']],
                    ['color_name' => 'Navy', 'color_codes' => ['#000080']]
                ],
                'base_price' => 15.99,
                'image_url' => 'https://files.cdn.printful.com/o/upload/product-catalog-img/e2/e239b78e54f77c63f3ec3bcda3be8e62_l',
                'is_active' => true,
            ],
            [
                'printful_id' => '17056',
                'printful_product_id' => 679,
                'name' => 'Premium T-Shirt | Bella+Canvas 3001',
                'description' => 'Premium quality t-shirt with enhanced comfort and softness.',
                'type' => 'T-shirt',
                'brand' => 'Bella+Canvas',
                'model' => '3001',
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Heather Gray', 'color_codes' => ['#9b9b9b']],
                    ['color_name' => 'Navy', 'color_codes' => ['#000080']]
                ],
                'base_price' => 19.99,
                'image_url' => 'https://files.cdn.printful.com/o/upload/product-catalog-img/e2/e239b78e54f77c63f3ec3bcda3be8e62_l',
                'is_active' => true,
            ],
            [
                'printful_id' => '17057',
                'printful_product_id' => 679,
                'name' => 'V-Neck T-Shirt | Gildan 64000',
                'description' => 'Classic v-neck t-shirt for a more sophisticated look.',
                'type' => 'T-shirt',
                'brand' => 'Gildan',
                'model' => '64000',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Gray', 'color_codes' => ['#808080']]
                ],
                'base_price' => 16.99,
                'image_url' => 'https://files.cdn.printful.com/o/upload/product-catalog-img/e2/e239b78e54f77c63f3ec3bcda3be8e62_l',
                'is_active' => true,
            ],
            [
                'printful_id' => '17058',
                'printful_product_id' => 679,
                'name' => 'Long Sleeve T-Shirt | Gildan 2400',
                'description' => 'Long sleeve t-shirt for added coverage and style.',
                'type' => 'T-shirt',
                'brand' => 'Gildan',
                'model' => '2400',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Navy', 'color_codes' => ['#000080']],
                    ['color_name' => 'Gray', 'color_codes' => ['#808080']]
                ],
                'base_price' => 18.99,
                'image_url' => 'https://files.cdn.printful.com/o/upload/product-catalog-img/e2/e239b78e54f77c63f3ec3bcda3be8e62_l',
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

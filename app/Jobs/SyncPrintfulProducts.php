<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\PrintfulService;
use App\Models\Product;
use App\Models\ClothesCategory;
use App\Models\ClothesType;

class SyncPrintfulProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(PrintfulService $printfulService): void
    {
        Log::info('Starting Printful products sync...');

        try {
            // Get all products from Printful
            $printfulProducts = $printfulService->getProducts();
            
            if (!$printfulProducts) {
                Log::error('Failed to fetch products from Printful');
                return;
            }

            Log::info('Found ' . count($printfulProducts) . ' products in Printful');

            $syncedCount = 0;
            $updatedCount = 0;
            $createdCount = 0;

            foreach ($printfulProducts as $printfulProduct) {
                try {
                    // Get product variants
                    $variants = $printfulService->getProductVariants($printfulProduct['id']);
                    
                    if (!$variants) {
                        Log::warning('No variants found for product ' . $printfulProduct['id']);
                        continue;
                    }

                    // Process each variant as a separate product
                    foreach ($variants as $variant) {
                        $result = $this->syncProductVariant($printfulProduct, $variant);
                        
                        if ($result === 'created') {
                            $createdCount++;
                        } elseif ($result === 'updated') {
                            $updatedCount++;
                        }
                        
                        $syncedCount++;
                    }

                } catch (\Exception $e) {
                    Log::error('Error syncing product ' . $printfulProduct['id'] . ': ' . $e->getMessage());
                    continue;
                }
            }

            Log::info("Printful sync completed: {$syncedCount} variants processed, {$createdCount} created, {$updatedCount} updated");

        } catch (\Exception $e) {
            Log::error('Printful sync job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync a single product variant
     */
    private function syncProductVariant(array $printfulProduct, array $variant): string
    {
        // Determine category and type based on product type
        $category = $this->determineCategory($printfulProduct['type']);
        $type = $this->determineType($printfulProduct['type']);

        // Create or update category
        $categoryModel = ClothesCategory::firstOrCreate(
            ['name' => $category],
            ['description' => $category . ' category']
        );

        // Create or update type
        $typeModel = ClothesType::firstOrCreate(
            ['name' => $type],
            ['category_id' => $categoryModel->id, 'description' => $type . ' type']
        );

        // Prepare product data
        $productData = [
            'printful_id' => $variant['id'],
            'printful_product_id' => $printfulProduct['id'],
            'name' => $variant['name'] ?? $printfulProduct['title'],
            'description' => $printfulProduct['description'] ?? '',
            'type' => $printfulProduct['type'] ?? 'unknown',
            'brand' => $printfulProduct['brand'] ?? 'Unknown',
            'model' => $printfulProduct['model'] ?? 'Unknown',
            'category_id' => $categoryModel->id,
            'clothes_type_id' => $typeModel->id,
            'sizes' => isset($variant['size']) ? [$variant['size']] : [],
            'colors' => isset($variant['color']) ? [$variant['color']] : [],
            'base_price' => $variant['price'] ?? 0,
            'image_url' => $variant['image'] ?? $printfulProduct['image'] ?? null,
            'is_active' => $variant['in_stock'] ?? true,
            'metadata' => [
                'printful_product' => $printfulProduct,
                'printful_variant' => $variant,
                'last_synced' => now()->toISOString(),
            ],
        ];

        // Find existing product by printful_id
        $existingProduct = Product::where('printful_id', $variant['id'])->first();

        if ($existingProduct) {
            // Update existing product
            $existingProduct->update($productData);
            return 'updated';
        } else {
            // Create new product
            Product::create($productData);
            return 'created';
        }
    }

    /**
     * Determine category based on Printful product type
     */
    private function determineCategory(string $type): string
    {
        $categoryMap = [
            'DTFILM' => 'T-Shirts',
            'EMBROIDERY' => 'T-Shirts',
            'DTG' => 'T-Shirts',
            'SUBLIMATION' => 'T-Shirts',
            'SWEATSHIRT' => 'Hoodies',
            'HOODIE' => 'Hoodies',
            'TANK_TOP' => 'Tank Tops',
            'LONG_SLEEVE' => 'Long Sleeve',
            'PULLOVER' => 'Hoodies',
            'ZIPPER' => 'Hoodies',
        ];

        return $categoryMap[$type] ?? 'Other';
    }

    /**
     * Determine type based on Printful product type
     */
    private function determineType(string $type): string
    {
        $typeMap = [
            'DTFILM' => 'T-Shirt',
            'EMBROIDERY' => 'T-Shirt',
            'DTG' => 'T-Shirt',
            'SUBLIMATION' => 'T-Shirt',
            'SWEATSHIRT' => 'Sweatshirt',
            'HOODIE' => 'Hoodie',
            'TANK_TOP' => 'Tank Top',
            'LONG_SLEEVE' => 'Long Sleeve T-Shirt',
            'PULLOVER' => 'Pullover',
            'ZIPPER' => 'Zipper Hoodie',
        ];

        return $typeMap[$type] ?? 'Unknown';
    }
}

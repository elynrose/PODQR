<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PrintfulService;

class FindValidProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printful:find-products {--limit=10 : Number of products to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find valid Printful product IDs with their variant IDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $printfulService = new PrintfulService();
        $limit = $this->option('limit');
        
        $this->info("🔍 Finding valid Printful products with variant IDs (limit: {$limit})");
        $this->newLine();
        
        try {
            // Get products from Printful API
            $products = $printfulService->getTshirtProducts($limit);
            
            if ($products->isEmpty()) {
                $this->error('No products found!');
                return;
            }
            
            $this->info('Found ' . $products->count() . ' products:');
            $this->newLine();
            
            foreach ($products as $product) {
                $this->line("📦 Product: {$product['name']}");
                $this->line("   Product ID: {$product['printful_id']}");
                $this->line("   Variant ID: {$product['variant_id']}");
                $this->line("   Price: $" . $product['base_price']);
                $this->line("   Sizes: " . implode(', ', $product['sizes']));
                $this->line("   Colors: " . count($product['colors']));
                $this->newLine();
            }
            
            // Test the variant IDs
            $this->info('Testing variant IDs...');
            $this->newLine();
            
            foreach ($products as $product) {
                $variantId = $product['variant_id'];
                $validation = $printfulService->validateVariantId($variantId);
                
                $status = $validation['exists'] ? '✅ VALID' : '❌ INVALID';
                $this->line("Variant {$variantId}: {$status} - {$validation['message']}");
            }
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
} 
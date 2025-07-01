<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PrintfulService;

class TestVariantValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printful:test-variants {--variant-id= : Specific variant ID to test} {--batch : Test multiple variant IDs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Printful variant ID validation system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $printfulService = new PrintfulService();
        
        $this->info('🔍 Testing Printful Variant ID Validation System');
        $this->newLine();
        
        // Test specific variant ID if provided
        if ($variantId = $this->option('variant-id')) {
            $this->testSingleVariant($printfulService, $variantId);
            return;
        }
        
        // Test batch validation if requested
        if ($this->option('batch')) {
            $this->testBatchValidation($printfulService);
            return;
        }
        
        // Default: test known good and bad variant IDs
        $this->testKnownVariants($printfulService);
    }
    
    private function testSingleVariant(PrintfulService $printfulService, $variantId)
    {
        $this->info("Testing variant ID: {$variantId}");
        $this->newLine();
        
        $validation = $printfulService->validateVariantId($variantId);
        
        if ($validation['exists']) {
            $this->info('✅ Variant is VALID');
            $this->line("   Message: {$validation['message']}");
            $this->line("   Variant Name: {$validation['variant_name']}");
            
            if (isset($validation['variant_data'])) {
                $this->line("   Retail Price: $" . ($validation['variant_data']['retail_price'] ?? 'N/A'));
                $this->line("   Size: " . ($validation['variant_data']['size'] ?? 'N/A'));
                $this->line("   Color: " . ($validation['variant_data']['color'] ?? 'N/A'));
            }
        } else {
            $this->error('❌ Variant is INVALID');
            $this->line("   Message: {$validation['message']}");
        }
    }
    
    private function testBatchValidation(PrintfulService $printfulService)
    {
        $this->info('Testing batch variant validation...');
        $this->newLine();
        
        // Test a mix of known good and bad variant IDs
        $testVariantIds = [
            '4012', // Known good unisex t-shirt variant
            '4013', // Known good unisex t-shirt variant
            '4014', // Known good unisex t-shirt variant
            '999999', // Likely invalid
            'abc123', // Invalid format
            '4015', // Another known good variant
        ];
        
        $this->info('Testing variant IDs: ' . implode(', ', $testVariantIds));
        $this->newLine();
        
        $results = $printfulService->validateVariantIds($testVariantIds);
        
        foreach ($results as $variantId => $validation) {
            $status = $validation['exists'] ? '✅ VALID' : '❌ INVALID';
            $this->line("{$variantId}: {$status} - {$validation['message']}");
        }
        
        $this->newLine();
        
        // Test getting only valid IDs
        $validIds = $printfulService->getValidVariantIds($testVariantIds);
        $this->info('Valid variant IDs: ' . implode(', ', $validIds));
    }
    
    private function testKnownVariants(PrintfulService $printfulService)
    {
        $this->info('Testing known variant IDs...');
        $this->newLine();
        
        // Test the fallback variant IDs we use
        $fallbackVariants = ['4012', '4013', '4014', '4015', '4016'];
        
        foreach ($fallbackVariants as $variantId) {
            $this->testSingleVariant($printfulService, $variantId);
            $this->newLine();
        }
        
        // Test some invalid IDs
        $this->info('Testing invalid variant IDs...');
        $this->newLine();
        
        $invalidVariants = ['999999', 'abc123', '0', ''];
        
        foreach ($invalidVariants as $variantId) {
            $this->testSingleVariant($printfulService, $variantId);
            $this->newLine();
        }
    }
} 
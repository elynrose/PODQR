<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerifyPrintfulStore extends Command
{
    protected $signature = 'printful:verify-store';
    protected $description = 'Verify Printful store configuration and fetch store details';

    public function handle()
    {
        $this->info('Verifying Printful store configuration...');
        
        $apiKey = config('services.printful.api_key');
        $storeId = config('services.printful.store_id');
        
        if (!$apiKey) {
            $this->error('Printful API key not configured!');
            return 1;
        }
        
        if (!$storeId) {
            $this->error('Printful store ID not configured!');
            return 1;
        }
        
        $this->info("API Key: " . substr($apiKey, 0, 10) . "...");
        $this->info("Store ID: {$storeId}");
        
        // Test API connection
        $this->info("\nTesting API connection...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.printful.com/stores');
            
            if ($response->successful()) {
                $stores = $response->json()['result'];
                $this->info("✅ API connection successful!");
                $this->info("Found " . count($stores) . " stores:");
                
                foreach ($stores as $store) {
                    $this->info("- Store ID: {$store['id']} | Name: {$store['name']} | Type: {$store['type']}");
                    
                    if ($store['id'] == $storeId) {
                        $this->info("  ✅ This is your configured store!");
                    }
                }
                
                // Get specific store details
                $this->info("\nFetching details for configured store ID: {$storeId}");
                $storeResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get("https://api.printful.com/stores/{$storeId}");
                
                if ($storeResponse->successful()) {
                    $storeDetails = $storeResponse->json()['result'];
                    $this->info("✅ Store found!");
                    $this->info("Store Name: {$storeDetails['name']}");
                    $this->info("Store Type: {$storeDetails['type']}");
                    $this->info("Store URL: {$storeDetails['url']}");
                    $this->info("Currency: {$storeDetails['currency']}");
                    $this->info("Status: {$storeDetails['status']}");
                } else {
                    $this->error("❌ Store with ID {$storeId} not found!");
                    $this->error("Response: " . $storeResponse->body());
                }
                
            } else {
                $this->error("❌ API connection failed!");
                $this->error("Status: " . $response->status());
                $this->error("Response: " . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        return 0;
    }
} 
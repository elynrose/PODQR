<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckPrintfulOrders extends Command
{
    protected $signature = 'printful:check-orders';
    protected $description = 'Check recent orders in Printful';

    public function handle()
    {
        $this->info('Checking recent Printful orders...');
        
        $apiKey = config('services.printful.api_key');
        $storeId = config('services.printful.store_id');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.printful.com/orders?store_id={$storeId}&limit=10");
            
            if ($response->successful()) {
                $result = $response->json();
                $orders = $result['result'] ?? [];
                
                $this->info("✅ Found " . count($orders) . " recent orders:");
                
                foreach ($orders as $order) {
                    $this->info("- Order ID: {$order['id']} | Status: {$order['status']} | Created: {$order['created']}");
                    if (isset($order['external_id'])) {
                        $this->info("  External ID: {$order['external_id']}");
                    }
                }
                
                if (empty($orders)) {
                    $this->warn("No orders found in Printful for store {$storeId}");
                }
                
            } else {
                $this->error("❌ Failed to fetch orders!");
                $this->error("Status: " . $response->status());
                $this->error("Response: " . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
        }
        
        return 0;
    }
} 
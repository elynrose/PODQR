<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Design;
use App\Services\PrintfulService;

class TestProductionImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printful:test-production {design_id} {--variant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Printful image upload in production environment';

    protected $printfulService;

    public function __construct(PrintfulService $printfulService)
    {
        parent::__construct();
        $this->printfulService = $printfulService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $designId = $this->argument('design_id');
        $variantId = $this->option('variant_id') ?: '22328'; // Default to a T-shirt variant

        $design = Design::find($designId);
        if (!$design) {
            $this->error("Design with ID {$designId} not found.");
            return 1;
        }

        $this->info("Testing Printful image upload for design: {$design->name}");
        $this->line("Design ID: {$design->id}");
        $this->line("Front image: {$design->front_image_path}");
        $this->line("Back image: {$design->back_image_path}");

        // Check if we're in production
        $appUrl = config('app.url');
        $this->line("App URL: {$appUrl}");
        
        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            $this->error("❌ This command should be run in production environment (Laravel Cloud)");
            $this->line("Current environment appears to be local development.");
            return 1;
        }

        $this->info("✅ Production environment detected");

        // Test the actual design images
        $testUrls = [];
        
        if ($design->front_image_path) {
            $frontUrl = asset('storage/' . $design->front_image_path);
            $testUrls['front_image'] = $frontUrl;
            $this->line("Front image URL: {$frontUrl}");
        }
        
        if ($design->back_image_path) {
            $backUrl = asset('storage/' . $design->back_image_path);
            $testUrls['back_image'] = $backUrl;
            $this->line("Back image URL: {$backUrl}");
        }

        if (empty($testUrls)) {
            $this->error("No design images found to test");
            return 1;
        }

        foreach ($testUrls as $type => $url) {
            $this->line("\nTesting {$type}: {$url}");

            try {
                // Create a test order with this image
                $testPayload = [
                    'shipping_address' => [
                        'name' => 'Test User',
                        'address1' => '123 Test St',
                        'city' => 'Test City',
                        'state_code' => 'CA',
                        'country_code' => 'US',
                        'zip' => '12345',
                        'email' => 'test@example.com',
                        'phone' => '1234567890'
                    ],
                    'items' => [
                        [
                            'variant_id' => $variantId,
                            'quantity' => 1,
                            'files' => [
                                [
                                    'url' => $url,
                                    'type' => 'front'
                                ]
                            ],
                            'options' => [
                                'size' => 'M',
                                'color' => 'Black'
                            ]
                        ]
                    ],
                    'subtotal' => 10.29,
                    'shipping' => 5.99,
                    'tax' => 0.82,
                    'total' => 17.10,
                    'currency' => 'USD'
                ];

                $this->line("Sending test order to Printful...");
                $response = $this->printfulService->createOrder($testPayload);

                if ($response && isset($response['id'])) {
                    $this->info("✅ SUCCESS: {$type} worked! Order ID: {$response['id']}");
                    
                    // Check if the image was accepted
                    if (isset($response['items'][0]['files'][0]['status'])) {
                        $status = $response['items'][0]['files'][0]['status'];
                        $this->line("Image status: {$status}");
                        
                        if ($status === 'waiting') {
                            $this->info("Image accepted and waiting for processing");
                        } elseif ($status === 'ok') {
                            $this->info("Image processed successfully");
                        } elseif ($status === 'failed') {
                            $this->error("Image processing failed");
                        } else {
                            $this->warn("Image status: {$status}");
                        }
                    }
                    
                    $this->line("Dashboard URL: https://www.printful.com/dashboard?order_id={$response['id']}");
                } else {
                    $this->error("❌ FAILED: {$type} failed");
                    if (isset($response['error'])) {
                        $this->error("Error: " . $response['error']);
                    }
                }

            } catch (\Exception $e) {
                $this->error("❌ ERROR: {$type} - " . $e->getMessage());
            }
        }

        return 0;
    }
} 
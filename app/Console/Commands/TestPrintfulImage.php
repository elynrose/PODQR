<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Design;
use App\Models\Product;
use App\Services\PrintfulService;
use Illuminate\Support\Facades\Storage;

class TestPrintfulImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printful:test-image {design_id} {--variant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Printful image upload with a specific design';

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
        
        // Check if we're in a local environment
        $frontUrl = $design->front_image_path ? Storage::url($design->front_image_path) : null;
        $backUrl = $design->back_image_path ? Storage::url($design->back_image_path) : null;
        
        if (str_contains($frontUrl, 'localhost') || str_contains($backUrl, 'localhost')) {
            $this->warn("⚠️  Localhost URLs detected. These will be skipped as Printful requires public URLs.");
            $this->line("   Deploy to Laravel Cloud or use a public hosting service for production testing.");
        }

        // Test different image URLs
        $testUrls = [
            'placeholder' => 'https://via.placeholder.com/500x500/000000/FFFFFF?text=Test+Design',
        ];
        
        // Only add local images if they're accessible via public URL
        if ($design->front_image_path) {
            $frontUrl = Storage::url($design->front_image_path);
            if (!str_contains($frontUrl, 'localhost') && !str_contains($frontUrl, '127.0.0.1')) {
                $testUrls['front_image'] = $frontUrl;
            }
        }
        
        if ($design->back_image_path) {
            $backUrl = Storage::url($design->back_image_path);
            if (!str_contains($backUrl, 'localhost') && !str_contains($backUrl, '127.0.0.1')) {
                $testUrls['back_image'] = $backUrl;
            }
        }

        foreach ($testUrls as $type => $url) {
            if (!$url) {
                $this->warn("Skipping {$type} - no URL available");
                continue;
            }

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
                        } else {
                            $this->warn("Image status: {$status}");
                        }
                    }
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
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Design;
use App\Models\User;
use App\Services\PrintfulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestPrintfulOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:printful-order {--order-id= : Specific order ID to test} {--user-id=1 : User ID to use for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending an order to Printful without requiring payment';

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
        $orderId = $this->option('order-id');
        $userId = $this->option('user-id');

        if ($orderId) {
            // Test with existing order
            $order = Order::where('id', $orderId)->where('user_id', $userId)->first();
            
            if (!$order) {
                $this->error("Order #{$orderId} not found for user #{$userId}");
                return 1;
            }

            $this->info("Testing Printful order creation for existing order #{$order->order_number}");
            $this->testExistingOrder($order);
        } else {
            // Create a test order
            $this->info("Creating a test order and sending to Printful...");
            $this->createAndTestOrder($userId);
        }

        return 0;
    }

    private function testExistingOrder(Order $order)
    {
        $this->info("Order details:");
        $this->info("- Order ID: {$order->id}");
        $this->info("- Order Number: {$order->order_number}");
        $this->info("- Status: {$order->status}");
        $this->info("- Total: \${$order->total}");
        $this->info("- Items: " . $order->orderItems->count());

        $this->info("\nOrder items:");
        foreach ($order->orderItems as $item) {
            $this->info("- {$item->name} ({$item->size}, {$item->color}) x{$item->quantity}");
            $this->info("  Printful Variant ID: {$item->printful_variant_id}");
        }

        $this->info("\nShipping address:");
        if ($order->shipping_address && is_array($order->shipping_address)) {
            $address = $order->shipping_address;
            $this->info("- Name: " . ($address['name'] ?? 'N/A'));
            $this->info("- Address: " . ($address['address1'] ?? $address['address'] ?? 'N/A'));
            $this->info("- City: " . ($address['city'] ?? 'N/A'));
            $this->info("- State: " . ($address['state_code'] ?? $address['state'] ?? 'N/A'));
            $this->info("- Zip: " . ($address['zip'] ?? 'N/A'));
            $this->info("- Country: " . ($address['country_code'] ?? $address['country'] ?? 'N/A'));
        } else {
            $this->warn("No shipping address found");
        }

        if ($this->confirm('Do you want to send this order to Printful?')) {
            $this->sendToPrintful($order);
        }
    }

    private function createAndTestOrder($userId)
    {
        // Find a user
        $user = User::find($userId);
        if (!$user) {
            $this->error("User #{$userId} not found");
            return;
        }

        // Find an active product with Printful data and valid size/color
        $product = Product::where('is_active', true)
            ->whereNotNull('printful_id')
            ->where('printful_id', '>', 0)
            ->whereNotNull('metadata')
            ->whereRaw("JSON_LENGTH(sizes) > 0")
            ->whereRaw("JSON_LENGTH(colors) > 0")
            ->first();

        if (!$product) {
            $this->error("No active products with Printful data and valid size/color found");
            return;
        }

        // Get a valid size and color
        $sizes = $product->sizes;
        $colors = $product->colors;
        $size = is_array($sizes) && count($sizes) > 0 ? $sizes[0] : 'M';
        $color = is_array($colors) && count($colors) > 0 ? $colors[0] : 'Black';

        // Find or create a design
        $design = Design::where('user_id', $userId)->first();
        if (!$design) {
            $this->warn("No design found for user, creating a test design...");
            $design = Design::create([
                'user_id' => $userId,
                'name' => 'Test Design',
                'description' => 'Test design for Printful order',
                'front_image_path' => null,
                'back_image_path' => null,
            ]);
        }

        // Use a valid image URL for the print file (use product image if no design image)
        $printFileUrl = $design->front_image_path
                            ? Storage::url($design->front_image_path)
            : ($product->image_url ?? ($product->metadata['printful_variant']['image_url'] ?? null));

        // Create test order
        $order = Order::create([
            'user_id' => $userId,
            'order_number' => 'TEST-' . time() . '-' . $userId,
            'stripe_payment_intent_id' => 'test_payment_intent',
            'status' => 'paid',
            'subtotal' => $product->base_price,
            'tax' => $product->base_price * 0.08,
            'shipping' => 5.99,
            'total' => $product->base_price + ($product->base_price * 0.08) + 5.99,
            'currency' => 'USD',
            'shipping_address' => [
                'name' => 'Test User',
                'email' => $user->email,
                'phone' => '555-123-4567',
                'address1' => '123 Test Street',
                'city' => 'Test City',
                'state_code' => 'CA',
                'zip' => '90210',
                'country_code' => 'US',
            ],
            'paid_at' => now(),
        ]);

        // Create order item with valid data
        OrderItem::create([
            'order_id' => $order->id,
            'design_id' => $design->id,
            'product_id' => $product->id,
            'printful_variant_id' => $product->printful_id,
            'size' => $size,
            'color' => $color,
            'quantity' => 1,
            'unit_price' => $product->base_price,
            'total_price' => $product->base_price,
            'design_data' => json_encode([
                'design_id' => $design->id,
                'design_name' => $design->name,
                'design_description' => $design->description,
                'front_image_path' => $design->front_image_path,
                'back_image_path' => $design->back_image_path,
                'print_file_url' => $printFileUrl,
            ]),
        ]);

        $this->info("Test order created:");
        $this->info("- Order ID: {$order->id}");
        $this->info("- Order Number: {$order->order_number}");
        $this->info("- Product: {$product->name}");
        $this->info("- Printful Variant ID: {$product->printful_id}");
        $this->info("- Size: {$size}");
        $this->info("- Color: {$color}");
        $this->info("- Print file URL: {$printFileUrl}");

        if ($this->confirm('Do you want to send this test order to Printful?')) {
            $this->sendToPrintful($order);
        }
    }

    private function sendToPrintful(Order $order)
    {
        $this->info("\nSending order to Printful...");

        try {
            // Load the design relationship to access design images
            $order->load(['orderItems.design']);
            
            $printfulItems = [];
            
            $this->info("Processing " . $order->orderItems->count() . " order items...");
            
            foreach ($order->orderItems as $item) {
                $this->info("Processing item {$item->id} - Design ID: " . ($item->design_id ?? 'null'));
                
                $designData = json_decode($item->design_data, true);
                
                // Use the printful_id (variant ID) directly from the product
                $variantId = $item->printful_variant_id ?? $item->variant_id ?? null;
                
                // Prioritize the actual design image (just the artwork) over product image
                $fileUrl = null;
                if ($item->design && $item->design->front_image_path) {
                    // Use the actual design image (just the artwork)
                    $fileUrl = Storage::url($item->design->front_image_path);
                    $this->info("Using design front image: {$fileUrl}");
                } elseif (isset($designData['front_image_path']) && $designData['front_image_path']) {
                    // Fallback to design data if design relationship is not loaded
                    $fileUrl = Storage::url($designData['front_image_path']);
                    $this->info("Using design data front image: {$fileUrl}");
                } elseif (isset($designData['print_file_url']) && $designData['print_file_url']) {
                    // Last fallback to print_file_url if it exists
                    $fileUrl = $designData['print_file_url'];
                    $this->info("Using print file URL: {$fileUrl}");
                } else {
                    // For testing, use a publicly accessible image URL since Printful requires public URLs
                    // In production, you would need to upload the design image to a public service
                    $fileUrl = 'https://via.placeholder.com/800x600/000000/FFFFFF?text=Design+Image';
                    $this->info("Using placeholder image for testing: {$fileUrl}");
                }
                
                // If no design image is available, this order cannot be sent to Printful
                if (!$fileUrl) {
                    $this->error("Order item {$item->id} has no design image available for Printful");
                    continue;
                }
                
                $size = $item->size;
                $color = $item->color;
                
                if (!$variantId) {
                    $this->error("No Printful variant ID found for product: " . $item->product_id);
                    continue;
                }
                if (!$size) {
                    $this->error("No size for order item: " . $item->id);
                    continue;
                }
                if (!$color) {
                    $this->error("No color for order item: " . $item->id);
                    continue;
                }
                
                $printfulItems[] = [
                    'variant_id' => $variantId,
                    'quantity' => $item->quantity,
                    'files' => [
                        [
                            'url' => $fileUrl,
                            'type' => 'front',
                        ]
                    ],
                    'options' => [
                        'size' => $size,
                        'color' => $color,
                    ]
                ];
                
                $this->info("Added item to printfulItems array (count: " . count($printfulItems) . ")");
            }

            if (empty($printfulItems)) {
                $this->error("No valid Printful items found for order: " . $order->id);
                return;
            }

            $orderData = [
                'shipping_address' => $order->shipping_address,
                'items' => $printfulItems,
                'subtotal' => $order->subtotal,
                'shipping' => $order->shipping,
                'tax' => $order->tax,
                'total' => $order->total,
                'currency' => $order->currency,
            ];

            $this->info("Sending order data to Printful with " . count($orderData['items']) . " items...");

            $printfulOrder = $this->printfulService->createOrder($orderData);

            if ($printfulOrder) {
                $this->info("✅ Printful order created successfully!");
                $this->info("Printful Order ID: {$printfulOrder['id']}");
                $this->info("Status: {$printfulOrder['status']}");
                
                // Update the order with Printful ID
                $order->update([
                    'printful_order_id' => $printfulOrder['id'],
                    'status' => 'processing',
                ]);

                $this->info("Order status updated to 'processing'");
            } else {
                $this->error("❌ Failed to create Printful order");
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception occurred: " . $e->getMessage());
            Log::error('Test Printful Order Exception: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 
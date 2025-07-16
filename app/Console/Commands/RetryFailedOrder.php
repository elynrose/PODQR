<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\PrintfulService;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;

class RetryFailedOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:retry {order_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry a failed order with Printful';

    protected $printfulService;
    protected $stripeService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PrintfulService $printfulService, StripeService $stripeService)
    {
        parent::__construct();
        $this->printfulService = $printfulService;
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        $this->info("Attempting to retry order #{$orderId}...");
        
        $order = Order::with(['orderItems.design'])->find($orderId);
        
        if (!$order) {
            $this->error("Order #{$orderId} not found!");
            return 1;
        }
        
        $this->info("Order found: {$order->order_number} (Status: {$order->status})");
        
        // Check if order is in a state that can be retried
        if (!in_array($order->status, ['paid', 'error'])) {
            $this->error("Order status '{$order->status}' cannot be retried. Only 'paid' or 'error' orders can be retried.");
            return 1;
        }
        
        // Check if order already has a Printful order ID
        if ($order->printful_order_id) {
            $this->warn("Order already has Printful order ID: {$order->printful_order_id}");
            $this->info("Checking Printful order status...");
            
            $printfulOrder = $this->printfulService->getOrderStatus($order->printful_order_id);
            if ($printfulOrder) {
                $this->info("Printful order status: {$printfulOrder['status']}");
                return 0;
            } else {
                $this->warn("Could not retrieve Printful order status. Proceeding with retry...");
            }
        }
        
        // Check if order has items with designs
        $hasValidItems = $order->orderItems->contains(function($item) {
            return $item->design_id && $item->design && $item->design->front_image_path;
        });
        
        if (!$hasValidItems) {
            $this->error("Order has no items with valid designs. Cannot retry.");
            return 1;
        }
        
        $this->info("Order has valid items with designs. Proceeding with Printful order creation...");
        
        try {
            // Create Printful order using the fixed logic
            $printfulOrder = $this->createPrintfulOrder($order);
            
            if ($printfulOrder) {
                $order->update([
                    'printful_order_id' => $printfulOrder['id'],
                    'status' => 'processing',
                ]);
                
                $this->info("✅ Printful order created successfully!");
                $this->info("Printful Order ID: {$printfulOrder['id']}");
                $this->info("Order status updated to: processing");
                
                return 0;
            } else {
                $this->error("❌ Failed to create Printful order");
                $order->update(['status' => 'error']);
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception occurred: " . $e->getMessage());
            Log::error("RetryFailedOrder command error: " . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Create order in Printful (fixed version)
     */
    protected function createPrintfulOrder($order)
    {
        try {
            $printfulItems = [];
            
            \Log::info('RetryFailedOrder: Starting createPrintfulOrder for order ' . $order->id);
            \Log::info('RetryFailedOrder: Order has ' . $order->orderItems->count() . ' items');
            
            // Load the design relationship for all order items
            $order->load('orderItems.design');
            
            foreach ($order->orderItems as $item) {
                \Log::info('RetryFailedOrder: Processing order item ' . $item->id);
                \Log::info('RetryFailedOrder: Item details', [
                    'design_id' => $item->design_id,
                    'product_id' => $item->product_id,
                    'size' => $item->size,
                    'color' => $item->color,
                    'quantity' => $item->quantity,
                    'has_design_relationship' => $item->design ? 'Yes' : 'No',
                    'design_name' => $item->design ? $item->design->name : 'null',
                    'design_front_image' => $item->design ? $item->design->front_image_path : 'null'
                ]);
                
                $designData = json_decode($item->design_data, true);
                \Log::info('RetryFailedOrder: Design data from JSON', ['design_data' => $designData]);
                
                // Use the printful_id (variant ID) directly from the product
                $variantId = $item->printful_variant_id ?? null;
                
                // Validate the variant ID before using it
                if (!$variantId) {
                    \Log::error("No Printful variant ID found for order item", [
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_printful_id' => $item->product_id ?? 'null',
                        'printful_variant_id' => $item->printful_variant_id ?? 'null'
                    ]);
                    continue;
                }
                
                // Validate that the variant ID exists in Printful
                $variantValidation = $this->printfulService->validateVariantId($variantId);
                if (!$variantValidation['exists']) {
                    \Log::error("Invalid Printful variant ID for order item", [
                        'order_item_id' => $item->id,
                        'variant_id' => $variantId,
                        'validation_message' => $variantValidation['message']
                    ]);
                    continue;
                }
                
                // Collect all available design images (front and back)
                $designFiles = [];
                
                // Check for front image
                $frontImageUrl = null;
                if ($item->design && $item->design->front_image_path) {
                    $relativeUrl = \Storage::url($item->design->front_image_path);
                    $frontImageUrl = url($relativeUrl);
                    \Log::info('RetryFailedOrder: Found design front image', ['file_url' => $frontImageUrl]);
                } elseif (isset($designData['front_image_path']) && $designData['front_image_path']) {
                    $relativeUrl = \Storage::url($designData['front_image_path']);
                    $frontImageUrl = url($relativeUrl);
                    \Log::info('RetryFailedOrder: Found front image in design data', ['file_url' => $frontImageUrl]);
                }
                
                // Check for back image
                $backImageUrl = null;
                if ($item->design && $item->design->back_image_path) {
                    $relativeUrl = \Storage::url($item->design->back_image_path);
                    $backImageUrl = url($relativeUrl);
                    \Log::info('RetryFailedOrder: Found design back image', ['file_url' => $backImageUrl]);
                } elseif (isset($designData['back_image_path']) && $designData['back_image_path']) {
                    $relativeUrl = \Storage::url($designData['back_image_path']);
                    $backImageUrl = url($relativeUrl);
                    \Log::info('RetryFailedOrder: Found back image in design data', ['file_url' => $backImageUrl]);
                }
                
                // Validate image URLs and add to design files
                if ($frontImageUrl) {
                    if (filter_var($frontImageUrl, FILTER_VALIDATE_URL) && 
                        !str_contains($frontImageUrl, 'localhost') && 
                        !str_contains($frontImageUrl, '127.0.0.1')) {
                        $designFiles[] = [
                            'url' => $frontImageUrl,
                            'type' => 'default'
                        ];
                        \Log::info('RetryFailedOrder: Added valid front image', ['file_url' => $frontImageUrl]);
                    } else {
                        \Log::error("Front image URL is invalid or not publicly accessible", ['file_url' => $frontImageUrl]);
                    }
                }
                
                if ($backImageUrl) {
                    if (filter_var($backImageUrl, FILTER_VALIDATE_URL) && 
                        !str_contains($backImageUrl, 'localhost') && 
                        !str_contains($backImageUrl, '127.0.0.1')) {
                        $designFiles[] = [
                            'url' => $backImageUrl,
                            'type' => 'back'
                        ];
                        \Log::info('RetryFailedOrder: Added valid back image', ['file_url' => $backImageUrl]);
                    } else {
                        \Log::error("Back image URL is invalid or not publicly accessible", ['file_url' => $backImageUrl]);
                    }
                }
                
                // If no valid images are available, this order cannot be sent to Printful
                if (empty($designFiles)) {
                    \Log::error("Order item {$item->id} has no valid design images available for Printful", [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'design_id' => $item->design_id,
                        'product_id' => $item->product_id,
                        'front_image_url' => $frontImageUrl,
                        'back_image_url' => $backImageUrl,
                        'design_data' => $designData
                    ]);
                    continue;
                }
                
                $size = $item->size;
                $color = $item->color ?: 'Default'; // Provide default color if empty
                
                \Log::info('RetryFailedOrder: Item data', [
                    'variant_id' => $variantId,
                    'design_files_count' => count($designFiles),
                    'size' => $size,
                    'color' => $color,
                    'quantity' => $item->quantity
                ]);
                
                if (!$variantId) {
                    \Log::error("No Printful variant ID found for product: " . $item->product_id);
                    continue;
                }
                if (empty($designFiles)) {
                    \Log::error("No design files for order item: " . $item->id);
                    continue;
                }
                if (!$size) {
                    \Log::error("No size for order item: " . $item->id);
                    continue;
                }
                
                \Log::info('RetryFailedOrder: Design files for item', [
                    'product_id' => $item->product_id,
                    'variant_id' => $variantId,
                    'design_files' => $designFiles
                ]);
                
                // Build options with required fields
                $options = [
                    'size' => $size,
                    'color' => $color,
                ];
                
                // Add stitch_color option for products that require it
                // Most T-shirts and similar products require stitch color
                if (in_array($color, ['White', 'white', '#ffffff'])) {
                    $options['stitch_color'] = 'white';
                } else {
                    $options['stitch_color'] = 'black';
                }
                
                $printfulItems[] = [
                    'variant_id' => $variantId,
                    'quantity' => $item->quantity,
                    'files' => $designFiles,
                    'options' => $options,
                ];
                
                \Log::info('RetryFailedOrder: Added item to printfulItems array', [
                    'printfulItems_count' => count($printfulItems)
                ]);
            }

            \Log::info('RetryFailedOrder: Final printfulItems array', [
                'count' => count($printfulItems),
                'items' => $printfulItems
            ]);

            if (empty($printfulItems)) {
                \Log::error("No valid Printful items found for order: " . $order->id);
                return null;
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

            \Log::info('RetryFailedOrder: Final orderData', [
                'items_count' => count($orderData['items']),
                'items' => $orderData['items']
            ]);

            $printfulOrder = $this->printfulService->createOrder($orderData);
            
            if ($printfulOrder) {
                \Log::info("Printful order created successfully", [
                    'order_id' => $order->id,
                    'printful_order_id' => $printfulOrder['id']
                ]);
                
                return $printfulOrder;
            }

        } catch (\Exception $e) {
            \Log::error('Printful order creation error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return null;
    }
} 
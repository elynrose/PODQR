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
                
                // Prioritize the actual design image (just the artwork) over product image
                $fileUrl = null;
                if ($item->design && $item->design->front_image_path) {
                    // Use storage URL for design image - convert to full URL
                    $relativeUrl = \Storage::url($item->design->front_image_path);
                    $fileUrl = url($relativeUrl);
                    \Log::info('RetryFailedOrder: Using design front image from storage', ['file_url' => $fileUrl]);
                } elseif (isset($designData['print_file_url']) && $designData['print_file_url']) {
                    // Fallback to print_file_url if it exists
                    $fileUrl = $designData['print_file_url'];
                    \Log::info('RetryFailedOrder: Using print file URL', ['file_url' => $fileUrl]);
                } elseif (isset($designData['front_image_path']) && $designData['front_image_path']) {
                    // Fallback to design data if design relationship is not loaded
                    $relativeUrl = \Storage::url($designData['front_image_path']);
                    $fileUrl = url($relativeUrl);
                    \Log::info('RetryFailedOrder: Using design data front image from storage', ['file_url' => $fileUrl]);
                }
                
                // Validate that the image URL is accessible and public
                if ($fileUrl && !filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                    \Log::error("Invalid image URL format", ['file_url' => $fileUrl]);
                    continue; // Skip this item if URL is invalid
                }
                
                // Ensure the URL is publicly accessible (not localhost)
                if ($fileUrl && (str_contains($fileUrl, 'localhost') || str_contains($fileUrl, '127.0.0.1'))) {
                    \Log::error("Image URL is not publicly accessible", ['file_url' => $fileUrl]);
                    continue; // Skip this item if URL is not public
                }
                
                // If no design image is available, this order cannot be sent to Printful
                if (!$fileUrl) {
                    \Log::error("Order item {$item->id} has no design image available for Printful", [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'design_id' => $item->design_id,
                        'product_id' => $item->product_id,
                        'design_data' => $designData
                    ]);
                    continue;
                }
                
                $size = $item->size;
                $color = $item->color ?: 'Default'; // Provide default color if empty
                
                \Log::info('RetryFailedOrder: Item data', [
                    'variant_id' => $variantId,
                    'file_url' => $fileUrl,
                    'size' => $size,
                    'color' => $color,
                    'quantity' => $item->quantity
                ]);
                
                if (!$variantId) {
                    \Log::error("No Printful variant ID found for product: " . $item->product_id);
                    continue;
                }
                if (!$fileUrl) {
                    \Log::error("No print file URL for order item: " . $item->id);
                    continue;
                }
                if (!$size) {
                    \Log::error("No size for order item: " . $item->id);
                    continue;
                }
                
                // Determine the correct file type based on product variant requirements
                $fileType = 'default'; // Default file type for most products
                
                // Check if this is a T-shirt variant that supports front/back
                if (str_contains($fileUrl, 'back_')) {
                    $fileType = 'back';
                } elseif (str_contains($fileUrl, 'front_') || str_contains($fileUrl, 'designs/')) {
                    // For T-shirts and other products, use 'default' instead of 'front'
                    $fileType = 'default';
                }
                
                \Log::info('RetryFailedOrder: File type determination', [
                    'product_id' => $item->product_id,
                    'variant_id' => $variantId,
                    'file_url' => $fileUrl,
                    'selected_file_type' => $fileType
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
                    'files' => [
                        [
                            'url' => $fileUrl,
                            'type' => $fileType,
                        ]
                    ],
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
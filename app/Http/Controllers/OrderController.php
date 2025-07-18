<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Design;
use App\Services\PrintfulService;
use App\Services\StripeService;
use App\Jobs\SyncPrintfulProducts;
use App\Mail\OrderFailedNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    protected $printfulService;
    protected $stripeService;

    public function __construct(PrintfulService $printfulService, StripeService $stripeService)
    {
        $this->printfulService = $printfulService;
        $this->stripeService = $stripeService;
    }

    /**
     * Get products that are compatible with the selected shipping location
     */
    protected function getLocationCompatibleProducts($query, $location)
    {
        \Log::info('Filtering products by location', ['location' => $location]);
        
        // Get all products first
        $allProducts = $query->get();
        
        // Filter products based on API validation
        $compatibleProducts = [];
        
        foreach ($allProducts as $product) {
            if ($this->isProductCompatibleWithLocation($product, $location)) {
                $compatibleProducts[] = $product->id;
            }
        }
        
        \Log::info('Location compatibility check completed', [
            'location' => $location,
            'total_products' => $allProducts->count(),
            'compatible_products' => count($compatibleProducts)
        ]);
        
        // Return query filtered to only compatible products
        return $query->whereIn('id', $compatibleProducts);
    }

    /**
     * Check if a product is compatible with the selected shipping location
     */
    protected function isProductCompatibleWithLocation($product, $location)
    {
        // Store products are already configured and tested - no regional restrictions
        if (isset($product->is_store_product) && $product->is_store_product) {
            \Log::info('Store product - no regional restrictions apply', [
                'product_id' => $product->id ?? 'unknown',
                'product_name' => $product->name ?? 'unknown',
                'location' => $location
            ]);
            return true;
        }
        
        // Use known working products from your store
        $knownWorkingProducts = [
            '71',   // Bella + Canvas 3001 (from your store)
            '493',  // Unisex Eco Sweatshirt | Stanley/Stella STSU178
            '506',  // SOL'S 03574 Comet
            '515',  // Oversized Tie-Dye T-shirt | Shaka Wear SHHTDS
        ];
        
        // If this is a known working product, allow it
        if (in_array($product->printful_id, $knownWorkingProducts)) {
            \Log::info('Known working product - allowing for all locations', [
                'product_id' => $product->id,
                'printful_id' => $product->printful_id,
                'product_name' => $product->name,
                'location' => $location
            ]);
            return true;
        }
        
        // Skip validation for Asia locations since most products ship there
        $asiaLocations = ['JP', 'KR', 'SG', 'MY', 'TH', 'VN', 'ID', 'PH'];
        if (in_array($location, $asiaLocations)) {
            return true;
        }
        
        // For non-Asia locations, check if the product has known regional restrictions
        $knownRestrictedProducts = [
            '8586', // United Athle 5001-01 product ID (all variants)
            '8587', // United Athle 5001-01 product ID (all variants)
        ];
        
        // Check if this product is in the known restricted list
        if (in_array($product->printful_id, $knownRestrictedProducts)) {
            \Log::info('Product excluded due to known regional restrictions', [
                'product_id' => $product->id,
                'printful_id' => $product->printful_id,
                'product_name' => $product->name,
                'location' => $location
            ]);
            return false;
        }
        
        // For other products, we could make a test API call to Printful
        // But for now, we'll assume they're compatible to avoid API rate limits
        return true;
    }

    /**
     * Show the order form
     */
    public function showOrderForm(Request $request, $designId)
    {
        try {
            $design = Design::findOrFail($designId);
            
            // Get user's location from request, profile, or default to US
            $userLocation = $request->get('location') ?? 
                           auth()->user()->country_code ?? 
                           'US';
            
            // Validate location is supported
            $supportedLocations = ['US', 'CA', 'GB', 'AU', 'JP'];
            if (!in_array($userLocation, $supportedLocations)) {
                $userLocation = 'US'; // Default to US if unsupported
            }
            
            \Log::info('OrderController: Loading products for location', [
                'design_id' => $designId,
                'user_location' => $userLocation,
                'user_id' => auth()->id()
            ]);
            
            // Get location-compatible products with pagination (optimized for performance)
            $products = $this->printfulService->getLocationCompatibleProducts($userLocation, 15, 0);

            // Debug: Log after API call
            \Log::info('OrderController: Printful API response', [
                'products_count' => $products->count(),
                'user_location' => $userLocation,
                'products' => $products->toArray()
            ]);

            // Extract unique types, sizes, and colors from products for filters
            $types = $products->pluck('type')->unique()->filter(function($type) {
                return is_string($type);
            })->values();
            $sizes = collect();
            $colors = collect();
            
            foreach ($products as $product) {
                if (isset($product['sizes']) && is_array($product['sizes'])) {
                    $sizes = $sizes->merge($product['sizes']);
                }
                if (isset($product['colors']) && is_array($product['colors'])) {
                    // Extract color names from the color objects
                    foreach ($product['colors'] as $color) {
                        if (is_array($color) && isset($color['color_name']) && is_string($color['color_name'])) {
                            $colors->push($color['color_name']);
                        } elseif (is_string($color)) {
                            $colors->push($color);
                        }
                    }
                }
            }
            
            $sizes = $sizes->unique()->filter(function($size) {
                return is_string($size);
            })->values();
            $colors = $colors->unique()->filter(function($color) {
                return is_string($color);
            })->values();

            // Debug: Log final data
            \Log::info('OrderController: Final data prepared', [
                'types_count' => $types->count(),
                'sizes_count' => $sizes->count(),
                'colors_count' => $colors->count(),
                'userLocation' => $userLocation
            ]);

            return view('orders.create', compact('design', 'products', 'userLocation', 'types', 'sizes', 'colors'));
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Design not found - show a friendly error page
            return view('orders.design-not-found', [
                'designId' => $designId,
                'availableDesigns' => Design::take(5)->get(['id', 'name'])
            ]);
        } catch (\Exception $e) {
            \Log::error('OrderController: Error in showOrderForm', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty products on error
            return view('orders.create', compact('design', 'products', 'userLocation', 'types', 'sizes', 'colors'));
        }
    }

    /**
     * Get more products for pagination
     */
    public function getMoreProducts(Request $request)
    {
        try {
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            
            \Log::info('OrderController: Loading more products', [
                'offset' => $offset,
                'limit' => $limit
            ]);
            
            // Get more T-shirt products from Printful API
            $products = $this->printfulService->getMoreTshirtProducts($offset, $limit);
            
            // Filter to only show known working products to avoid regional issues
            $products = $products->filter(function ($product) {
                $knownWorkingProducts = ['71', '493', '506', '515'];
                return in_array($product['printful_id'], $knownWorkingProducts);
            });
            
            \Log::info('OrderController: More products loaded', [
                'products_count' => $products->count(),
                'offset' => $offset,
                'limit' => $limit
            ]);
            
            return response()->json([
                'success' => true,
                'products' => $products->values(),
                'has_more' => $products->count() >= $limit
            ]);
            
        } catch (\Exception $e) {
            \Log::error('OrderController: Error loading more products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to load more products',
                'products' => []
            ], 500);
        }
    }

    /**
     * Get available products for ordering
     */
    public function getProducts(Request $request)
    {
        $designId = $request->input('design_id');
        $design = $designId ? Design::find($designId) : null;
        
        // Focus on USA location for unisex t-shirts
        $userLocation = 'US';
        
        try {
            // Get location-compatible products with pagination (optimized for performance)
            $products = $this->printfulService->getLocationCompatibleProducts($userLocation, 12, 0);
            
            if ($products && $products->count() > 0) {
                Log::info('OrderController: Successfully fetched ' . $products->count() . ' location-compatible products');
            } else {
                // Fallback to known working products
                Log::warning('OrderController: No location-compatible products found, using known working products');
                $products = $this->printfulService->getKnownWorkingProducts($userLocation, 12, 0);
            }
        } catch (\Exception $e) {
            Log::error('OrderController: Error fetching location-compatible products: ' . $e->getMessage());
            // Ultimate fallback: known working products
            $products = $this->printfulService->getKnownWorkingProducts($userLocation, 12, 0);
        }

        // Filter by design color if design exists
        if ($design && $design->color_code) {
            $products = $products->filter(function ($product) use ($design) {
                if (isset($product['colors']) && is_array($product['colors'])) {
                    foreach ($product['colors'] as $color) {
                        if (is_array($color) && isset($color['color_codes'])) {
                            foreach ($color['color_codes'] as $colorCode) {
                                if ($colorCode === $design->color_code) {
                                    return true;
                                }
                            }
                        }
                    }
                }
                return false;
            });
        }

        return response()->json($products->values());
    }

    /**
     * Calculate order total
     */
    public function calculateTotal(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
        ]);

        $subtotal = 0;
        $items = [];

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $itemTotal = $product->base_price * $item['quantity'];
            $subtotal += $itemTotal;

            $items[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'unit_price' => $product->base_price,
                'total' => $itemTotal,
            ];
        }

        // Calculate shipping (simplified for now)
        $shipping = 5.99;
        
        // Calculate tax (simplified for now)
        $tax = $subtotal * 0.08; // 8% tax rate
        
        $total = $subtotal + $shipping + $tax;

        return response()->json([
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'items' => $items,
        ]);
    }

    /**
     * Create Stripe Checkout session
     */
    public function createCheckoutSession(Request $request)
    {
        try {
            \Log::info('=== Creating checkout session ===');
            \Log::info('Request method: ' . $request->method());
            \Log::info('Request URL: ' . $request->url());
            \Log::info('Request headers: ' . json_encode($request->headers->all()));
            \Log::info('Request data: ' . json_encode($request->all()));
            \Log::info('User authenticated: ' . (Auth::check() ? 'Yes' : 'No'));
            \Log::info('User ID: ' . (Auth::id() ?? 'Not authenticated'));
            
            $request->validate([
                'design_id' => 'required|exists:designs,id',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.size' => 'required|string',
                'items.*.color' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'shipping_address' => 'required|array',
            ]);

            $design = Design::findOrFail($request->design_id);
            
            // Check if user owns the design
            if ($design->user_id !== Auth::id()) {
                \Log::error('User does not own design', ['user_id' => Auth::id(), 'design_user_id' => $design->user_id]);
                return response()->json(['error' => 'You can only order your own designs.'], 403);
            }

            // Prepare order data for Stripe
            $orderData = [
                'design_id' => $design->id,
                'user_id' => Auth::id(),
                'shipping_address' => $request->shipping_address,
                'items' => [],
            ];

            foreach ($request->items as $item) {
                // Get product from Printful API data instead of database
                $printfulProducts = $this->printfulService->getTshirtProducts(50);
                $product = $printfulProducts->firstWhere('printful_id', $item['product_id']);
                
                if (!$product) {
                    \Log::error('Product not found in Printful data', ['product_id' => $item['product_id']]);
                    return response()->json(['error' => 'Product not found'], 404);
                }
                
                $itemTotal = $product['base_price'] * $item['quantity'];
                $subtotal += $itemTotal;

                $orderData['items'][] = [
                    'product_id' => $product['printful_id'],
                    'name' => $product['name'],
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'price' => $product['base_price'],
                ];
            }

            \Log::info('Order data prepared: ' . json_encode($orderData));

            // Create checkout session
            $successUrl = route('orders.success') . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('orders.create', $design->id);

            \Log::info('Creating Stripe session with URLs', ['successUrl' => $successUrl, 'cancelUrl' => $cancelUrl]);

            $session = $this->stripeService->createCheckoutSession($orderData, $successUrl, $cancelUrl);

            if (!$session) {
                \Log::error('Failed to create Stripe session');
                return response()->json(['error' => 'Failed to create checkout session'], 500);
            }

            \Log::info('Stripe session created successfully', ['session_id' => $session->id, 'checkout_url' => $session->url]);

            return response()->json([
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error creating checkout session: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process the order
     */
    public function processOrder(Request $request)
    {
        $request->validate([
            'design_id' => 'required|exists:designs,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|string',
            'items.*.size' => 'required|string',
            'items.*.color' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'payment_intent_id' => 'required|string',
        ]);

        $design = Design::findOrFail($request->design_id);
        
        // Check if user owns the design
        if ($design->user_id !== Auth::id()) {
            abort(403, 'You can only order your own designs.');
        }

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                // Validate product_id is not empty
                if (empty($item['product_id'])) {
                    \Log::error('Empty product_id provided', ['item' => $item]);
                    return response()->json(['error' => 'Invalid product ID'], 400);
                }
                
                // Get product from Printful API data instead of database
                $printfulProducts = $this->printfulService->getTshirtProducts(50);
                $product = $printfulProducts->firstWhere('printful_id', $item['product_id']);
                
                if (!$product) {
                    \Log::error('Product not found in Printful data', ['product_id' => $item['product_id']]);
                    return response()->json(['error' => 'Product not found'], 404);
                }
                
                $itemTotal = $product['base_price'] * $item['quantity'];
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'product' => $product,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product['base_price'],
                    'total_price' => $itemTotal,
                ];
            }

            $shipping = 5.99;
            $tax = $subtotal * 0.08;
            $total = $subtotal + $shipping + $tax;

            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => 'ORD-' . time() . '-' . Auth::id(),
                'stripe_payment_intent_id' => $request->payment_intent_id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'currency' => 'USD',
                'shipping_address' => $request->shipping_address,
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                $designData = [];
                
                if ($design) {
                    $designData = [
                        'design_id' => $design->id,
                        'design_name' => $design->name,
                        'design_description' => $design->description,
                        'front_image_path' => $design->front_image_path,
                        'back_image_path' => $design->back_image_path,
                        'print_file_url' => $design->front_image_path ? url(Storage::url($design->front_image_path)) : null,
                    ];
                    \Log::info('Creating order item with design:', [
                        'design_id' => $design->id,
                        'design_name' => $design->name,
                        'front_image_path' => $design->front_image_path
                    ]);
                } else {
                    $designData = [
                        'product_name' => $item['name'],
                        'product_type' => 'T-shirt',
                        'variant_id' => $item['product_id'],
                    ];
                    \Log::info('Creating order item without design', [
                        'product_name' => $designData['product_name'],
                        'product_type' => $designData['product_type']
                    ]);
                }
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'design_id' => $design ? $design->id : null,
                    'product_id' => $item['product_id'],
                    'printful_variant_id' => $item['product_id'],
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'design_data' => json_encode($designData),
                ]);
                
                \Log::info('Order item created:', [
                    'order_item_id' => $orderItem->id,
                    'design_id' => $orderItem->design_id,
                    'design_data' => $orderItem->design_data
                ]);
            }

            // Verify payment with Stripe
            $paymentIntent = $this->stripeService->getPaymentIntent($request->payment_intent_id);
            
            if (!$paymentIntent || $paymentIntent->status !== 'succeeded') {
                throw new \Exception('Payment not completed');
            }

            // Update order status
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Create order in Printful
            $this->createPrintfulOrder($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => 'Order placed successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order processing error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to process order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create order in Printful
     */
    protected function createPrintfulOrder($order)
    {
        try {
            $printfulItems = [];
            
            \Log::info('OrderController: Starting createPrintfulOrder for order ' . $order->id);
            \Log::info('OrderController: Order has ' . $order->orderItems->count() . ' items');
            
            // Load the design relationship for all order items
            $order->load('orderItems.design');
            
            foreach ($order->orderItems as $item) {
                \Log::info('OrderController: Processing order item ' . $item->id);
                \Log::info('OrderController: Item details', [
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
                \Log::info('OrderController: Design data from JSON', ['design_data' => $designData]);
                
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
                    $relativeUrl = Storage::url($item->design->front_image_path);
                    $frontImageUrl = url($relativeUrl);
                    \Log::info('OrderController: Found design front image', ['file_url' => $frontImageUrl]);
                } elseif (isset($designData['front_image_path']) && $designData['front_image_path']) {
                    $relativeUrl = Storage::url($designData['front_image_path']);
                    $frontImageUrl = url($relativeUrl);
                    \Log::info('OrderController: Found front image in design data', ['file_url' => $frontImageUrl]);
                }
                
                // Check for back image
                $backImageUrl = null;
                if ($item->design && $item->design->back_image_path) {
                    $relativeUrl = Storage::url($item->design->back_image_path);
                    $backImageUrl = url($relativeUrl);
                    \Log::info('OrderController: Found design back image', ['file_url' => $backImageUrl]);
                } elseif (isset($designData['back_image_path']) && $designData['back_image_path']) {
                    $relativeUrl = Storage::url($designData['back_image_path']);
                    $backImageUrl = url($relativeUrl);
                    \Log::info('OrderController: Found back image in design data', ['file_url' => $backImageUrl]);
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
                        \Log::info('OrderController: Added valid front image', ['file_url' => $frontImageUrl]);
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
                        \Log::info('OrderController: Added valid back image', ['file_url' => $backImageUrl]);
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
                
                \Log::info('OrderController: Item data', [
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
                
                \Log::info('OrderController: Design files for item', [
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
                
                \Log::info('OrderController: Added item to printfulItems array', [
                    'printfulItems_count' => count($printfulItems)
                ]);
            }

            \Log::info('OrderController: Final printfulItems array', [
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

            \Log::info('OrderController: Final orderData', [
                'items_count' => count($orderData['items']),
                'items' => $orderData['items']
            ]);

            $printfulOrder = $this->printfulService->createOrder($orderData);
            
            if ($printfulOrder) {
                $order->update([
                    'printful_order_id' => $printfulOrder['id'],
                    'status' => 'processing',
                ]);
                
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

    /**
     * Show order history
     */
    public function orderHistory()
    {
        $orders = Auth::user()->orders()
            ->with(['orderItems.design'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('orders.history', compact('orders'));
    }

    /**
     * Show order details
     */
    public function showOrder($orderId)
    {
        $order = Order::with(['orderItems.design'])
            ->where('user_id', Auth::id())
            ->findOrFail($orderId);

        return view('orders.show', compact('order'));
    }

    /**
     * Sync products from Printful
     */
    public function syncProducts()
    {
        try {
            // Dispatch the sync job
            SyncPrintfulProducts::dispatch();
            
            return response()->json([
                'message' => 'Printful products sync has been queued successfully!',
                'status' => 'queued'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to queue Printful sync: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to queue Printful sync: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle successful payment from Stripe Checkout
     */
    public function handleSuccess(Request $request)
    {
        \Log::info('=== handleSuccess called ===');
        \Log::info('Request URL: ' . $request->url());
        \Log::info('Request query: ' . json_encode($request->query()));
        
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            \Log::error('No session_id provided');
            return redirect()->route('dashboard')->with('error', 'Invalid session ID');
        }

        \Log::info('Session ID: ' . $sessionId);

        // Retrieve the checkout session
        $session = $this->stripeService->getCheckoutSession($sessionId);
        
        if (!$session) {
            \Log::error('Unable to retrieve checkout session');
            return redirect()->route('dashboard')->with('error', 'Unable to retrieve checkout session');
        }

        \Log::info('Session retrieved: ' . json_encode([
            'id' => $session->id,
            'payment_status' => $session->payment_status,
            'status' => $session->status,
            'metadata' => $session->metadata
        ]));

        // Check if payment was successful
        if ($session->payment_status !== 'paid') {
            \Log::error('Payment not completed. Status: ' . $session->payment_status);
            return redirect()->route('dashboard')->with('error', 'Payment was not completed');
        }

        // Extract order data from metadata
        $orderId = $session->metadata->order_id ?? null;
        $userId = $session->metadata->user_id ?? null;
        
        \Log::info('Extracted metadata: order_id=' . $orderId . ', user_id=' . $userId);
        
        if (!$orderId || !$userId) {
            \Log::error('Invalid order data in metadata');
            return redirect()->route('dashboard')->with('error', 'Invalid order data');
        }

        // Check if user owns the order
        if ($userId != Auth::id()) {
            \Log::error('User mismatch: expected ' . $userId . ', got ' . Auth::id());
            return redirect()->route('dashboard')->with('error', 'Unauthorized access');
        }

        // Find the existing order
        $order = Order::where('id', $orderId)->where('user_id', Auth::id())->first();
        
        if (!$order) {
            \Log::error('Order not found: ' . $orderId);
            return redirect()->route('dashboard')->with('error', 'Order not found');
        }

        \Log::info('Order found: ' . $order->id . ' (status: ' . $order->status . ')');

        // Update order status to paid and store session ID
        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
            'stripe_payment_intent_id' => $session->payment_intent,
            'stripe_session_id' => $sessionId,
        ]);

        \Log::info('Order updated to paid status');

        // Create Printful order
        \Log::info('Creating Printful order...');
        $printfulOrder = $this->createPrintfulOrder($order);

        if ($printfulOrder) {
            \Log::info('Printful order created successfully: ' . json_encode($printfulOrder));
            $order->update([
                'printful_order_id' => $printfulOrder['id'],
                'status' => 'processing',
            ]);
            
            \Log::info('=== handleSuccess completed successfully ===');
            return redirect()->route('orders.show', $order->id)
                           ->with('success', 'Order placed successfully! Your order number is: ' . $order->order_number);
        } else {
            \Log::error('Printful order creation failed - processing refund');
            
            // Process refund for failed Printful order
            $refundResult = $this->stripeService->processRefund($sessionId, $order->total);
            
            if ($refundResult['success']) {
                \Log::info('Refund processed successfully for failed Printful order', [
                    'order_id' => $order->id,
                    'refund_id' => $refundResult['refund_id']
                ]);
                
                // Update order status to cancelled
                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Printful order creation failed - no design image available'
                ]);
                
                // Send email notification to customer
                $this->sendOrderFailedEmail($order, 'Your order could not be processed because no design image was available for printing. A full refund has been processed.');
                
                return redirect()->route('orders.show', $order->id)
                               ->with('error', 'Order cancelled and refunded. No design image was available for printing. Please create a new order with a design.');
            } else {
                \Log::error('Failed to process refund for failed Printful order', [
                    'order_id' => $order->id,
                    'refund_error' => $refundResult['message']
                ]);
                
                // Update order status to indicate issue
                $order->update([
                    'status' => 'error',
                    'notes' => 'Printful order failed and refund processing failed: ' . $refundResult['message']
                ]);
                
                return redirect()->route('orders.show', $order->id)
                               ->with('error', 'Order processing failed. Please contact support immediately.');
            }
        }
    }

    /**
     * Load more products progressively (optimized for performance)
     */
    public function loadMoreProducts(Request $request)
    {
        try {
            $designId = $request->input('design_id');
            $design = $designId ? Design::find($designId) : null;
            
            // Get user's preferred location from request, profile, or default
            $userLocation = $request->get('location') ?? 
                           auth()->user()->country_code ?? 
                           'US';
            
            // Validate location is supported
            $supportedLocations = ['US', 'CA', 'GB', 'AU', 'JP'];
            if (!in_array($userLocation, $supportedLocations)) {
                $userLocation = 'US';
            }
            
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 6); // Smaller batches for faster loading
            
            \Log::info('OrderController: Loading more products', [
                'location' => $userLocation,
                'offset' => $offset,
                'limit' => $limit,
                'design_id' => $designId
            ]);

            // Get location-compatible products with pagination
            $products = $this->printfulService->getLocationCompatibleProducts($userLocation, $limit, $offset);
            
            // Filter by design color if design exists
            if ($design && $design->color_code) {
                $products = $products->filter(function ($product) use ($design) {
                    return in_array($design->color_code, $product['color_codes'] ?? []);
                });
            }

            \Log::info('OrderController: Loaded more products', [
                'products_count' => $products->count(),
                'has_more' => $products->count() >= $limit,
                'location' => $userLocation
            ]);

            return response()->json([
                'success' => true,
                'products' => $products,
                'has_more' => $products->count() >= $limit,
                'offset' => $offset + $products->count(),
                'location' => $userLocation
            ]);

        } catch (\Exception $e) {
            \Log::error('OrderController: Error loading more products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to load more products',
                'products' => []
            ], 500);
        }
    }

    /**
     * Change location and reload products
     */
    public function changeLocation(Request $request)
    {
        try {
            $designId = $request->input('design_id');
            $design = $designId ? Design::find($designId) : null;
            
            // Get and validate location
            $userLocation = $request->get('location', 'US');
            $supportedLocations = ['US', 'CA', 'GB', 'AU', 'JP'];
            if (!in_array($userLocation, $supportedLocations)) {
                $userLocation = 'US';
            }
            
            \Log::info('OrderController: Changing location', [
                'new_location' => $userLocation,
                'design_id' => $designId,
                'user_id' => auth()->id()
            ]);

            // Get location-compatible products
            $products = $this->printfulService->getLocationCompatibleProducts($userLocation, 15, 0);
            
            // Filter by design color if design exists
            if ($design && $design->color_code) {
                $products = $products->filter(function ($product) use ($design) {
                    return in_array($design->color_code, $product['color_codes'] ?? []);
                });
            }

            // Extract filter options from new products
            $types = $products->pluck('type')->unique()->filter(function($type) {
                return is_string($type);
            })->values();
            
            $sizes = collect();
            $colors = collect();
            
            foreach ($products as $product) {
                if (isset($product['sizes']) && is_array($product['sizes'])) {
                    $sizes = $sizes->merge($product['sizes']);
                }
                if (isset($product['colors']) && is_array($product['colors'])) {
                    foreach ($product['colors'] as $color) {
                        if (is_array($color) && isset($color['color_name']) && is_string($color['color_name'])) {
                            $colors->push($color['color_name']);
                        } elseif (is_string($color)) {
                            $colors->push($color);
                        }
                    }
                }
            }
            
            $sizes = $sizes->unique()->filter(function($size) {
                return is_string($size);
            })->values();
            $colors = $colors->unique()->filter(function($color) {
                return is_string($color);
            })->values();

            \Log::info('OrderController: Location change completed', [
                'location' => $userLocation,
                'products_count' => $products->count(),
                'types_count' => $types->count(),
                'sizes_count' => $sizes->count(),
                'colors_count' => $colors->count()
            ]);

            return response()->json([
                'success' => true,
                'products' => $products,
                'types' => $types,
                'sizes' => $sizes,
                'colors' => $colors,
                'location' => $userLocation,
                'location_info' => $this->getLocationInfo($userLocation)
            ]);

        } catch (\Exception $e) {
            \Log::error('OrderController: Error changing location', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to change location',
                'products' => []
            ], 500);
        }
    }

    /**
     * Get location-specific information
     */
    private function getLocationInfo($location)
    {
        $locationInfo = [
            'US' => 'Full product selection available. Most products ship within 3-5 business days.',
            'CA' => 'Limited product selection. Shipping may take 5-10 business days.',
            'GB' => 'Good product selection. Shipping may take 7-14 business days.',
            'AU' => 'Limited product selection. Shipping may take 10-20 business days.',
            'JP' => 'Good product selection. Shipping may take 7-14 business days.'
        ];
        
        return $locationInfo[$location] ?? 'Product availability may vary by location.';
    }

    /**
     * Validate product shipping compatibility with Printful API
     * This method can be used for real-time validation when needed
     */
    protected function validateProductShippingWithAPI($product, $location, $shippingAddress = null)
    {
        try {
            // Create a test order payload to validate shipping
            $testPayload = [
                'store_id' => $this->printfulService->getStoreId(),
                'recipient' => $shippingAddress ?: [
                    'name' => 'Test User',
                    'address1' => '123 Test St',
                    'city' => 'Test City',
                    'state_code' => 'CA',
                    'country_code' => $location,
                    'zip' => '12345',
                    'email' => 'test@example.com',
                    'phone' => '1234567890'
                ],
                'items' => [
                    [
                        'variant_id' => $product->printful_variant_id ?? $product->printful_id,
                        'quantity' => 1
                    ]
                ]
            ];

            // Make a test API call to Printful
            $response = $this->printfulService->testOrderCreation($testPayload);
            
            if ($response && isset($response['success']) && $response['success']) {
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            \Log::warning('API validation failed, falling back to known restrictions', [
                'product_id' => $product->id,
                'location' => $location,
                'error' => $e->getMessage()
            ]);
            
            // Fall back to known restrictions
            return $this->isProductCompatibleWithLocation($product, $location);
        }
    }

    /**
     * Store a new order
     */
    public function store(Request $request)
    {
        \Log::info('=== Order store method called ===');
        \Log::info('Request data received:', [
            'design_id' => $request->design_id,
            'items' => $request->items,
            'shipping_address' => $request->shipping_address,
            'all_data' => $request->all()
        ]);

        $request->validate([
            'items' => 'required|string', // JSON string
            'shipping_address' => 'required|string', // JSON string
            'design_id' => 'nullable|exists:designs,id', // Add design_id validation
        ]);

        try {
            // Decode JSON data
            $items = json_decode($request->items, true);
            $shippingAddress = json_decode($request->shipping_address, true);
            
                    \Log::info('Decoded data:', [
            'design_id' => $request->design_id,
            'items_count' => count($items),
            'items' => $items, // Log the actual items data
            'shipping_address' => $shippingAddress
        ]);

            // Validate shipping address
            $requiredAddressFields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'];
            foreach ($requiredAddressFields as $field) {
                if (empty($shippingAddress[$field])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Shipping address field '$field' is required."
                    ], 400);
                }
            }

            // Validate items structure
            foreach ($items as $item) {
                if (!isset($item['variant_id']) || !isset($item['size']) || !isset($item['color']) || !isset($item['quantity'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid item data provided. Missing required fields.'
                    ], 400);
                }
            }

            // Validate Printful variants directly from API
            $variantValidationErrors = [];
            $validatedItems = [];
            $total = 0;
            
            foreach ($items as $index => $item) {
                // Validate variant ID directly with Printful API
                try {
                    $variantInfo = $this->printfulService->getVariant($item['variant_id']);
                    if (!$variantInfo) {
                        $variantValidationErrors[] = "Variant ID '{$item['variant_id']}' is not valid or no longer available in Printful.";
                        continue;
                    }
                    
                    // Check if variant is discontinued or not enabled
                    if (isset($variantInfo['discontinued']) && $variantInfo['discontinued']) {
                        $variantValidationErrors[] = "Variant ID '{$item['variant_id']}' is discontinued.";
                        continue;
                    }
                    
                                    // Note: Some variants may show as not enabled but are still available for ordering
                // We'll skip this check for now as it may be a Printful API limitation
                // if (isset($variantInfo['is_enabled']) && !$variantInfo['is_enabled']) {
                //     $variantValidationErrors[] = "Variant ID '{$item['variant_id']}' is not enabled.";
                //     continue;
                // }
                    
                    // Calculate item total using variant price
                    $variantPrice = $variantInfo['price'] ?? 19.99;
                    $itemTotal = $variantPrice * $item['quantity'];
                    $total += $itemTotal;
                    
                    $validatedItems[] = [
                        'variant_id' => $item['variant_id'],
                        'product_id' => $variantInfo['product_id'],
                        'name' => $variantInfo['display_name'],
                        'size' => $item['size'],
                        'color' => $item['color'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $variantPrice,
                        'total_price' => $itemTotal,
                        'variant_info' => $variantInfo
                    ];
                    
                } catch (\Exception $e) {
                    \Log::warning("Could not validate Printful variant {$item['variant_id']}: " . $e->getMessage());
                    $variantValidationErrors[] = "Could not verify availability of variant ID '{$item['variant_id']}' in Printful.";
                    continue;
                }
            }

            // If any variants are invalid, return error
            if (!empty($variantValidationErrors)) {
                \Log::error('Printful variant validation failed:', $variantValidationErrors);
                return response()->json([
                    'success' => false,
                    'message' => 'Some products are no longer available: ' . implode(' ', $variantValidationErrors),
                    'errors' => $variantValidationErrors
                ], 400);
            }

            // CRITICAL: Validate design is provided and has image
            $design = null;
            if ($request->design_id) {
                $design = Design::find($request->design_id);
                
                \Log::info('Design found:', [
                    'design_id' => $request->design_id,
                    'design' => $design ? [
                        'id' => $design->id,
                        'name' => $design->name,
                        'front_image_path' => $design->front_image_path,
                        'user_id' => $design->user_id
                    ] : 'Not found'
                ]);
                
                // Check if user owns the design
                if ($design && $design->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only order your own designs.'
                    ], 403);
                }
                
                // CRITICAL: Check if design has an image
                if ($design && !$design->front_image_path) {
                    \Log::warning('Order attempt with design that has no image - rejecting', [
                        'design_id' => $design->id,
                        'design_name' => $design->name
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected design does not have a printable image. Please select a different design or upload an image to your design.'
                    ], 400);
                }
            } else {
                \Log::warning('Order attempt without design ID - rejecting');
                return response()->json([
                    'success' => false,
                    'message' => 'A design must be selected to place an order. Please select a design and try again.'
                ], 400);
            }

            // Calculate shipping and tax
            $shipping = 5.99; // Fixed shipping cost
            $tax = $total * 0.08; // 8% tax
            $grandTotal = $total + $shipping + $tax;

            // Create the order
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'subtotal' => $total,
                'shipping' => $shipping,
                'tax' => $tax,
                'total' => $grandTotal,
                'currency' => 'USD',
                'shipping_address' => $shippingAddress,
            ]);

            // Create order items
            foreach ($validatedItems as $item) {
                $designData = [];
                
                if ($design) {
                    $designData = [
                        'design_id' => $design->id,
                        'design_name' => $design->name,
                        'design_description' => $design->description,
                        'front_image_path' => $design->front_image_path,
                        'back_image_path' => $design->back_image_path,
                        'print_file_url' => $design->front_image_path ? url(Storage::url($design->front_image_path)) : null,
                    ];
                    \Log::info('Creating order item with design:', [
                        'design_id' => $design->id,
                        'design_name' => $design->name,
                        'front_image_path' => $design->front_image_path
                    ]);
                } else {
                    $designData = [
                        'product_name' => $item['name'],
                        'product_type' => 'T-shirt',
                        'variant_id' => $item['variant_id'],
                    ];
                    \Log::info('Creating order item without design', [
                        'product_name' => $designData['product_name'],
                        'product_type' => $designData['product_type']
                    ]);
                }
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'design_id' => $design ? $design->id : null,
                    'product_id' => $item['product_id'],
                    'printful_variant_id' => $item['variant_id'],
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'design_data' => json_encode($designData),
                ]);
                
                \Log::info('Order item created:', [
                    'order_item_id' => $orderItem->id,
                    'design_id' => $orderItem->design_id,
                    'design_data' => $orderItem->design_data
                ]);
            }

            // Create Stripe checkout session
            $orderData = [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'items' => $validatedItems,
                'shipping_address' => $shippingAddress,
                'subtotal' => $total,
                'shipping' => $shipping,
                'tax' => $tax,
                'total' => $grandTotal,
                'currency' => 'USD',
            ];
            
            // Only add design_id if a design is provided
            if ($design) {
                $orderData['design_id'] = $design->id;
            }
            
            \Log::info('Attempting to create Stripe checkout session', ['orderData' => $orderData]);
            $stripeService = new StripeService();
            $successUrl = route('orders.success');
            $cancelUrl = route('orders.create');
            $checkoutSession = $stripeService->createCheckoutSession($orderData, $successUrl, $cancelUrl);
            \Log::info('Stripe checkout session response', ['checkoutSession' => $checkoutSession]);

            if (!$checkoutSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to process payment. Please try again.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'redirect_url' => $checkoutSession['url']
            ]);

        } catch (\Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your order. Please try again.'
            ], 500);
        }
    }

    /**
     * Cancel order due to discontinued variants and process refund
     */
    public function cancelOrderDueToDiscontinuedVariants(Order $order)
    {
        \Log::info('=== cancelOrderDueToDiscontinuedVariants called ===', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'order_user_id' => $order->user_id,
            'order_status' => $order->status
        ]);

        // Check if user owns the order
        if ($order->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'You can only cancel your own orders.');
        }

        // Check if order is in a cancellable state
        if (!in_array($order->status, ['pending', 'paid'])) {
            return redirect()->back()->with('error', 'This order cannot be cancelled in its current state.');
        }

        try {
            // If order was paid, process refund through Stripe
            if ($order->status === 'paid' && $order->stripe_session_id) {
                $refundResult = $this->stripeService->processRefund($order->stripe_session_id, $order->total);
                
                if (!$refundResult['success']) {
                    \Log::error('Failed to process refund for order', [
                        'order_id' => $order->id,
                        'error' => $refundResult['message']
                    ]);
                    return redirect()->back()->with('error', 'Failed to process refund. Please contact support.');
                }
            }

            // Update order status to cancelled
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Discontinued product variants'
            ]);

            \Log::info('Order cancelled due to discontinued variants', [
                'order_id' => $order->id,
                'refund_processed' => $order->status === 'paid'
            ]);

            return redirect()->back()->with('success', 'Order cancelled successfully. ' . 
                ($order->status === 'paid' ? 'Refund has been processed.' : ''));

        } catch (\Exception $e) {
            \Log::error('Error cancelling order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'An error occurred while cancelling the order. Please contact support.');
        }
    }

    /**
     * Manually send an order to Printful
     */
    public function sendToPrintful(Order $order)
    {
        \Log::info('=== sendToPrintful called ===', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'order_user_id' => $order->user_id,
            'order_status' => $order->status,
            'printful_order_id' => $order->printful_order_id
        ]);

        // Check if user owns the order
        if ($order->user_id !== Auth::id()) {
            \Log::warning('User does not own order', [
                'user_id' => Auth::id(),
                'order_user_id' => $order->user_id
            ]);
            return redirect()->back()->with('error', 'You can only send your own orders to Printful.');
        }

        // Check if order is already paid
        if ($order->status !== 'paid') {
            \Log::warning('Order is not paid', ['order_status' => $order->status]);
            return redirect()->back()->with('error', 'Only paid orders can be sent to Printful.');
        }

        // Check if order is already sent to Printful
        if ($order->printful_order_id) {
            \Log::info('Order already sent to Printful', ['printful_order_id' => $order->printful_order_id]);
            return redirect()->back()->with('error', 'This order has already been sent to Printful.');
        }

        try {
            \Log::info('Manually sending order to Printful', ['order_id' => $order->id]);
            
            // Load the design relationship to access design images
            $order->load(['orderItems.design']);
            
            \Log::info('Order items loaded', [
                'order_id' => $order->id,
                'items_count' => $order->orderItems->count(),
                'items' => $order->orderItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'design_id' => $item->design_id,
                        'product_id' => $item->product_id,
                        'design_name' => $item->design ? $item->design->name : 'No design',
                        'design_front_image' => $item->design ? $item->design->front_image_path : 'No front image',
                        'design_back_image' => $item->design ? $item->design->back_image_path : 'No back image',
                        'product_name' => $item->name ?? 'No product',
                        'printful_variant_id' => $item->printful_variant_id
                    ];
                })->toArray()
            ]);
            
            $printfulOrder = $this->createPrintfulOrder($order);
            
            if ($printfulOrder) {
                \Log::info('Printful order created successfully', [
                    'order_id' => $order->id,
                    'printful_order_id' => $printfulOrder['id']
                ]);
                return redirect()->back()->with('success', 'Order successfully sent to Printful! Printful Order ID: ' . $printfulOrder['id']);
            } else {
                // Check if the issue is missing design images
                $hasItemsWithoutDesigns = $order->orderItems->contains(function($item) {
                    return !$item->design_id || !$item->design;
                });
                
                \Log::error('Printful order creation failed', [
                    'order_id' => $order->id,
                    'has_items_without_designs' => $hasItemsWithoutDesigns,
                    'items_details' => $order->orderItems->map(function($item) {
                        return [
                            'id' => $item->id,
                            'design_id' => $item->design_id,
                            'has_design' => $item->design ? 'Yes' : 'No',
                            'design_front_image' => $item->design ? $item->design->front_image_path : 'No image'
                        ];
                    })->toArray()
                ]);
                
                if ($hasItemsWithoutDesigns) {
                    return redirect()->back()->with('error', 'This order cannot be sent to Printful because it contains items without custom designs. Printful requires a design image for each item.');
                } else {
                    // Check if the error is due to regional restrictions
                    // Instead of trying to get last error, we'll provide a general error message
                    // and let the user know they can cancel the order if needed
                    return redirect()->back()->with('error', 'Failed to send order to Printful. This may be due to product availability issues, regional restrictions, or discontinued products. You can cancel this order and create a new one with different products, or contact support for assistance.');
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error manually sending order to Printful: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error sending order to Printful: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order due to regional restrictions and process refund
     */
    public function cancelOrderDueToRegionalRestrictions(Order $order)
    {
        \Log::info('=== cancelOrderDueToRegionalRestrictions called ===', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'order_user_id' => $order->user_id,
            'order_status' => $order->status
        ]);

        // Check if user owns the order
        if ($order->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'You can only cancel your own orders.');
        }

        // Check if order is in a cancellable state
        if (!in_array($order->status, ['pending', 'paid'])) {
            return redirect()->back()->with('error', 'This order cannot be cancelled in its current state.');
        }

        try {
            // If order was paid, process refund through Stripe
            if ($order->status === 'paid' && $order->stripe_session_id) {
                $refundResult = $this->stripeService->processRefund($order->stripe_session_id, $order->total);
                
                if (!$refundResult['success']) {
                    \Log::error('Failed to process refund for order', [
                        'order_id' => $order->id,
                        'error' => $refundResult['message']
                    ]);
                    return redirect()->back()->with('error', 'Failed to process refund. Please contact support.');
                }
            }

            // Update order status to cancelled
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Regional shipping restrictions'
            ]);

            \Log::info('Order cancelled due to regional restrictions', [
                'order_id' => $order->id,
                'refund_processed' => $order->status === 'paid'
            ]);

            return redirect()->back()->with('success', 'Order cancelled successfully due to regional shipping restrictions. ' . 
                ($order->status === 'paid' ? 'Refund has been processed.' : '') . 
                ' You can create a new order with different products that are available in your region.');

        } catch (\Exception $e) {
            \Log::error('Error cancelling order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'An error occurred while cancelling the order. Please contact support.');
        }
    }

    /**
     * Validate product shipping compatibility via API
     */
    public function validateProductShipping(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location' => 'required|string|size:2',
            'shipping_address' => 'sometimes|array'
        ]);

        $product = Product::find($request->product_id);
        $location = $request->location;
        $shippingAddress = $request->shipping_address;

        try {
            $isCompatible = $this->isProductCompatibleWithLocation($product, $location);
            
            // If basic validation passes, do API validation for non-Asia locations
            if ($isCompatible && !in_array($location, ['JP', 'KR', 'SG', 'MY', 'TH', 'VN', 'ID', 'PH'])) {
                $apiValidation = $this->validateProductShippingWithAPI($product, $location, $shippingAddress);
                $isCompatible = $apiValidation;
            }

            return response()->json([
                'success' => true,
                'compatible' => $isCompatible,
                'product_name' => $product->name,
                'location' => $location
            ]);

        } catch (\Exception $e) {
            \Log::error('Product shipping validation failed', [
                'product_id' => $request->product_id,
                'location' => $location,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate shipping compatibility',
                'compatible' => false
            ], 500);
        }
    }

    /**
     * Test Printful API endpoint
     */
    public function testPrintfulApi()
    {
        try {
            \Log::info('Testing Printful API directly');
            
            // Test basic API connection with shorter timeout
            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . config('services.printful.api_key'),
            ])->get('https://api.printful.com/catalog/products');

            \Log::info('Direct API test response', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            return response()->json([
                'status_code' => $response->status(),
                'response_body' => $response->json(),
                'api_key_length' => strlen(config('services.printful.api_key') ?? ''),
                'store_id' => config('services.printful.store_id'),
                'timeout_used' => 15
            ]);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Direct API test connection error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Connection timeout or network error: ' . $e->getMessage(),
                'type' => 'connection_error',
                'api_key_length' => strlen(config('services.printful.api_key') ?? ''),
                'store_id' => config('services.printful.store_id')
            ], 408);
        } catch (\Exception $e) {
            \Log::error('Direct API test error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'type' => 'general_error',
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Test Printful products endpoint
     */
    public function testPrintfulProducts()
    {
        try {
            \Log::info('Testing Printful products directly');
            
            // Test the getTshirtProducts method
            $products = $this->printfulService->getTshirtProducts(5);
            
            \Log::info('Printful products test result', [
                'products_count' => $products->count(),
                'products' => $products->toArray()
            ]);

            return response()->json([
                'success' => true,
                'products_count' => $products->count(),
                'products' => $products->toArray(),
                'api_key_length' => strlen(config('services.printful.api_key') ?? ''),
                'store_id' => config('services.printful.store_id')
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Printful products test error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'type' => 'products_error',
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    private function getProductsForOrder()
    {
        try {
            Log::info('OrderController: About to fetch T-shirt products from Printful API');
            
            // Try to get products from database first
            $products = Product::where('type', 'T-SHIRT')
                ->where('is_active', true)
                ->get();
            
            if ($products->count() > 0) {
                Log::info('OrderController: Found ' . $products->count() . ' T-shirt products in database');
                return $this->formatProductsForOrder($products);
            }
            
            // If no products in database, try Printful API
            Log::info('OrderController: No products in database, trying Printful API');
            $printfulService = new PrintfulService();
            $products = $printfulService->getTshirtProducts(10, 0);
            
            if ($products && $products->count() > 0) {
                Log::info('OrderController: Successfully fetched ' . $products->count() . ' products from Printful API');
                return $products;
            }
            
            // Final fallback: use basic products without any API calls
            Log::warning('OrderController: API failed, using basic T-shirt products');
            return $printfulService->getBasicTshirtProducts(10);
            
        } catch (\Exception $e) {
            Log::error('OrderController: Error fetching products: ' . $e->getMessage());
            
            // Ultimate fallback: basic products
            try {
                $printfulService = new PrintfulService();
                return $printfulService->getBasicTshirtProducts(10);
            } catch (\Exception $fallbackError) {
                Log::error('OrderController: Even fallback failed: ' . $fallbackError->getMessage());
                return collect([]);
            }
        }
    }

    /**
     * Send email notification for failed orders
     */
    private function sendOrderFailedEmail(Order $order, string $reason)
    {
        try {
            \Log::info('Sending order failed email notification', [
                'order_id' => $order->id,
                'user_email' => $order->user->email,
                'reason' => $reason
            ]);
            
            Mail::to($order->user->email)->send(new OrderFailedNotification($order, $reason));
            
            \Log::info('Order failed email sent successfully', [
                'order_id' => $order->id,
                'user_email' => $order->user->email
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send order failed email', [
                'order_id' => $order->id,
                'user_email' => $order->user->email,
                'error' => $e->getMessage()
            ]);
        }
    }
}

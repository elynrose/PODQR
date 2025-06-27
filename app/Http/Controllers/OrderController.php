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
            
            // Get user's preferred location from profile, fallback to request parameter
            $userLocation = auth()->check() ? (auth()->user()->country_code ?? $request->get('location', 'US')) : $request->get('location', 'US');
            
            // Get only T-shirt products, limited to 20 for better performance
            // Note: Printful uses "T-SHIRT" (uppercase) for the type
            $products = Product::where('type', 'T-SHIRT')
                ->where('is_active', true)
                ->orderBy('name')
                ->take(20)
                ->get();

            // Extract unique types, sizes, and colors from products for filters
            $types = $products->pluck('type')->unique()->filter()->values();
            $sizes = collect();
            $colors = collect();
            
            foreach ($products as $product) {
                if ($product->sizes && is_array($product->sizes)) {
                    $sizes = $sizes->merge($product->sizes);
                }
                if ($product->colors && is_array($product->colors)) {
                    // Extract color names from the color objects
                    foreach ($product->colors as $color) {
                        if (is_array($color) && isset($color['color_name'])) {
                            $colors->push($color['color_name']);
                        } elseif (is_string($color)) {
                            $colors->push($color);
                        }
                    }
                }
            }
            
            $sizes = $sizes->unique()->filter()->values();
            $colors = $colors->unique()->filter()->values();

            return view('orders.create', compact('design', 'products', 'userLocation', 'types', 'sizes', 'colors'));
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Design not found - show a friendly error page
            return view('orders.design-not-found', [
                'designId' => $designId,
                'availableDesigns' => Design::take(5)->get(['id', 'name'])
            ]);
        }
    }

    /**
     * Get available products for ordering
     */
    public function getProducts(Request $request)
    {
        $designId = $request->input('design_id');
        $design = $designId ? Design::find($designId) : null;
        
        // Get user's preferred location from profile, fallback to request parameter
        $userLocation = auth()->check() ? (auth()->user()->country_code ?? $request->get('location', 'US')) : $request->get('location', 'US');
        
        $query = Product::where('type', 'T-SHIRT');

        // Filter by design color if design exists
        if ($design && $design->color_code) {
            $query->whereJsonContains('colors', $design->color_code);
        }

        $products = $query->orderBy('name')->take(12)->get();

        return response()->json($products);
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
                $product = Product::find($item['product_id']);
                $orderData['items'][] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'price' => $product->base_price,
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
            'items.*.product_id' => 'required|exists:products,id',
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
                $product = Product::find($item['product_id']);
                $itemTotal = $product->base_price * $item['quantity'];
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'product' => $product,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->base_price,
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
                        'print_file_url' => $design->front_image_path ? asset('storage/' . $design->front_image_path) : null,
                    ];
                    \Log::info('Creating order item with design:', [
                        'design_id' => $design->id,
                        'design_name' => $design->name,
                        'front_image_path' => $design->front_image_path
                    ]);
                } else {
                    $designData = [
                        'product_name' => $item['product']->name ?? 'Unknown Product',
                        'product_type' => $item['product']->type ?? 'Unknown Type',
                    ];
                    \Log::info('Creating order item without design', [
                        'product_name' => $designData['product_name'],
                        'product_type' => $designData['product_type']
                    ]);
                }
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'design_id' => $design ? $design->id : null,
                    'product_id' => $item['product']->id,
                    'printful_variant_id' => $item['product']->printful_id,
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
                $variantId = $item->product->printful_id;
                
                // Prioritize the actual design image (just the artwork) over product image
                $fileUrl = null;
                if ($item->design && $item->design->front_image_path) {
                    // Use the actual design image (just the artwork)
                    $fileUrl = asset('storage/' . $item->design->front_image_path);
                    \Log::info('OrderController: Using design front image', ['file_url' => $fileUrl]);
                } elseif (isset($designData['front_image_path']) && $designData['front_image_path']) {
                    // Fallback to design data if design relationship is not loaded
                    $fileUrl = asset('storage/' . $designData['front_image_path']);
                    \Log::info('OrderController: Using design data front image', ['file_url' => $fileUrl]);
                } elseif (isset($designData['print_file_url']) && $designData['print_file_url']) {
                    // Last fallback to print_file_url if it exists
                    $fileUrl = $designData['print_file_url'];
                    \Log::info('OrderController: Using print file URL', ['file_url' => $fileUrl]);
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
                
                \Log::info('OrderController: Item data', [
                    'variant_id' => $variantId,
                    'file_url' => $fileUrl,
                    'size' => $size,
                    'color' => $color,
                    'quantity' => $item->quantity
                ]);
                
                if (!$variantId) {
                    \Log::error("No Printful variant ID found for product: " . $item->product->id);
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
                
                // Determine the correct file type for T-shirts
                $fileType = 'front'; // Default for front designs
                
                // For T-shirts, we only support front and back
                // The file type is determined by which design image is being used
                if (str_contains($fileUrl, 'back_')) {
                    $fileType = 'back';
                } else {
                    $fileType = 'front';
                }
                
                \Log::info('OrderController: File type for T-shirt', [
                    'product_id' => $item->product->id,
                    'file_url' => $fileUrl,
                    'selected_file_type' => $fileType
                ]);
                
                $options = [
                    'size' => $size,
                    'color' => $color,
                ];
                
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
            ->with(['orderItems.product', 'orderItems.design'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('orders.history', compact('orders'));
    }

    /**
     * Show order details
     */
    public function showOrder($orderId)
    {
        $order = Order::with(['orderItems.product', 'orderItems.design'])
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

        // Update order status to paid
        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
            'stripe_payment_intent_id' => $session->payment_intent,
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
        } else {
            \Log::error('Printful order creation failed');
        }

        \Log::info('=== handleSuccess completed ===');

        return redirect()->route('orders.show', $order->id)
                       ->with('success', 'Order placed successfully! Your order number is: ' . $order->order_number);
    }

    /**
     * Load more products with filters
     */
    public function loadMoreProducts(Request $request)
    {
        $designId = $request->input('design_id');
        $design = $designId ? Design::find($designId) : null;
        
        // Get user's preferred location from profile, fallback to request parameter
        $userLocation = auth()->user()->country_code ?? $request->get('location', 'US');
        
        $query = Product::whereJsonContains('metadata->type', 'T-shirt')
            ->whereJsonContains('metadata->location', $userLocation);

        // Filter by design color if design exists
        if ($design && $design->color_code) {
            $query->whereJsonContains('colors', $design->color_code);
        }

        $products = $query->orderBy('name')
            ->skip($request->input('offset', 0))
            ->take(12)
            ->get();

        return response()->json($products);
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

            // Validate Printful variants and shipping availability before creating order
            $variantValidationErrors = [];
            $shippingValidationErrors = [];
            
            foreach ($items as $index => $item) {
                $product = Product::find($item['product_id']);
                if (!$product || !$product->printful_id) {
                    $variantValidationErrors[] = "Product '{$item['product_name']}' has no valid Printful variant ID.";
                    continue;
                }

                // Check if variant exists and is active in Printful
                try {
                    $variantInfo = $this->printfulService->getVariant($product->printful_id);
                    if (!$variantInfo || isset($variantInfo['error'])) {
                        $variantValidationErrors[] = "Product '{$product->name}' (Variant ID: {$product->printful_id}) is no longer available in Printful.";
                        continue;
                    }
                } catch (\Exception $e) {
                    \Log::warning("Could not validate Printful variant {$product->printful_id}: " . $e->getMessage());
                    $variantValidationErrors[] = "Could not verify availability of '{$product->name}' in Printful.";
                    continue;
                }

                // Check shipping availability
                $shippingValidation = $this->validateProductShippingWithAPI($product, $shippingAddress['country'], $shippingAddress);
                if (!$shippingValidation) {
                    $shippingValidationErrors[] = "Product '{$product->name}': This product has regional shipping restrictions and cannot be shipped to your address.";
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

            // If any products have shipping restrictions, return error
            if (!empty($shippingValidationErrors)) {
                \Log::error('Product shipping validation failed:', $shippingValidationErrors);
                return response()->json([
                    'success' => false,
                    'message' => 'Some products cannot be shipped to your address: ' . implode(' ', $shippingValidationErrors),
                    'errors' => $shippingValidationErrors
                ], 400);
            }

            // Get design if provided
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
            } else {
                \Log::info('No design ID provided');
            }

            // Validate items structure
            foreach ($items as $item) {
                if (!isset($item['product_id']) || !isset($item['size']) || !isset($item['color']) || !isset($item['quantity'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid item data provided.'
                    ], 400);
                }
            }

            // Validate all products are available and have valid Printful IDs
            $orderItems = [];
            $total = 0;
            
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more selected products are no longer available.'
                    ], 400);
                }
                
                if (!$product->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more selected products are currently unavailable.'
                    ], 400);
                }
                
                if (empty($product->printful_id)) {
                    $variantValidationErrors[] = "Product '{$product->name}' has no valid Printful variant ID.";
                    continue;
                }
                
                if (empty($product->base_price) || $product->base_price <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more selected products have invalid pricing.'
                    ], 400);
                }
                
                $itemTotal = $product->base_price * $item['quantity'];
                $total += $itemTotal;
                
                $orderItems[] = [
                    'product' => $product,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->base_price,
                    'total_price' => $itemTotal,
                ];
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
            foreach ($orderItems as $item) {
                $designData = [];
                
                if ($design) {
                    $designData = [
                        'design_id' => $design->id,
                        'design_name' => $design->name,
                        'design_description' => $design->description,
                        'front_image_path' => $design->front_image_path,
                        'back_image_path' => $design->back_image_path,
                        'print_file_url' => $design->front_image_path ? asset('storage/' . $design->front_image_path) : null,
                    ];
                    \Log::info('Creating order item with design:', [
                        'design_id' => $design->id,
                        'design_name' => $design->name,
                        'front_image_path' => $design->front_image_path
                    ]);
                } else {
                    $designData = [
                        'product_name' => $item['product']->name ?? 'Unknown Product',
                        'product_type' => $item['product']->type ?? 'Unknown Type',
                    ];
                    \Log::info('Creating order item without design', [
                        'product_name' => $designData['product_name'],
                        'product_type' => $designData['product_type']
                    ]);
                }
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'design_id' => $design ? $design->id : null,
                    'product_id' => $item['product']->id,
                    'printful_variant_id' => $item['product']->printful_id,
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
                'items' => $orderItems,
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
            $order->load(['orderItems.product', 'orderItems.design']);
            
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
                        'product_name' => $item->product ? $item->product->name : 'No product',
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
}

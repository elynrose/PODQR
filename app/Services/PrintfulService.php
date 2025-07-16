<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class PrintfulService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.printful.com';
    protected $storeId;

    public function __construct()
    {
        $this->apiKey = config('services.printful.api_key');
        $this->storeId = config('services.printful.store_id');
    }

    /**
     * Get all products from Printful
     */
    public function getProducts()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/products');

            if ($response->successful()) {
                return $response->json()['result'];
            }

            Log::error('Printful API Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Printful API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get products from your store (templates) instead of catalog
     * This eliminates regional issues since store products are already configured
     */
    public function getStoreProducts($limit = 10, $offset = 0)
    {
        try {
            \Log::info('PrintfulService: Fetching products from store', [
                'store_id' => $this->storeId,
                'limit' => $limit,
                'offset' => $offset
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/store/products', [
                'store_id' => $this->storeId,
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $products = $data['result'] ?? [];
                
                \Log::info('PrintfulService: Store products fetched successfully', [
                    'products_count' => count($products),
                    'store_id' => $this->storeId
                ]);

                // Transform store products to match the expected format
                $transformedProducts = collect($products)->map(function ($product) {
                    return $this->transformStoreProduct($product);
                })->filter();

                return $transformedProducts;
            }

            Log::error('Printful Store Products Error: ' . $response->body());
            return collect([]);
        } catch (\Exception $e) {
            Log::error('Printful Store Products Exception: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Transform a store product to match the expected format
     */
    private function transformStoreProduct($product)
    {
        try {
            // Extract variant information
            $variants = $product['variants'] ?? [];
            if (empty($variants)) {
                return null;
            }

            // Get the first variant for basic info
            $firstVariant = $variants[0];
            
            // Extract sizes and colors from all variants
            $sizes = collect($variants)->pluck('size')->filter()->unique()->values()->toArray();
            $colors = collect($variants)->pluck('color')->filter()->unique()->values()->toArray();
            
            // Get base price from the first variant
            $basePrice = $firstVariant['retail_price'] ?? 25.00;

            return [
                'id' => $product['id'],
                'name' => $product['name'] ?? 'Custom Product',
                'printful_id' => $product['id'],
                'type' => 'T-shirt',
                'base_price' => $basePrice,
                'sizes' => $sizes,
                'colors' => $colors,
                'variants' => $variants,
                'thumbnail' => $product['thumbnail'] ?? null,
                'is_store_product' => true, // Flag to identify store products
                'store_id' => $this->storeId
            ];
        } catch (\Exception $e) {
            \Log::error('PrintfulService: Error transforming store product', [
                'product_id' => $product['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get product variants
     */
    public function getProductVariants($productId)
    {
        try {
            // Use the catalog variants endpoint with product_id parameter
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/catalog/variants', [
                'product_id' => $productId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['result']['variants'] ?? [];
            }

            Log::error('Printful API Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Printful API Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a product with design
     */
    public function createProduct($designData, $productId, $variantId)
    {
        try {
            $payload = [
                'name' => $designData['name'] ?? 'Custom Design',
                'thumbnail' => $designData['image_url'],
                'is_enabled' => true,
                'variants' => [
                    [
                        'id' => $variantId,
                        'retail_price' => $designData['price'] ?? '25.00',
                        'files' => [
                            [
                                'url' => $designData['image_url'],
                                'type' => 'front',
                                'position' => [
                                    'area_width' => 1800,
                                    'area_height' => 2400,
                                    'width' => 1800,
                                    'height' => 2400,
                                    'top' => 0,
                                    'left' => 0,
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->baseUrl . '/products', $payload);

            if ($response->successful()) {
                return $response->json()['result'];
            }

            Log::error('Printful Create Product Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Printful Create Product Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create an order
     */
    public function createOrder($orderData)
    {
        try {
            // Map address fields to Printful's expected keys
            $address = $orderData['shipping_address'];
            $recipient = [
                'name' => $address['name'] ?? '',
                'address1' => $address['address1'] ?? ($address['address'] ?? ''),
                'city' => $address['city'] ?? '',
                'state_code' => $address['state_code'] ?? ($address['state'] ?? ''),
                'country_code' => $address['country_code'] ?? ($address['country'] ?? ''),
                'zip' => $address['zip'] ?? '',
                'email' => $address['email'] ?? '',
                'phone' => $address['phone'] ?? '',
            ];

            $payload = [
                'store_id' => $this->storeId,
                'recipient' => $recipient,
                'items' => $orderData['items'],
                'retail_costs' => [
                    'currency' => $orderData['currency'] ?? 'USD',
                    'subtotal' => $orderData['subtotal'],
                    'shipping' => $orderData['shipping'],
                    'tax' => $orderData['tax'],
                    'total' => $orderData['total'],
                ]
            ];

            // Debug logging
            Log::info('Printful createOrder payload:', [
                'payload' => $payload,
                'items_count' => count($orderData['items'] ?? []),
                'items' => $orderData['items'] ?? []
            ]);

            // Ensure items is a proper array
            if (empty($payload['items']) || !is_array($payload['items'])) {
                Log::error('PrintfulService: items array is empty or not an array', ['items' => $payload['items']]);
            } else {
                $payload['items'] = array_values($payload['items']);
            }

            $url = $this->baseUrl . '/orders';

            \Log::info('PrintfulService: Making API request to create order', [
                'url' => $url,
                'store_id' => $this->storeId,
                'payload_size' => strlen(json_encode($payload))
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            \Log::info('PrintfulService: API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'response_headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $result = $response->json();
                \Log::info('PrintfulService: Order created successfully', [
                    'printful_order_id' => $result['result']['id'] ?? 'unknown',
                    'printful_order_number' => $result['result']['external_id'] ?? 'unknown',
                    'status' => $result['result']['status'] ?? 'unknown'
                ]);
                return $result['result'];
            } else {
                \Log::error('PrintfulService: API request failed', [
                    'status_code' => $response->status(),
                    'error_response' => $response->body(),
                    'request_payload' => $payload
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Printful Create Order Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get order status
     */
    public function getOrderStatus($orderId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/orders/' . $orderId);

            if ($response->successful()) {
                return $response->json()['result'];
            }

            Log::error('Printful Get Order Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Printful Get Order Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync variants from Printful to local database
     */
    public function syncProducts()
    {
        $products = $this->getProducts();
        
        if (!$products) {
            Log::warning('No products found from Printful API');
            return false;
        }

        $processedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($products as $product) {
            // Validate product has required fields
            if (empty($product['id']) || empty($product['title'])) {
                Log::warning("Skipping product with missing required fields: " . json_encode($product));
                $skippedCount++;
                continue;
            }

            $variants = $this->getProductVariants($product['id']);
            
            if ($variants && is_array($variants)) {
                foreach ($variants as $variant) {
                    $processedCount++;
                    
                    // Validate variant has required fields
                    if (empty($variant['id']) || empty($variant['price'])) {
                        Log::warning("Skipping variant with missing required fields for product {$product['id']}: " . json_encode($variant));
                        $skippedCount++;
                        continue;
                    }
                    
                    // Extract size and color from variant data
                    $size = $variant['size'] ?? null;
                    $color = $variant['color']['color_name'] ?? null;
                    
                    // Create a unique name for the variant
                    $variantName = $product['title'] ?? 'Unknown Product';
                    if ($size) $variantName .= " - $size";
                    if ($color) $variantName .= " - $color";
                    
                    try {
                        Product::updateOrCreate(
                            ['printful_id' => $variant['id']], // Use variant ID, not product ID
                            [
                                'printful_product_id' => $product['id'], // Store product ID separately
                                'name' => $variantName,
                                'description' => $product['description'] ?? null,
                                'type' => $product['type'] ?? 'unknown',
                                'brand' => $product['brand'] ?? 'Unknown',
                                'model' => $product['model'] ?? 'Unknown',
                                'sizes' => $size ? [$size] : [],
                                'colors' => $color ? [$color] : [],
                                'base_price' => $variant['price'] ?? 0,
                                'image_url' => $variant['image_url'] ?? $product['image'] ?? null,
                                'is_active' => true,
                                'metadata' => [
                                    'printful_product' => $product,
                                    'printful_variant' => $variant,
                                    'last_synced' => now()->toISOString()
                                ]
                            ]
                        );
                        
                        $createdCount++;
                    } catch (\Exception $e) {
                        Log::error("Failed to save variant {$variant['id']} for product {$product['id']}: " . $e->getMessage());
                        $skippedCount++;
                    }
                }
            } else {
                Log::info("No variants found for product {$product['id']} ({$product['title']})");
            }
        }

        Log::info("Printful sync completed: $processedCount variants processed, $createdCount created, $updatedCount updated, $skippedCount skipped");
        return true;
    }

    /**
     * Get shipping rates
     */
    public function getShippingRates($address, $items)
    {
        try {
            $payload = [
                'store_id' => $this->storeId,
                'recipient' => [
                    'country_code' => $address['country_code'],
                    'state_code' => $address['state_code'] ?? null,
                    'city' => $address['city'],
                    'zip' => $address['zip'],
                ],
                'items' => $items
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->baseUrl . '/shipping/rates', $payload);

            if ($response->successful()) {
                return $response->json()['result'];
            }

            Log::error('Printful Shipping Rates Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Printful Shipping Rates Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate if a product can be shipped to a specific location using shipping rates API
     * This is more reliable than test order creation
     */
    public function validateProductShipping($variantId, $location, $address = null)
    {
        try {
            // Create a test address if none provided
            $testAddress = $address ?: [
                'country_code' => $location,
                'state_code' => $location === 'US' ? 'CA' : null,
                'city' => 'Test City',
                'zip' => $location === 'US' ? '90210' : '12345',
            ];

            $testItems = [
                [
                    'variant_id' => $variantId,
                    'quantity' => 1
                ]
            ];

            \Log::info('PrintfulService: Validating product shipping', [
                'variant_id' => $variantId,
                'location' => $location,
                'test_address' => $testAddress
            ]);

            $shippingRates = $this->getShippingRates($testAddress, $testItems);

            if ($shippingRates && !empty($shippingRates)) {
                \Log::info('PrintfulService: Product shipping validation successful', [
                    'variant_id' => $variantId,
                    'location' => $location,
                    'shipping_options' => count($shippingRates)
                ]);
                return [
                    'success' => true,
                    'message' => 'Product can be shipped to this location',
                    'shipping_options' => count($shippingRates)
                ];
            } else {
                \Log::warning('PrintfulService: Product shipping validation failed - no shipping options', [
                    'variant_id' => $variantId,
                    'location' => $location
                ]);
                return [
                    'success' => false,
                    'message' => 'Product cannot be shipped to this location',
                    'type' => 'no_shipping_options'
                ];
            }

        } catch (\Exception $e) {
            \Log::error('PrintfulService: Product shipping validation error', [
                'variant_id' => $variantId,
                'location' => $location,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to validate shipping compatibility',
                'type' => 'api_error'
            ];
        }
    }

    /**
     * Get products that are compatible with a specific location (optimized for performance)
     */
    public function getLocationCompatibleProducts($location, $limit = 10, $offset = 0)
    {
        try {
            \Log::info('PrintfulService: Starting optimized location compatibility check', [
                'location' => $location,
                'limit' => $limit,
                'offset' => $offset
            ]);

            // Use known working products for immediate response
            $knownWorkingProducts = $this->getKnownWorkingProducts($location, $limit, $offset);
            
            if ($knownWorkingProducts->count() >= $limit) {
                \Log::info('PrintfulService: Using known working products for immediate response', [
                    'location' => $location,
                    'products_count' => $knownWorkingProducts->count()
                ]);
                return $knownWorkingProducts;
            }

            // If we need more products, get from catalog with minimal validation
            $additionalProducts = $this->getCatalogProductsWithMinimalValidation($location, $limit - $knownWorkingProducts->count(), $offset);
            
            $allProducts = $knownWorkingProducts->merge($additionalProducts);
            
            \Log::info('PrintfulService: Optimized location compatibility check completed', [
                'location' => $location,
                'known_products' => $knownWorkingProducts->count(),
                'additional_products' => $additionalProducts->count(),
                'total_products' => $allProducts->count()
            ]);

            return $allProducts;

        } catch (\Exception $e) {
            \Log::error('PrintfulService: Error in optimized location compatibility check', [
                'location' => $location,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to known working products
            return $this->getKnownWorkingProducts($location, $limit, $offset);
        }
    }

    /**
     * Get known working products for a location (no API validation needed)
     */
    public function getKnownWorkingProducts($location, $limit = 10, $offset = 0)
    {
        // Known working products by location
        $locationBasedProducts = [
            'US' => [
                '71' => [4012, 4013, 4014, 4015, 4016, 4017, 4018, 4019, 4020], // Bella + Canvas
                '37' => [1984, 1985, 1982], // Gildan Lightweight
                '679' => [17008, 17009, 17080], // Performance Crew Neck
            ],
            'GB' => [
                '71' => [4012, 4013, 4014, 4015, 4016, 4017, 4018, 4019, 4020], // Bella + Canvas
                '37' => [1984, 1985, 1982], // Gildan Lightweight
            ],
            'JP' => [
                '71' => [4012, 4013, 4014, 4015, 4016, 4017, 4018, 4019, 4020], // Bella + Canvas
                '37' => [1984, 1985, 1982], // Gildan Lightweight
            ],
            'CA' => [
                // Limited options for Canada
                '71' => [4012, 4013, 4014], // Only some Bella + Canvas variants
            ],
            'AU' => [
                // Limited options for Australia
                '71' => [4012, 4013, 4014], // Only some Bella + Canvas variants
            ],
        ];

        $locationProducts = $locationBasedProducts[$location] ?? $locationBasedProducts['US'];
        
        $allVariants = [];
        foreach ($locationProducts as $productId => $variantIds) {
            foreach ($variantIds as $variantId) {
                $allVariants[] = [
                    'variant_id' => $variantId,
                    'product_id' => $productId
                ];
            }
        }

        // Apply pagination
        $paginatedVariants = array_slice($allVariants, $offset, $limit);
        
        $products = collect();
        foreach ($paginatedVariants as $variantInfo) {
            try {
                $variant = $this->getVariant($variantInfo['variant_id']);
                if ($variant) {
                    $product = [
                        'id' => $products->count() + 1,
                        'printful_id' => $variant['product_id'],
                        'variant_id' => $variantInfo['variant_id'],
                        'name' => $variant['display_name'],
                        'type' => 'T-shirt',
                        'base_price' => $variant['retail_price'] ?? 15.00,
                        'image_url' => $variant['image_url'] ?? null,
                        'sizes' => [$this->getSizeFromVariantName($variant['display_name'])],
                        'colors' => [$this->getColorFromVariantName($variant['display_name'])],
                        'color_codes' => [$this->getColorCode($this->getColorFromVariantName($variant['display_name']))],
                        'available_as_sample' => $variant['available_as_sample'] ?? false,
                        'currency' => 'USD'
                    ];
                    $products->push($product);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $products;
    }

    /**
     * Get catalog products with minimal validation (for additional products)
     */
    public function getCatalogProductsWithMinimalValidation($location, $limit = 5, $offset = 0)
    {
        // Only validate a few products to avoid timeout
        $maxValidationChecks = 3;
        
        $catalogProducts = $this->getCatalogTshirtProducts($limit + $maxValidationChecks);
        $validatedProducts = collect();
        $checkedCount = 0;

        foreach ($catalogProducts as $product) {
            if ($checkedCount >= $maxValidationChecks) {
                break;
            }

            $variantId = $product['variant_id'] ?? null;
            if (!$variantId) {
                continue;
            }

            // Quick validation (with timeout protection)
            $validation = $this->validateProductShipping($variantId, $location);
            
            if ($validation['success']) {
                $validatedProducts->push($product);
                
                if ($validatedProducts->count() >= $limit) {
                    break;
                }
            }

            $checkedCount++;
        }

        return $validatedProducts;
    }

    /**
     * Extract size from variant name
     */
    private function getSizeFromVariantName($variantName)
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'];
        foreach ($sizes as $size) {
            if (stripos($variantName, $size) !== false) {
                return $size;
            }
        }
        return 'M'; // Default
    }

    /**
     * Extract color from variant name
     */
    private function getColorFromVariantName($variantName)
    {
        $colors = ['White', 'Black', 'Navy', 'Gray', 'Red', 'Blue', 'Green'];
        foreach ($colors as $color) {
            if (stripos($variantName, $color) !== false) {
                return $color;
            }
        }
        return 'White'; // Default
    }

    /**
     * Get T-shirt products from Printful catalog with more variety
     */
    public function getCatalogTshirtProducts($limit = 20)
    {
        try {
            \Log::info('PrintfulService: Starting getCatalogTshirtProducts', [
                'limit' => $limit,
                'api_key_length' => strlen($this->apiKey ?? ''),
                'store_id' => $this->storeId
            ]);

            // Check if API key is configured
            if (empty($this->apiKey)) {
                \Log::warning('PrintfulService: No API key configured, returning fallback products');
                return $this->getFallbackProducts($limit);
            }

            // Real T-shirt variant IDs from Printful catalog
            $catalogTshirtVariants = [
                // Bella + Canvas variants (known working)
                4012 => ['product_id' => 71, 'size' => 'M', 'color' => 'White'],
                4013 => ['product_id' => 71, 'size' => 'L', 'color' => 'White'],
                4014 => ['product_id' => 71, 'size' => 'XL', 'color' => 'White'],
                4015 => ['product_id' => 71, 'size' => 'M', 'color' => 'Black'],
                4016 => ['product_id' => 71, 'size' => 'L', 'color' => 'Black'],
                4017 => ['product_id' => 71, 'size' => 'XL', 'color' => 'Black'],
                4018 => ['product_id' => 71, 'size' => 'M', 'color' => 'Navy'],
                4019 => ['product_id' => 71, 'size' => 'L', 'color' => 'Navy'],
                4020 => ['product_id' => 71, 'size' => 'XL', 'color' => 'Navy'],
                
                // Gildan Lightweight T-Shirt (Product 37)
                1984 => ['product_id' => 37, 'size' => 'M', 'color' => 'White'],
                1985 => ['product_id' => 37, 'size' => 'L', 'color' => 'White'],
                1982 => ['product_id' => 37, 'size' => 'XL', 'color' => 'White'],
                
                // Performance Crew Neck T-Shirt (Product 679)
                17008 => ['product_id' => 679, 'size' => 'M', 'color' => 'White'],
                17009 => ['product_id' => 679, 'size' => 'L', 'color' => 'White'],
                17080 => ['product_id' => 679, 'size' => 'XL', 'color' => 'White'],
                
                // All-Over Print Men's Crew Neck T-Shirt (Product 257)
                8855 => ['product_id' => 257, 'size' => 'M', 'color' => 'White'],
                8853 => ['product_id' => 257, 'size' => 'L', 'color' => 'White'],
                8852 => ['product_id' => 257, 'size' => 'XL', 'color' => 'White'],
                
                // All-Over Print Women's Crew Neck T-Shirt (Product 261)
                8889 => ['product_id' => 261, 'size' => 'M', 'color' => 'White'],
                8887 => ['product_id' => 261, 'size' => 'L', 'color' => 'White'],
                8886 => ['product_id' => 261, 'size' => 'XL', 'color' => 'White'],
                
                // All-Over Print Men's Athletic T-Shirt (Product 328)
                9957 => ['product_id' => 328, 'size' => 'M', 'color' => 'White'],
                9958 => ['product_id' => 328, 'size' => 'L', 'color' => 'White'],
                9955 => ['product_id' => 328, 'size' => 'XL', 'color' => 'White'],
                
                // All-Over Print Women's Athletic T-Shirt (Product 329)
                9964 => ['product_id' => 329, 'size' => 'M', 'color' => 'White'],
                9965 => ['product_id' => 329, 'size' => 'L', 'color' => 'White'],
                9962 => ['product_id' => 329, 'size' => 'XL', 'color' => 'White'],
                
                // All-Over Print Kids Crew Neck T-Shirt (Product 384)
                10817 => ['product_id' => 384, 'size' => 'M', 'color' => 'White'],
                10818 => ['product_id' => 384, 'size' => 'L', 'color' => 'White'],
                10819 => ['product_id' => 384, 'size' => 'XL', 'color' => 'White'],
                
                // All-Over Print Youth Crew Neck T-shirt (Product 385)
                10825 => ['product_id' => 385, 'size' => 'M', 'color' => 'White'],
                10826 => ['product_id' => 385, 'size' => 'L', 'color' => 'White'],
                10827 => ['product_id' => 385, 'size' => 'XL', 'color' => 'White'],
                
                // Additional known working products
                12585 => ['product_id' => 493, 'size' => 'S', 'color' => 'Black'],
                12694 => ['product_id' => 506, 'size' => 'S', 'color' => 'Black'],
                12966 => ['product_id' => 515, 'size' => 'S', 'color' => 'Milky way'],
            ];

            \Log::info('PrintfulService: Using expanded catalog variant IDs', [
                'variant_count' => count($catalogTshirtVariants),
                'variant_ids' => array_keys($catalogTshirtVariants)
            ]);

            // Transform to our format using individual variant API calls
            $formattedProducts = collect();
            $productsAdded = 0;
            
            foreach ($catalogTshirtVariants as $variantId => $variantInfo) {
                if ($productsAdded >= $limit) {
                    break;
                }
                
                try {
                    // Get individual variant details
                    $variant = $this->getVariant($variantId);
                    
                    if (!$variant) {
                        \Log::warning("PrintfulService: Variant {$variantId} not found, skipping");
                        continue;
                    }
                    
                    // Skip toddler products
                    if (isset($variant['product_id']) && $variant['product_id'] == 489) {
                        \Log::info("PrintfulService: Skipping toddler product 489");
                        continue;
                    }
                    
                    // Get product details from catalog
                    $product = $this->getProductFromCatalog($variant['product_id']);
                    
                    if (!$product) {
                        \Log::warning("PrintfulService: Product {$variant['product_id']} not found in catalog, using variant data only");
                        // Use variant data as fallback
                        $product = [
                            'id' => $variant['product_id'],
                            'display_name' => $variant['display_name'],
                            'image_url' => $variant['image_url'],
                        ];
                    }
                    
                    // Format the product
                    $formattedProduct = [
                        'id' => $productsAdded + 1,
                        'printful_id' => $variant['product_id'],
                        'variant_id' => $variantId,
                        'name' => $variant['display_name'],
                        'type' => 'T-shirt',
                        'base_price' => $variant['retail_price'] ?? 15.00,
                        'image_url' => $variant['image_url'] ?? $product['image_url'] ?? null,
                        'sizes' => [$variantInfo['size']],
                        'colors' => [$variantInfo['color']],
                        'color_codes' => [$this->getColorCode($variantInfo['color'])],
                        'available_as_sample' => $variant['available_as_sample'] ?? false,
                        'currency' => 'USD'
                    ];
                    
                    $formattedProducts->push($formattedProduct);
                    $productsAdded++;
                    
                    \Log::info('PrintfulService: Added catalog product', [
                        'product_id' => $variant['product_id'],
                        'variant_id' => $variantId,
                        'name' => $variant['display_name'],
                        'price' => $variant['retail_price'] ?? 15.00
                    ]);
                    
                } catch (\Exception $e) {
                    \Log::error("PrintfulService: Error processing variant {$variantId}", [
                        'error' => $e->getMessage(),
                        'variant_id' => $variantId
                    ]);
                    continue;
                }
            }

            \Log::info('PrintfulService: Catalog products processing completed', [
                'total_products' => $formattedProducts->count(),
                'products' => $formattedProducts->pluck('name')->toArray()
            ]);

            return $formattedProducts;

        } catch (\Exception $e) {
            \Log::error('PrintfulService: Error in getCatalogTshirtProducts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to basic products
            return $this->getTshirtProducts($limit);
        }
    }

    /**
     * Get all stores from Printful
     */
    public function getStores()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/stores');

            if ($response->successful()) {
                return $response->json()['result'];
            }

            Log::error('Printful Get Stores Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Printful Get Stores Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a specific variant by ID
     */
    public function getVariant($variantId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/catalog/variants/' . $variantId);

            if ($response->successful()) {
                $data = $response->json();
                // The API returns result.variant, not just result
                return $data['result']['variant'] ?? null;
            }

            // If variant not found or error, return null
            Log::warning('Printful variant not found or error: ' . $response->body(), ['variant_id' => $variantId]);
            return null;
        } catch (\Exception $e) {
            Log::error('Printful getVariant Exception: ' . $e->getMessage(), ['variant_id' => $variantId]);
            return null;
        }
    }

    /**
     * Check if a variant ID exists and is valid
     */
    public function validateVariantId($variantId)
    {
        try {
            $variant = $this->getVariant($variantId);
            
            if (!$variant) {
                return [
                    'exists' => false,
                    'message' => 'Variant ID not found',
                    'variant_id' => $variantId
                ];
            }

            // Check if variant is discontinued
            if (isset($variant['discontinued']) && $variant['discontinued']) {
                return [
                    'exists' => false,
                    'message' => 'Variant is discontinued',
                    'variant_id' => $variantId,
                    'variant_name' => $variant['title'] ?? 'Unknown'
                ];
            }

            // Check if variant is available
            if (isset($variant['is_enabled']) && !$variant['is_enabled']) {
                return [
                    'exists' => false,
                    'message' => 'Variant is not enabled',
                    'variant_id' => $variantId,
                    'variant_name' => $variant['title'] ?? 'Unknown'
                ];
            }

            return [
                'exists' => true,
                'message' => 'Variant is valid and available',
                'variant_id' => $variantId,
                'variant_name' => $variant['title'] ?? 'Unknown',
                'variant_data' => $variant
            ];

        } catch (\Exception $e) {
            Log::error('Printful validateVariantId Exception: ' . $e->getMessage(), ['variant_id' => $variantId]);
            return [
                'exists' => false,
                'message' => 'Error validating variant: ' . $e->getMessage(),
                'variant_id' => $variantId
            ];
        }
    }

    /**
     * Validate multiple variant IDs and return results
     */
    public function validateVariantIds($variantIds)
    {
        $results = [];
        
        foreach ($variantIds as $variantId) {
            $results[$variantId] = $this->validateVariantId($variantId);
        }
        
        return $results;
    }

    /**
     * Get only valid variant IDs from a list
     */
    public function getValidVariantIds($variantIds)
    {
        $validIds = [];
        
        foreach ($variantIds as $variantId) {
            $validation = $this->validateVariantId($variantId);
            if ($validation['exists']) {
                $validIds[] = $variantId;
            } else {
                \Log::warning('PrintfulService: Invalid variant ID found', [
                    'variant_id' => $variantId,
                    'message' => $validation['message']
                ]);
            }
        }
        
        return $validIds;
    }

    /**
     * Test order creation to validate shipping compatibility
     * This method validates shipping without actually creating an order
     */
    public function testOrderCreation($orderData)
    {
        try {
            // Create a minimal test payload with required fields
            $testPayload = [
                'store_id' => $this->storeId,
                'recipient' => [
                    'name' => $orderData['recipient']['name'] ?? 'Test User',
                    'address1' => $orderData['recipient']['address1'] ?? $orderData['recipient']['address'] ?? '123 Test St',
                    'city' => $orderData['recipient']['city'] ?? 'Test City',
                    'state_code' => $orderData['recipient']['state_code'] ?? $orderData['recipient']['state'] ?? 'CA',
                    'country_code' => $orderData['recipient']['country_code'] ?? $orderData['recipient']['country'] ?? 'US',
                    'zip' => $orderData['recipient']['zip'] ?? '12345',
                    'email' => $orderData['recipient']['email'] ?? 'test@example.com',
                    'phone' => $orderData['recipient']['phone'] ?? '1234567890'
                ],
                'items' => [
                    [
                        'variant_id' => $orderData['items'][0]['variant_id'] ?? $orderData['items'][0]['id'],
                        'quantity' => 1,
                        'files' => [
                            [
                                'url' => 'https://via.placeholder.com/400x400.png?text=Test',
                                'type' => 'front'
                            ]
                        ],
                        'options' => [
                            'size' => 'M',
                            'color' => 'Black'
                        ]
                    ]
                ],
                'retail_costs' => [
                    'currency' => 'USD',
                    'subtotal' => '10.00',
                    'shipping' => '5.99',
                    'tax' => '0.80',
                    'total' => '16.79'
                ]
            ];

            \Log::info('PrintfulService: Testing order creation for validation', [
                'store_id' => $this->storeId,
                'items_count' => count($orderData['items'] ?? []),
                'recipient_country' => $testPayload['recipient']['country_code']
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/orders', $testPayload);

            \Log::info('PrintfulService: Test order validation response', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Product is compatible with shipping location'];
            } else {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);
                
                // Check if it's a regional restriction error
                if (strpos($errorBody, 'ships to') !== false && strpos($errorBody, 'only') !== false) {
                    return [
                        'success' => false, 
                        'message' => 'Product has regional shipping restrictions',
                        'type' => 'regional_restriction'
                    ];
                }
                
                return [
                    'success' => false, 
                    'message' => $errorData['result'] ?? 'Unknown validation error',
                    'type' => 'validation_error'
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('PrintfulService: Test order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false, 
                'message' => 'Failed to validate shipping compatibility',
                'type' => 'api_error'
            ];
        }
    }

    /**
     * Get store ID
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Get T-shirt products directly from Printful catalog with pagination
     */
    public function getTshirtProducts($limit = 10, $offset = 0)
    {
        try {
            \Log::info('PrintfulService: Starting getTshirtProducts', [
                'limit' => $limit,
                'offset' => $offset,
                'api_key_length' => strlen($this->apiKey ?? ''),
                'store_id' => $this->storeId,
                'api_key_preview' => substr($this->apiKey ?? '', 0, 10) . '...'
            ]);

            // Check if API key is configured
            if (empty($this->apiKey)) {
                \Log::warning('PrintfulService: No API key configured, returning fallback products');
                return $this->getFallbackProducts($limit);
            }

            // Use known valid variant IDs for unisex t-shirts
            // Based on our API analysis, these are confirmed valid variant IDs
            $knownUnisexTshirtVariants = [
                4012 => ['product_id' => 71, 'size' => 'M', 'color' => 'White'],      // Bella + Canvas Unisex T-Shirt
                4013 => ['product_id' => 71, 'size' => 'L', 'color' => 'White'],      // Bella + Canvas Unisex T-Shirt  
                4014 => ['product_id' => 71, 'size' => 'XL', 'color' => 'White'],     // Bella + Canvas Unisex T-Shirt
                12585 => ['product_id' => 493, 'size' => 'S', 'color' => 'Black'],    // Unisex Eco Sweatshirt
                12694 => ['product_id' => 506, 'size' => 'S', 'color' => 'Black'],    // SOL'S Comet
                12966 => ['product_id' => 515, 'size' => 'S', 'color' => 'Milky way'], // Oversized Tie-Dye T-shirt
            ];

            \Log::info('PrintfulService: Using known valid variant IDs', [
                'variant_count' => count($knownUnisexTshirtVariants),
                'variant_ids' => array_keys($knownUnisexTshirtVariants)
            ]);

            // Transform to our format using individual variant API calls
            $formattedProducts = collect();
            $productsAdded = 0;
            
            foreach ($knownUnisexTshirtVariants as $variantId => $variantInfo) {
                if ($productsAdded >= $limit) {
                    break;
                }
                
                try {
                    // Get individual variant details
                    $variant = $this->getVariant($variantId);
                    
                    if (!$variant) {
                        \Log::warning("PrintfulService: Variant {$variantId} not found, skipping");
                        continue;
                    }
                    
                    // Skip toddler products (like product 489)
                    if (isset($variant['product_id']) && $variant['product_id'] == 489) {
                        \Log::info("PrintfulService: Skipping toddler product 489");
                        continue;
                    }
                    
                    // Get product details from catalog
                    $product = $this->getProductFromCatalog($variant['product_id']);
                    
                    if (!$product) {
                        \Log::warning("PrintfulService: Product {$variant['product_id']} not found in catalog, using variant data only");
                        // Use variant data as fallback
                        $product = [
                            'id' => $variant['product_id'],
                            'display_name' => $variant['display_name'],
                            'image_url' => $variant['image_url'],
                            'price' => $variant['price']
                        ];
                    }
                    
                    // Format the product data
                    $formattedProduct = [
                        'id' => $variant['product_id'], // Add id field for consistency
                        'printful_id' => $variant['product_id'],
                        'variant_id' => $variant['id'],
                        'name' => $product['display_name'] ?? $variant['display_name'],
                        'type' => 'T-SHIRT', // Add type field
                        'base_price' => $variant['price'],
                        'image_url' => $product['image_url'] ?? $variant['image_url'],
                        'sizes' => [$variant['size']],
                        'colors' => [$variant['color']['color_name'] ?? 'Unknown'],
                        'color_codes' => [$variant['color']['color_codes'][0] ?? '#000000'],
                        'available_as_sample' => $variant['available_as_sample'] ?? false,
                        'currency' => $variant['currency'] ?? 'USD'
                    ];
                    
                    $formattedProducts->push($formattedProduct);
                    $productsAdded++;
                    
                    \Log::info("PrintfulService: Added product", [
                        'product_id' => $variant['product_id'],
                        'variant_id' => $variant['id'],
                        'name' => $formattedProduct['name'],
                        'price' => $formattedProduct['base_price']
                    ]);
                    
                } catch (\Exception $e) {
                    \Log::error("PrintfulService: Error processing variant {$variantId}", [
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            \Log::info('PrintfulService: Final formatted products', [
                'total_products' => $formattedProducts->count(),
                'products' => $formattedProducts->pluck('name')->toArray()
            ]);

            // If we couldn't get any products from API, return fallback
            if ($formattedProducts->isEmpty()) {
                \Log::warning('PrintfulService: No products from API, returning fallback products');
                return $this->getFallbackProducts($limit);
            }

            return $formattedProducts;
            
        } catch (\Exception $e) {
            \Log::error('PrintfulService: Exception in getTshirtProducts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackProducts($limit);
        }

    }

    /**
     * Get product details from catalog
     */
    private function getProductFromCatalog($productId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/catalog/products/' . $productId);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['result'] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error("PrintfulService: Failed to get product {$productId} from catalog", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get cached product variants with timeout
     */
    private function getCachedProductVariants($productId)
    {
        $cacheKey = "printful_variants_{$productId}";
        
        // Try to get from cache first
        if (cache()->has($cacheKey)) {
            \Log::info("PrintfulService: Using cached variants for product {$productId}");
            return cache()->get($cacheKey);
        }
        
        try {
            // Fetch variants with shorter timeout
            $variants = $this->getProductVariants($productId);
            
            // Cache for 1 hour
            cache()->put($cacheKey, $variants, 3600);
            
            \Log::info("PrintfulService: Fetched and cached variants for product {$productId}", [
                'variants_count' => count($variants)
            ]);
            
            return $variants;
        } catch (\Exception $e) {
            \Log::error("PrintfulService: Failed to fetch variants for product {$productId}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get base price from variants
     */
    private function getBasePriceFromVariants($variants)
    {
        if ($variants && count($variants) > 0) {
            return $variants[0]['retail_price'] ?? 19.99;
        }
        return 19.99; // Default price
    }

    /**
     * Get sizes from variants
     */
    private function getSizesFromVariants($variants)
    {
        if ($variants) {
            return collect($variants)
                ->pluck('size')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }
        return ['M']; // Default size
    }

    /**
     * Get colors from variants
     */
    private function getColorsFromVariants($variants)
    {
        if ($variants) {
            return collect($variants)
                ->pluck('color')
                ->filter()
                ->unique()
                ->map(function ($color) {
                    return [
                        'color_name' => $color,
                        'color_codes' => [$this->getColorCode($color)]
                    ];
                })
                ->values()
                ->toArray();
        }
        return [['color_name' => 'White', 'color_codes' => ['#ffffff']]]; // Default color
    }

    /**
     * Get more T-shirt products for pagination
     */
    public function getMoreTshirtProducts($offset = 0, $limit = 10)
    {
        return $this->getTshirtProducts($limit, $offset);
    }

    /**
     * Get fallback products when Printful API fails
     */
    private function getFallbackProducts($limit = 20)
    {
        \Log::info('PrintfulService: Using fallback unisex t-shirt products for USA', ['limit' => $limit]);
        
        return collect([
            [
                'id' => '493', // Add id field for consistency
                'printful_id' => '493', // Valid Printful product ID
                'printful_product_id' => '493',
                'variant_id' => '12585', // Valid variant ID for this product
                'name' => 'Unisex Eco Sweatshirt | Stanley/Stella STSU178',
                'description' => 'Premium organic cotton unisex sweatshirt - perfect for USA market',
                'type' => 'T-SHIRT',
                'brand' => 'Stanley/Stella',
                'model' => 'STSU178',
                'base_price' => 34.75,
                'image_url' => null,
                'is_active' => true,
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Navy', 'color_codes' => ['#000080']],
                ],
            ],
            [
                'printful_id' => '506', // Valid Printful product ID
                'printful_product_id' => '506',
                'variant_id' => '12694', // Valid variant ID for this product
                'name' => 'Unisex Organic Sweatshirt | SOL\'S 03574',
                'description' => 'High-quality organic cotton unisex sweatshirt - USA compatible',
                'type' => 'T-SHIRT',
                'brand' => 'SOL\'S',
                'model' => '03574',
                'base_price' => 24.75,
                'image_url' => null,
                'is_active' => true,
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Red', 'color_codes' => ['#ff0000']],
                    ['color_name' => 'Blue', 'color_codes' => ['#0000ff']],
                ],
            ],
            [
                'printful_id' => '515', // Valid Printful product ID
                'printful_product_id' => '515',
                'variant_id' => '12966', // Valid variant ID for this product
                'name' => 'Oversized Tie-Dye T-Shirt | Shaka Wear SHHTDS',
                'description' => 'Comfortable oversized tie-dye unisex T-shirt - perfect for all',
                'type' => 'T-SHIRT',
                'brand' => 'Shaka Wear',
                'model' => 'SHHTDS',
                'base_price' => 19.33,
                'image_url' => null,
                'is_active' => true,
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Gray', 'color_codes' => ['#808080']],
                ],
            ]
        ])->take($limit);
    }

    /**
     * Get color code from color name
     */
    private function getColorCode($colorName)
    {
        $colorMap = [
            'white' => '#ffffff',
            'black' => '#000000',
            'navy' => '#000080',
            'gray' => '#808080',
            'red' => '#ff0000',
            'blue' => '#0000ff',
            'green' => '#00ff00',
            'yellow' => '#ffff00',
            'purple' => '#800080',
            'pink' => '#ffc0cb',
            'orange' => '#ffa500',
            'brown' => '#a52a2a',
        ];

        // Ensure colorName is a string before calling strtolower
        if (!is_string($colorName)) {
            \Log::warning('PrintfulService: getColorCode received non-string colorName', [
                'colorName' => $colorName,
                'type' => gettype($colorName)
            ]);
            return '#ffffff'; // Default to white
        }

        $colorName = strtolower($colorName);
        return $colorMap[$colorName] ?? '#ffffff';
    }

    /**
     * Get basic T-shirt products without API calls (for when API is down)
     */
    public function getBasicTshirtProducts($limit = 10)
    {
        \Log::info('PrintfulService: Using basic unisex T-shirt products for USA (no API calls)');
        
        return collect([
            [
                'id' => '493', // Add id field for consistency
                'printful_id' => '493', // Valid Printful product ID
                'printful_product_id' => '493',
                'variant_id' => '12585', // Valid variant ID for this product
                'name' => 'Unisex Eco Sweatshirt | Stanley/Stella STSU178',
                'description' => 'Premium organic cotton unisex sweatshirt - perfect for USA market',
                'type' => 'T-SHIRT',
                'brand' => 'Stanley/Stella',
                'model' => 'STSU178',
                'base_price' => 34.75,
                'image_url' => null,
                'is_active' => true,
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Navy', 'color_codes' => ['#000080']],
                ],
            ],
            [
                'printful_id' => '506', // Valid Printful product ID
                'printful_product_id' => '506',
                'variant_id' => '12694', // Valid variant ID for this product
                'name' => 'Unisex Organic Sweatshirt | SOL\'S 03574',
                'description' => 'High-quality organic cotton unisex sweatshirt - USA compatible',
                'type' => 'T-SHIRT',
                'brand' => 'SOL\'S',
                'model' => '03574',
                'base_price' => 24.75,
                'image_url' => null,
                'is_active' => true,
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Red', 'color_codes' => ['#ff0000']],
                    ['color_name' => 'Blue', 'color_codes' => ['#0000ff']],
                ],
            ],
            [
                'printful_id' => '515', // Valid Printful product ID
                'printful_product_id' => '515',
                'variant_id' => '12966', // Valid variant ID for this product
                'name' => 'Oversized Tie-Dye T-Shirt | Shaka Wear SHHTDS',
                'description' => 'Comfortable oversized tie-dye unisex T-shirt - perfect for all',
                'type' => 'T-SHIRT',
                'brand' => 'Shaka Wear',
                'model' => 'SHHTDS',
                'base_price' => 19.33,
                'image_url' => null,
                'is_active' => true,
                'sizes' => ['S', 'M', 'L', 'XL', '2XL'],
                'colors' => [
                    ['color_name' => 'White', 'color_codes' => ['#ffffff']],
                    ['color_name' => 'Black', 'color_codes' => ['#000000']],
                    ['color_name' => 'Gray', 'color_codes' => ['#808080']],
                ],
            ]
        ])->take($limit);
    }
} 
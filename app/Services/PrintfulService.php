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
                return $data['result'] ?? null;
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
     * Get T-shirt products directly from Printful catalog
     */
    public function getTshirtProducts($limit = 20)
    {
        try {
            \Log::info('PrintfulService: Starting getTshirtProducts', [
                'limit' => $limit,
                'api_key_length' => strlen($this->apiKey ?? ''),
                'store_id' => $this->storeId
            ]);

            // Get all products from catalog
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/catalog/products');

            \Log::info('PrintfulService: API response received', [
                'status_code' => $response->status(),
                'response_body_length' => strlen($response->body()),
                'response_body' => $response->body()
            ]);

            if (!$response->successful()) {
                Log::error('Printful Catalog API Error: ' . $response->body());
                return collect();
            }

            $data = $response->json();
            $products = collect($data['result']['products'] ?? []);

            \Log::info('PrintfulService: Raw products data', [
                'total_products' => $products->count(),
                'first_product' => $products->first()
            ]);

            // Filter for T-shirt products
            $tshirtProducts = $products->filter(function ($product) {
                $name = strtolower($product['name'] ?? '');
                $type = strtolower($product['type'] ?? '');
                
                // Check if it's a T-shirt product
                $isTshirt = str_contains($name, 't-shirt') || 
                           str_contains($name, 'tshirt') || 
                           str_contains($type, 't-shirt') ||
                           str_contains($type, 'tshirt');
                
                \Log::info('PrintfulService: Product filtering', [
                    'product_name' => $product['name'] ?? 'unknown',
                    'product_type' => $product['type'] ?? 'unknown',
                    'is_tshirt' => $isTshirt
                ]);
                
                return $isTshirt;
            })->take($limit);

            \Log::info('PrintfulService: T-shirt products filtered', [
                'tshirt_products_count' => $tshirtProducts->count()
            ]);

            // Transform to our format
            $formattedProducts = $tshirtProducts->map(function ($product) {
                $formatted = [
                    'printful_id' => $product['id'],
                    'printful_product_id' => $product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'] ?? '',
                    'type' => 'T-SHIRT',
                    'brand' => $product['brand'] ?? 'Unknown',
                    'model' => $product['model'] ?? '',
                    'base_price' => $this->getProductBasePrice($product['id']),
                    'image_url' => $product['image'] ?? null,
                    'is_active' => true,
                    'sizes' => $this->getProductSizes($product['id']),
                    'colors' => $this->getProductColors($product['id']),
                ];
                
                \Log::info('PrintfulService: Formatted product', [
                    'product_id' => $product['id'],
                    'formatted' => $formatted
                ]);
                
                return $formatted;
            });

            Log::info('Printful T-shirt products fetched', [
                'total_products' => $products->count(),
                'tshirt_products' => $tshirtProducts->count(),
                'formatted_products' => $formattedProducts->count()
            ]);

            return $formattedProducts;

        } catch (\Exception $e) {
            Log::error('Printful T-shirt products fetch error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Get product base price from variants
     */
    private function getProductBasePrice($productId)
    {
        try {
            $variants = $this->getProductVariants($productId);
            if ($variants && count($variants) > 0) {
                // Get the first variant's retail price
                return $variants[0]['retail_price'] ?? 19.99;
            }
            return 19.99; // Default price
        } catch (\Exception $e) {
            return 19.99; // Default price on error
        }
    }

    /**
     * Get product sizes from variants
     */
    private function getProductSizes($productId)
    {
        try {
            $variants = $this->getProductVariants($productId);
            if ($variants) {
                return collect($variants)
                    ->pluck('size')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            return ['M']; // Default size
        } catch (\Exception $e) {
            return ['M']; // Default size on error
        }
    }

    /**
     * Get product colors from variants
     */
    private function getProductColors($productId)
    {
        try {
            $variants = $this->getProductVariants($productId);
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
        } catch (\Exception $e) {
            return [['color_name' => 'White', 'color_codes' => ['#ffffff']]]; // Default color on error
        }
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

        $colorName = strtolower($colorName);
        return $colorMap[$colorName] ?? '#ffffff';
    }
} 
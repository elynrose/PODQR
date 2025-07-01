<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0 text-gray-800">
                Order Details
            </h2>
        </div>
    </x-slot>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-white">Order #{{ $order->order_number }}</h1>
                            <p class="text-blue-100 mt-1">Placed on {{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                                @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($order->status === 'paid') bg-blue-100 text-blue-800
                                @elseif($order->status === 'processing') bg-purple-100 text-purple-800
                                @elseif($order->status === 'shipped') bg-green-100 text-green-800
                                @elseif($order->status === 'delivered') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Order Status Timeline -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Order Status</h2>
                        <div class="relative">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium">Order Placed</p>
                                    <p class="text-sm text-gray-600">{{ $order->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                            </div>

                            @if($order->paid_at)
                            <div class="flex items-center space-x-4 mt-4">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium">Payment Confirmed</p>
                                    <p class="text-sm text-gray-600">{{ $order->paid_at->format('M d, Y g:i A') }}</p>
                                </div>
                            </div>
                            @endif

                            @if($order->status === 'processing' || $order->status === 'shipped' || $order->status === 'delivered')
                            <div class="flex items-center space-x-4 mt-4">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium">Processing</p>
                                    <p class="text-sm text-gray-600">Your order is being prepared</p>
                                </div>
                            </div>
                            @endif

                            @if($order->status === 'shipped' || $order->status === 'delivered')
                            <div class="flex items-center space-x-4 mt-4">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium">Shipped</p>
                                    <p class="text-sm text-gray-600">{{ $order->shipped_at ? $order->shipped_at->format('M d, Y g:i A') : 'Your order has been shipped' }}</p>
                                </div>
                            </div>
                            @endif

                            @if($order->status === 'delivered')
                            <div class="flex items-center space-x-4 mt-4">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium">Delivered</p>
                                    <p class="text-sm text-gray-600">Your order has been delivered</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Order Items</h2>
                        <div class="space-y-4">
                            @foreach($order->orderItems as $item)
                            <div class="border rounded-lg p-4">
                                <div class="flex items-start space-x-4">
                                    @php
                                        $designData = $item->design_data ? json_decode($item->design_data, true) : [];
                                        $frontImageUrl = null;
                                        $backImageUrl = null;
                                        
                                        // Get front image
                                        if ($item->design && $item->design->front_image_path) {
                                            $frontImageUrl = \App\Services\CloudStorageService::getUrl($item->design->front_image_path);
                                        } elseif (!empty($designData['print_file_url'])) {
                                            $frontImageUrl = $designData['print_file_url'];
                                        } elseif (!empty($designData['front_image_path'])) {
                                            $frontImageUrl = \App\Services\CloudStorageService::getUrl($designData['front_image_path']);
                                        }
                                        
                                        // Get back image
                                        if ($item->design && $item->design->back_image_path) {
                                            $backImageUrl = \App\Services\CloudStorageService::getUrl($item->design->back_image_path);
                                        } elseif (!empty($designData['back_image_path'])) {
                                            $backImageUrl = \App\Services\CloudStorageService::getUrl($designData['back_image_path']);
                                        }
                                        
                                        $hasImages = $frontImageUrl || $backImageUrl;
                                    @endphp
                                    
                                    @if($hasImages)
                                        <div class="flex-shrink-0">
                                            <div class="relative">
                                                @if($frontImageUrl && $backImageUrl)
                                                    <!-- Carousel for both front and back images -->
                                                    <div id="carousel-{{ $item->id }}" class="carousel slide" data-bs-ride="carousel" style="width: 120px; height: 120px;">
                                                        <div class="carousel-inner">
                                                            <div class="carousel-item active">
                                                                <img src="{{ $frontImageUrl }}" 
                                                                     alt="Front Design" 
                                                                     class="w-20 h-20 object-cover rounded border">
                                                                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs text-center py-1">
                                                                    Front
                                                                </div>
                                                            </div>
                                                            <div class="carousel-item">
                                                                <img src="{{ $backImageUrl }}" 
                                                                     alt="Back Design" 
                                                                     class="w-20 h-20 object-cover rounded border">
                                                                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs text-center py-1">
                                                                    Back
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel-{{ $item->id }}" data-bs-slide="prev" style="width: 20px; height: 20px; top: 50%; transform: translateY(-50%);">
                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                        </button>
                                                        <button class="carousel-control-next" type="button" data-bs-target="#carousel-{{ $item->id }}" data-bs-slide="next" style="width: 20px; height: 20px; top: 50%; transform: translateY(-50%);">
                                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                        </button>
                                                    </div>
                                                @elseif($frontImageUrl)
                                                    <!-- Single front image -->
                                                    <img src="{{ $frontImageUrl }}" 
                                                         alt="Design" 
                                                         class="w-20 h-20 object-cover rounded border">
                                                @elseif($backImageUrl)
                                                    <!-- Single back image -->
                                                    <img src="{{ $backImageUrl }}" 
                                                         alt="Design" 
                                                         class="w-20 h-20 object-cover rounded border">
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="w-20 h-20 bg-gray-200 rounded border flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-lg">{{ $item->design ? $item->design->name : 'Product Only' }}</h3>
                                        <p class="text-gray-600">{{ $item->name }}</p>
                                        <div class="mt-2 space-y-1 text-sm text-gray-600">
                                            <p><span class="font-medium">Size:</span> {{ $item->size }}</p>
                                            <p><span class="font-medium">Color:</span> {{ $item->color ?: 'Default' }}</p>
                                            <p><span class="font-medium">Quantity:</span> {{ $item->quantity }}</p>
                                            <p><span class="font-medium">Unit Price:</span> ${{ number_format($item->unit_price, 2) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-gray-900">${{ number_format($item->total_price, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Subtotal:</span>
                                    <span>${{ number_format($order->subtotal, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Shipping:</span>
                                    <span>${{ number_format($order->shipping, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tax:</span>
                                    <span>${{ number_format($order->tax, 2) }}</span>
                                </div>
                                <div class="border-t pt-2 flex justify-between font-bold text-lg">
                                    <span>Total:</span>
                                    <span>${{ number_format($order->total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Shipping Information</h2>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h3 class="font-medium text-gray-900 mb-2">Shipping Address</h3>
                                    <div class="text-gray-600">
                                        @if($order->shipping_address && is_array($order->shipping_address))
                                            <p>{{ $order->shipping_address['name'] ?? 'N/A' }}</p>
                                            <p>{{ $order->shipping_address['address1'] ?? $order->shipping_address['address'] ?? 'N/A' }}</p>
                                            <p>{{ $order->shipping_address['city'] ?? 'N/A' }}, {{ $order->shipping_address['state_code'] ?? $order->shipping_address['state'] ?? 'N/A' }} {{ $order->shipping_address['zip'] ?? 'N/A' }}</p>
                                            <p>{{ $order->shipping_address['country_code'] ?? $order->shipping_address['country'] ?? 'N/A' }}</p>
                                        @else
                                            <p>Address information not available</p>
                                        @endif
                                    </div>
                                </div>
                                @if($order->billing_address)
                                <div>
                                    <h3 class="font-medium text-gray-900 mb-2">Billing Address</h3>
                                    <div class="text-gray-600">
                                        @if(is_array($order->billing_address))
                                            <p>{{ $order->billing_address['name'] ?? 'N/A' }}</p>
                                            <p>{{ $order->billing_address['address1'] ?? $order->billing_address['address'] ?? 'N/A' }}</p>
                                            <p>{{ $order->billing_address['city'] ?? 'N/A' }}, {{ $order->billing_address['state_code'] ?? $order->billing_address['state'] ?? 'N/A' }} {{ $order->billing_address['zip'] ?? 'N/A' }}</p>
                                            <p>{{ $order->billing_address['country_code'] ?? $order->billing_address['country'] ?? 'N/A' }}</p>
                                        @else
                                            <p>Address information not available</p>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    @if($order->printful_order_id)
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Fulfillment Information</h2>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p><span class="font-medium">Printful Order ID:</span> {{ $order->printful_order_id }}</p>
                            <p class="text-sm text-gray-600 mt-1">Your order is being fulfilled by Printful</p>
                        </div>
                    </div>
                    @endif

                    @if($order->notes)
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Order Notes</h2>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-700">{{ $order->notes }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t">
                        <a href="{{ route('orders.history') }}" 
                           class="bg-gray-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-gray-700">
                            Back to Orders
                        </a>
                        
                        <div class="flex space-x-3">
                            @if($order->status === 'pending')
                            <div class="text-sm text-gray-600">
                                <p>Payment pending. Please complete your payment to process this order.</p>
                            </div>
                            @elseif(trim($order->status) == 'paid' && empty($order->printful_order_id))
                            <div class="flex space-x-3">
                                <form action="{{ route('orders.send-to-printful', $order) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="btn btn-success"
                                            onclick="return confirm('Are you sure you want to send this order to Printful?')">
                                        🚀 Send to Printful
                                    </button>
                                </form>
                                
                                <!-- Show cancellation option for orders that might have discontinued variants -->
                                <form action="{{ route('orders.cancel-discontinued', $order) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="btn btn-warning"
                                            onclick="return confirm('Are you sure you want to cancel this order? This will process a refund if payment was made.')">
                                        ❌ Cancel Order
                                    </button>
                                </form>
                            </div>
                            @elseif($order->printful_order_id)
                            <span class="btn btn-secondary disabled">
                                ✅ Sent to Printful (ID: {{ $order->printful_order_id }})
                            </span>
                            @elseif($order->status === 'cancelled')
                            <span class="btn btn-secondary disabled">
                                ❌ Order Cancelled
                                @if($order->cancellation_reason)
                                    <br><small>{{ $order->cancellation_reason }}</small>
                                @endif
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
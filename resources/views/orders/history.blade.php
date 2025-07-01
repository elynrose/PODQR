<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0 text-gray-800">
                Order History
            </h2>
        </div>
    </x-slot>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-blue-600 px-6 py-4">
                    <h1 class="text-2xl font-bold text-white">Order History</h1>
                    <p class="text-green-100 mt-1">Track your orders and their status</p>
                </div>

                <div class="p-6">
                    @if($orders->count() > 0)
                        <div class="space-y-6">
                            @foreach($orders as $order)
                            <div class="border rounded-lg p-6 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold">Order #{{ $order->order_number }}</h3>
                                        <p class="text-sm text-gray-600">{{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                            @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($order->status === 'paid') bg-blue-100 text-blue-800
                                            @elseif($order->status === 'processing') bg-purple-100 text-purple-800
                                            @elseif($order->status === 'shipped') bg-green-100 text-green-800
                                            @elseif($order->status === 'delivered') bg-green-100 text-green-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                        <p class="text-lg font-bold text-gray-900 mt-1">${{ number_format($order->total, 2) }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">Shipping Address</h4>
                                        <div class="text-sm text-gray-600">
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
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">Order Summary</h4>
                                        <div class="text-sm text-gray-600 space-y-1">
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
                                            <div class="flex justify-between font-medium">
                                                <span>Total:</span>
                                                <span>${{ number_format($order->total, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t pt-4">
                                    <h4 class="font-medium text-gray-900 mb-3">Items</h4>
                                    <div class="space-y-3">
                                        @foreach($order->orderItems as $item)
                                        <div class="flex items-center space-x-4">
                                            @php
                                                $designData = $item->design_data ? json_decode($item->design_data, true) : [];
                                                $frontImageUrl = null;
                                                $backImageUrl = null;
                                                
                                                // Get front image
                                                if ($item->design && $item->design->front_image_path) {
                                                    $frontImageUrl = asset('storage/' . $item->design->front_image_path);
                                                } elseif (!empty($designData['front_image_path'])) {
                                                    $frontImageUrl = asset('storage/' . $designData['front_image_path']);
                                                } elseif (!empty($designData['print_file_url'])) {
                                                    $frontImageUrl = $designData['print_file_url'];
                                                }
                                                
                                                // Get back image
                                                if ($item->design && $item->design->back_image_path) {
                                                    $backImageUrl = asset('storage/' . $item->design->back_image_path);
                                                } elseif (!empty($designData['back_image_path'])) {
                                                    $backImageUrl = asset('storage/' . $designData['back_image_path']);
                                                }
                                            @endphp
                                            
                                            <!-- Design Images -->
                                            <div class="flex space-x-2">
                                                @if($frontImageUrl)
                                                    <div class="text-center">
                                                        <img src="{{ $frontImageUrl }}" alt="Front Design" 
                                                             class="rounded border" style="max-width:80px;max-height:80px;">
                                                        <p class="text-xs text-gray-500 mt-1">Front</p>
                                                    </div>
                                                @endif
                                                
                                                @if($backImageUrl)
                                                    <div class="text-center">
                                                        <img src="{{ $backImageUrl }}" alt="Back Design" 
                                                             class="rounded border" style="max-width:80px;max-height:80px;">
                                                        <p class="text-xs text-gray-500 mt-1">Back</p>
                                                    </div>
                                                @endif
                                                
                                                @if(!$frontImageUrl && !$backImageUrl)
                                                    <div class="w-16 h-16 bg-gray-200 rounded border flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="flex-1">
                                                <h5 class="font-medium">{{ $item->design ? $item->design->name : 'Product Only' }}</h5>
                                                <p class="text-sm text-gray-600">
                                                    {{ $item->name }} - {{ $item->size }}, {{ $item->color }}
                                                </p>
                                                <p class="text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-medium">${{ number_format($item->total_price, 2) }}</p>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end space-x-2">
                                    <a href="{{ route('orders.show', $order) }}" 
                                       class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                                        View Details
                                    </a>
                                    
                                    @if(trim($order->status) == 'paid' && empty($order->printful_order_id))
                                        <form action="{{ route('orders.send-to-printful', $order) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-success btn-sm"
                                                    onclick="return confirm('Are you sure you want to send this order to Printful?')">
                                                🚀 Send to Printful
                                            </button>
                                        </form>
                                        
                                        <!-- Cancel due to regional restrictions -->
                                        <form action="{{ route('orders.cancel-regional', $order) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-warning btn-sm"
                                                    onclick="return confirm('Cancel this order due to regional shipping restrictions? This will process a refund if the order was paid.')">
                                                🚫 Cancel (Regional Issues)
                                            </button>
                                        </form>
                                    @elseif($order->printful_order_id)
                                        <span class="btn btn-secondary btn-sm disabled">
                                            ✅ Sent to Printful (ID: {{ $order->printful_order_id }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $orders->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No orders yet</h3>
                            <p class="text-gray-600 mb-6">Start creating designs and place your first order!</p>
                            <a href="{{ route('designs.index') }}" 
                               class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700">
                                Browse Designs
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
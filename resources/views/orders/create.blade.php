@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Error Messages -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="bi bi-cart-plus me-2"></i>
                            Order Products
                        </h3>
                        <p class="mb-0 mt-1 opacity-75">Select products to order</p>
                    </div>

                    <div class="card-body">
                        <!-- Order Form -->
                        <form id="orderForm" method="POST" action="{{ route('orders.store') }}">
                            @csrf
                            
                            <!-- Product Selection -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Select Products</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- Redesigned Filters -->
                                            <div class="row mb-4">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h6 class="card-title mb-3">
                                                                <i class="bi bi-funnel me-2"></i>
                                                                Filter Products
                                                            </h6>
                                                            
                                                            <div class="row g-3">
                                                                <!-- Size Filter -->
                                                                <div class="col-md-4">
                                                                    <label class="form-label fw-bold">Size</label>
                                                                    <div class="btn-group-vertical w-100" role="group" id="sizeFilterGroup">
                                                                        <input type="radio" class="btn-check" name="sizeFilter" id="sizeAll" value="" checked>
                                                                        <label class="btn btn-outline-primary btn-sm" for="sizeAll">
                                                                            <i class="bi bi-check-circle me-1"></i>All Sizes
                                                                        </label>
                                                                        
                                                                        @foreach($sizes as $size)
                                                                            <input type="radio" class="btn-check" name="sizeFilter" id="size_{{ str_replace([' ', '+', '-'], '_', $size) }}" value="{{ $size }}">
                                                                            <label class="btn btn-outline-primary btn-sm" for="size_{{ str_replace([' ', '+', '-'], '_', $size) }}">
                                                                                {{ $size }}
                                                                            </label>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Color Filter -->
                                                                <div class="col-md-4">
                                                                    <label class="form-label fw-bold">Color</label>
                                                                    <div class="btn-group-vertical w-100" role="group" id="colorFilterGroup">
                                                                        <input type="radio" class="btn-check" name="colorFilter" id="colorAll" value="" checked>
                                                                        <label class="btn btn-outline-primary btn-sm" for="colorAll">
                                                                            <i class="bi bi-check-circle me-1"></i>All Colors
                                                                        </label>
                                                                        
                                                                        @foreach($colors as $color)
                                                                            <input type="radio" class="btn-check" name="colorFilter" id="color_{{ str_replace([' ', '+', '-'], '_', $color) }}" value="{{ $color }}">
                                                                            <label class="btn btn-outline-primary btn-sm" for="color_{{ str_replace([' ', '+', '-'], '_', $color) }}">
                                                                                <span class="d-inline-block me-2" style="width: 12px; height: 12px; background-color: {{ $color == 'White' ? '#ffffff' : ($color == 'Black' ? '#000000' : ($color == 'Navy' ? '#000080' : '#cccccc')) }}; border: 1px solid #ddd; border-radius: 2px;"></span>
                                                                                {{ $color }}
                                                                            </label>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Type Filter -->
                                                                <div class="col-md-4">
                                                                    <label class="form-label fw-bold">Type</label>
                                                                    <div class="btn-group-vertical w-100" role="group" id="typeFilterGroup">
                                                                        <input type="radio" class="btn-check" name="typeFilter" id="typeAll" value="" checked>
                                                                        <label class="btn btn-outline-primary btn-sm" for="typeAll">
                                                                            <i class="bi bi-check-circle me-1"></i>All Types
                                                                        </label>
                                                                        
                                                                        @foreach($types as $type)
                                                                            <input type="radio" class="btn-check" name="typeFilter" id="type_{{ str_replace([' ', '+', '-'], '_', $type) }}" value="{{ $type }}">
                                                                            <label class="btn btn-outline-primary btn-sm" for="type_{{ str_replace([' ', '+', '-'], '_', $type) }}">
                                                                                {{ ucfirst(str_replace('-', ' ', $type)) }}
                                                                            </label>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row mt-3">
                                                                <div class="col-12">
                                                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                                                                        <i class="bi bi-x-circle me-1"></i>Clear All Filters
                                                                    </button>
                                                                    <span class="ms-3 text-muted" id="productCount">
                                                                        <i class="bi bi-grid me-1"></i>Showing {{ count($products) }} products
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Product Grid -->
                                            <div class="row" id="productGrid">
                                                @forelse($products as $product)
                                                    <div class="col-md-4 mb-3 product-card" 
                                                         data-product-id="{{ is_array($product) ? (is_string($product['id']) ? $product['id'] : (is_numeric($product['id']) ? $product['id'] : '')) : $product->id }}"
                                                         data-variant-id="{{ is_array($product) ? (is_string($product['variant_id']) ? $product['variant_id'] : (is_numeric($product['variant_id']) ? $product['variant_id'] : '')) : ($product->variant_id ?? '') }}"
                                                         data-type="{{ is_array($product) ? (is_string($product['type']) ? $product['type'] : 'T-SHIRT') : $product->type }}"
                                                         data-sizes="{{ is_array($product) ? json_encode($product['sizes']) : json_encode($product->sizes) }}"
                                                         data-colors="{{ is_array($product) ? json_encode($product['colors']) : json_encode($product->colors) }}"
                                                         data-price="{{ is_array($product) ? (is_numeric($product['base_price']) ? $product['base_price'] : 19.99) : $product->base_price }}">
                                                        <div class="card h-100 product-card-inner">
                                                            <div class="card-body d-flex flex-column">
                                                                <div class="text-center mb-3">
                                                                    @if(is_array($product) ? (is_string($product['image_url']) ? $product['image_url'] : null) : $product->image_url)
                                                                        <img src="{{ is_array($product) ? (is_string($product['image_url']) ? $product['image_url'] : '') : $product->image_url }}" 
                                                                             alt="{{ is_array($product) ? (is_string($product['name']) ? $product['name'] : 'Product') : $product->name }}" 
                                                                             class="img-fluid" 
                                                                             style="max-height: 150px; object-fit: contain;">
                                                                    @else
                                                                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                                                            <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <h6 class="card-title">{{ is_array($product) ? (is_string($product['name']) ? $product['name'] : 'Product') : $product->name }}</h6>
                                                                <p class="card-text text-muted small">{{ is_array($product) ? (is_string($product['type']) ? $product['type'] : 'T-Shirt') : $product->type }}</p>
                                                                <div class="mt-auto">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <span class="fw-bold text-primary">${{ number_format(is_array($product) ? (is_numeric($product['base_price']) ? $product['base_price'] : 19.99) : $product->base_price, 2) }}</span>
                                                                        <button type="button" class="btn btn-outline-primary btn-sm select-product">
                                                                            Select
                                                                        </button>
                                                                    </div>
                                                                    
                                                                    <div class="mt-3">
                                                                        @php
                                                                            $sizes = is_array($product) ? (is_array($product['sizes']) ? $product['sizes'] : ['M']) : $product->sizes;
                                                                        @endphp
                                                                        @if($sizes && count($sizes) > 0)
                                                                            <select class="form-select form-select-sm size-select mb-2" disabled>
                                                                                <option value="">Select Size</option>
                                                                                @foreach($sizes as $size)
                                                                                    <option value="{{ $size }}">{{ $size }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif
                                                                        
                                                                        {{-- Color dropdown commented out since we filter by design color --}}
                                                                        {{-- @if($product->colors && count($product->colors) > 0)
                                                                            <select class="form-select form-select-sm color-select mb-2" disabled>
                                                                                <option value="">Select Color</option>
                                                                                @foreach($product->colors as $color)
                                                                                    <option value="{{ is_array($color) ? $color['color_name'] : $color }}">{{ is_array($color) ? $color['color_name'] : $color }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif --}}
                                                                        
                                                                        <div class="input-group input-group-sm">
                                                                            <label class="input-group-text">Qty</label>
                                                                            <input type="number" min="1" value="1" 
                                                                                   class="form-control quantity-input" disabled>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="col-12">
                                                        <div class="alert alert-info text-center">
                                                            <i class="bi bi-info-circle me-2"></i>
                                                            No products are currently available. Please check back later.
                                                        </div>
                                                    </div>
                                                @endforelse
                                            </div>
                                            
                                            <!-- No Products Alert -->
                                            <div class="row" id="noProductsAlert" style="display: none;">
                                                <div class="col-12">
                                                    <div class="alert alert-info text-center">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        No products match your current filters. 
                                                        <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="clearFilters()">
                                                            Clear Filters
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Load More Products -->
                                            <div class="row mt-4" id="loadMoreSection" style="display: none;">
                                                <div class="col-12 text-center">
                                                    <button type="button" class="btn btn-outline-primary" id="loadMoreBtn">
                                                        <i class="bi bi-arrow-down-circle me-2"></i>
                                                        Load More Products
                                                    </button>
                                                    <div class="mt-2">
                                                        <small class="text-muted" id="loadMoreStatus"></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Design Selection -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="bi bi-palette me-2"></i>
                                                Select Design (Optional)
                                            </h5>
                                            <p class="mb-0 mt-1 text-muted small">Choose a design to apply to your products, or leave blank for plain products</p>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label class="form-label">Your Designs</label>
                                                    <select class="form-select" id="designSelect" name="design_id">
                                                        <option value="">No Design (Plain Product)</option>
                                                        @if(auth()->check() && auth()->user())
                                                            @foreach(auth()->user()->designs()->whereNotNull('front_image_path')->get() as $userDesign)
                                                                <option value="{{ $userDesign->id }}" 
                                                                        {{ $design && $design->id == $userDesign->id ? 'selected' : '' }}>
                                                                    {{ $userDesign->name }}
                                                                </option>
                                                            @endforeach
                                                        @else
                                                            <option value="" disabled>Please log in to see your designs</option>
                                                        @endif
                                                    </select>
                                                    @if(!auth()->check())
                                                        <small class="text-muted">You need to be logged in to use your own designs</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <div id="designPreview" class="text-center" style="display: none;">
                                                        <img id="designPreviewImage" src="" alt="Design Preview" 
                                                             class="img-fluid rounded" style="max-height: 200px;">
                                                        <p class="mt-2 text-muted small" id="designPreviewName"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="bi bi-receipt me-2"></i>
                                                Order Summary
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="orderSummary">
                                                <p class="text-muted mb-0">Select products to see order summary</p>
                                            </div>
                                            <div class="border-top pt-3 mt-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0">Total:</h5>
                                                    <h4 class="mb-0 text-primary" id="orderTotal">$0.00</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-3">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    Shipping Address
                                </h4>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Full Name *</label>
                                                <input type="text" name="shipping_address[name]" required
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email *</label>
                                                <input type="email" name="shipping_address[email]" required
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone *</label>
                                                <input type="tel" name="shipping_address[phone]" required
                                                       class="form-control">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Address *</label>
                                                <input type="text" name="shipping_address[address]" required
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">City *</label>
                                                <input type="text" name="shipping_address[city]" required
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">State *</label>
                                                <input type="text" name="shipping_address[state]" required
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">ZIP Code *</label>
                                                <input type="text" name="shipping_address[zip]" required
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Country *</label>
                                                <select name="shipping_address[country]" required
                                                        class="form-select">
                                                    <option value="">Select Country</option>
                                                    <option value="US">United States</option>
                                                    <option value="CA">Canada</option>
                                                    <option value="GB">United Kingdom</option>
                                                    <option value="AU">Australia</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-3">
                                    <i class="bi bi-credit-card me-2"></i>
                                    Payment
                                </h4>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>Secure Payment:</strong> You'll be redirected to Stripe's secure payment page to complete your order. We never store your credit card information on our servers.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="submit" id="submitButton" 
                                        class="btn btn-primary btn-lg" disabled>
                                    <i class="bi bi-cart-check me-2"></i>
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mb-2">Processing your order...</h5>
                    <p class="text-muted mb-0">Please don't close this window</p>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .product-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
        
        .product-card.selected {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
        }
        
        .selected-badge {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .form-control:disabled, .form-select:disabled {
            background-color: #f8f9fa;
            opacity: 0.6;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    // Global variables
    const selectedProducts = new Map();
    let currentPage = 1;
    let hasMorePages = true;
    let currentFilters = {};
    
    // Design color from backend
    const designColor = @json($designColor ?? null);

    // Function definitions
    function addProductCardEventListeners(card) {
        const selectBtn = card.querySelector('.select-product');
        const sizeSelect = card.querySelector('.size-select');
        // const colorSelect = card.querySelector('.color-select'); // Commented out since color is filtered by design
        const quantityInput = card.querySelector('.quantity-input');
        
        if (!selectBtn) return; // Skip if no select button found
        
        selectBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = card.dataset.productId;
            const variantId = card.dataset.variantId;
            const productName = card.querySelector('.card-title').textContent;
            const priceValue = card.dataset.price;
            const productPrice = parseFloat(priceValue) || 0;
            
            if (selectedProducts.has(productId)) {
                // Deselect
                selectedProducts.delete(productId);
                card.classList.remove('selected');
                selectBtn.textContent = 'Select';
                selectBtn.classList.remove('btn-primary');
                selectBtn.classList.add('btn-outline-primary');
                
                // Disable inputs
                if (sizeSelect) sizeSelect.disabled = true;
                // if (colorSelect) colorSelect.disabled = true; // Commented out
                if (quantityInput) quantityInput.disabled = true;
            } else {
                // Get the design color if a design is selected
                const designSelect = document.getElementById('designSelect');
                let selectedDesignColor = null;
                if (designSelect && designSelect.value) {
                    // Use the design color from the backend
                    selectedDesignColor = designColor;
                }
                
                // Select
                selectedProducts.set(productId, {
                    id: productId,
                    variant_id: variantId, // Add variant ID for Printful
                    name: productName,
                    price: productPrice,
                    size: sizeSelect ? sizeSelect.value : 'One Size',
                    color: selectedDesignColor, // Use design color instead of dropdown selection
                    quantity: quantityInput ? parseInt(quantityInput.value) || 1 : 1
                });
                
                card.classList.add('selected');
                selectBtn.textContent = 'Selected';
                selectBtn.classList.remove('btn-outline-primary');
                selectBtn.classList.add('btn-primary');
                
                // Enable inputs
                if (sizeSelect) sizeSelect.disabled = false;
                // if (colorSelect) colorSelect.disabled = false; // Commented out
                if (quantityInput) quantityInput.disabled = false;
            }
            
            updateOrderSummary();
        });
        
        // Handle options changes
        if (sizeSelect) {
            sizeSelect.addEventListener('change', function() {
                if (selectedProducts.has(card.dataset.productId)) {
                    updateProductData();
                    updateOrderSummary();
                }
            });
        }
        
        // Color select event listener commented out since color dropdown is removed
        // if (colorSelect) {
        //     colorSelect.addEventListener('change', function() {
        //         if (selectedProducts.has(card.dataset.productId)) {
        //             updateProductData();
        //             updateOrderSummary();
        //         }
        //     });
        // }
        
        if (quantityInput) {
            quantityInput.addEventListener('change', function() {
                if (selectedProducts.has(card.dataset.productId)) {
                    updateProductData();
                    updateOrderSummary();
                }
            });
        }
    }

    function updateProductData() {
        // Get the design color if a design is selected
        const designSelect = document.getElementById('designSelect');
        let selectedDesignColor = 'White'; // Default color
        if (designSelect && designSelect.value) {
            // Use the design color from the backend
            selectedDesignColor = designColor || 'White';
        }
        
        selectedProducts.forEach((product, productId) => {
            const card = document.querySelector(`[data-product-id="${productId}"]`);
            if (card) {
                const sizeSelect = card.querySelector('.size-select');
                // const colorSelect = card.querySelector('.color-select'); // Commented out
                const quantityInput = card.querySelector('.quantity-input');
                
                // Set size if dropdown exists, otherwise use 'One Size'
                product.size = sizeSelect ? sizeSelect.value : 'One Size';
                // Color is now determined by the design, not by dropdown selection
                product.color = selectedDesignColor; // Set color based on design
                product.quantity = parseInt(quantityInput.value) || 1;
            }
        });
    }
    
    function updateOrderSummary() {
        const summary = document.getElementById('orderSummary');
        const total = document.getElementById('orderTotal');
        const submitButton = document.getElementById('submitButton');
        
        if (selectedProducts.size === 0) {
            summary.innerHTML = '<p class="text-muted mb-0">Select products to see order summary</p>';
            total.textContent = '$0.00';
            submitButton.disabled = true;
            return;
        }
        
        // Get the design color if a design is selected
        const designSelect = document.getElementById('designSelect');
        let selectedDesignColor = null;
        if (designSelect && designSelect.value) {
            // Use the design color from the backend
            selectedDesignColor = designColor;
        }
        
        let subtotal = 0;
        let summaryHTML = '';
        
        selectedProducts.forEach(product => {
            const itemTotal = product.price * product.quantity;
            
            subtotal += itemTotal;
            
            // Build description based on available options
            let description = product.name;
            if (product.size && product.size !== 'One Size') {
                description += ` (${product.size}`;
                if (selectedDesignColor) {
                    description += `, ${selectedDesignColor}`;
                }
                description += ')';
            } else if (selectedDesignColor) {
                description += ` (${selectedDesignColor})`;
            }
            description += ` x${product.quantity}`;
            
            summaryHTML += `
                <div class="d-flex justify-content-between mb-2">
                    <span>${description}</span>
                    <span class="fw-medium">$${itemTotal.toFixed(2)}</span>
                </div>
            `;
        });
        
        const shipping = 5.99;
        const tax = subtotal * 0.08;
        const orderTotal = subtotal + shipping + tax;
        
        summaryHTML += `
            <hr class="my-3">
            <div class="d-flex justify-content-between mb-1">
                <span>Subtotal:</span>
                <span>$${subtotal.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Shipping:</span>
                <span>$${shipping.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Tax:</span>
                <span>$${tax.toFixed(2)}</span>
            </div>
        `;
        
        summary.innerHTML = summaryHTML;
        total.textContent = `$${orderTotal.toFixed(2)}`;
        submitButton.disabled = false;
    }

    // Store all products for client-side filtering
    let allProducts = [];
    let currentOffset = {{ count($products) }};
    let isLoadingMore = false;
    let hasMoreProducts = true;
    
    function applyFilters() {
        const sizeFilter = document.querySelector('input[name="sizeFilter"]:checked').value;
        const colorFilter = document.querySelector('input[name="colorFilter"]:checked').value;
        const typeFilter = document.querySelector('input[name="typeFilter"]:checked').value;
        
        // Filter products client-side
        const filteredProducts = allProducts.filter(product => {
            let matches = true;
            
            // Size filter - only apply if a specific size is selected (not "All")
            if (sizeFilter && sizeFilter !== '') {
                matches = matches && product.sizes && product.sizes.includes(sizeFilter);
            }
            
            // Color filter - only apply if a specific color is selected (not "All")
            if (colorFilter && colorFilter !== '') {
                matches = matches && product.colors && product.colors.includes(colorFilter);
            }
            
            // Type filter - only apply if a specific type is selected (not "All")
            if (typeFilter && typeFilter !== '') {
                matches = matches && product.type && product.type.toLowerCase() === typeFilter.toLowerCase();
            }
            
            return matches;
        });
        
        // Update the display
        updateProductDisplay(filteredProducts);
    }
    
    function updateProductDisplay(products) {
        const productGrid = document.getElementById('productGrid');
        const productCount = document.getElementById('productCount');
        const noProductsAlert = document.getElementById('noProductsAlert');
        
        if (products.length === 0) {
            // Hide all product cards
            const productCards = productGrid.querySelectorAll('.product-card');
            productCards.forEach(card => {
                card.style.display = 'none';
            });
            // Show the alert
            if (noProductsAlert) noProductsAlert.style.display = 'block';
        } else {
            // Get all product cards
            const productCards = productGrid.querySelectorAll('.product-card');
            // Create a map of product IDs to their corresponding cards
            const productCardMap = new Map();
            productCards.forEach(card => {
                const productId = card.dataset.productId;
                productCardMap.set(productId, card);
            });
            // Show/hide cards based on filtered products
            productCards.forEach(card => {
                card.style.display = 'none'; // Hide all cards first
            });
            products.forEach(product => {
                // Try both string and number versions of the ID
                const card = productCardMap.get(product.id.toString()) || productCardMap.get(product.id);
                if (card) {
                    card.style.display = 'block'; // Show matching cards
                }
            });
            // Hide the alert
            if (noProductsAlert) noProductsAlert.style.display = 'none';
        }
        
        // Update product count
        if (productCount) {
            productCount.innerHTML = `<i class="bi bi-grid me-1"></i>Showing ${products.length} products`;
        }
    }

    function clearFilters() {
        // Reset all radio buttons to "All"
        document.querySelectorAll('input[name="sizeFilter"]').forEach(radio => {
            if (radio.value === '') radio.checked = true;
        });
        document.querySelectorAll('input[name="colorFilter"]').forEach(radio => {
            if (radio.value === '') radio.checked = true;
        });
        document.querySelectorAll('input[name="typeFilter"]').forEach(radio => {
            if (radio.value === '') radio.checked = true;
        });
        
        // Show all products
        updateProductDisplay(allProducts);
    }

    function updateProductCount(count) {
        const productCount = document.getElementById('productCount');
        if (productCount) {
            productCount.innerHTML = `<i class="bi bi-grid me-1"></i>Showing ${count} products`;
        }
    }
    
    function loadMoreProducts() {
        if (isLoadingMore || !hasMoreProducts) return;
        
        isLoadingMore = true;
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const loadMoreStatus = document.getElementById('loadMoreStatus');
        
        // Update button state
        loadMoreBtn.disabled = true;
        loadMoreBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Loading...';
        loadMoreStatus.textContent = 'Fetching more products...';
        
        // Make AJAX request
        fetch('{{ route("orders.load-more-products") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                offset: currentOffset,
                limit: 6,
                design_id: document.getElementById('designSelect')?.value || null,
                location: '{{ $userLocation ?? "US" }}'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products.length > 0) {
                // Add new products to allProducts array
                data.products.forEach(product => {
                    allProducts.push({
                        id: product.id,
                        variant_id: product.variant_id,
                        type: product.type,
                        sizes: product.sizes || [],
                        colors: product.colors || [],
                        base_price: parseFloat(product.base_price || 0),
                        name: product.name,
                        image_url: product.image_url
                    });
                });
                
                // Add new products to the grid
                const productGrid = document.getElementById('productGrid');
                data.products.forEach(product => {
                    const productCard = createProductCard(product);
                    productGrid.appendChild(productCard);
                });
                
                // Update offset and check if more products are available
                currentOffset = data.offset;
                hasMoreProducts = data.has_more;
                
                // Update product count
                updateProductCount(allProducts.length);
                
                // Show/hide load more section
                const loadMoreSection = document.getElementById('loadMoreSection');
                if (hasMoreProducts) {
                    loadMoreSection.style.display = 'block';
                    loadMoreStatus.textContent = `Loaded ${data.products.length} more products`;
                } else {
                    loadMoreSection.style.display = 'none';
                    loadMoreStatus.textContent = 'All products loaded';
                }
                
                // Re-apply current filters to new products
                applyFilters();
                
            } else {
                hasMoreProducts = false;
                loadMoreStatus.textContent = 'No more products available';
                document.getElementById('loadMoreSection').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading more products:', error);
            loadMoreStatus.textContent = 'Error loading products. Please try again.';
        })
        .finally(() => {
            isLoadingMore = false;
            loadMoreBtn.disabled = false;
            loadMoreBtn.innerHTML = '<i class="bi bi-arrow-down-circle me-2"></i>Load More Products';
        });
    }
    
    function createProductCard(product) {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3 product-card';
        col.setAttribute('data-product-id', product.id);
        col.setAttribute('data-variant-id', product.variant_id);
        col.setAttribute('data-type', product.type);
        col.setAttribute('data-sizes', JSON.stringify(product.sizes));
        col.setAttribute('data-colors', JSON.stringify(product.colors));
        col.setAttribute('data-price', product.base_price);
        
        const imageUrl = product.image_url || '';
        const imageHtml = imageUrl ? 
            `<img src="${imageUrl}" alt="${product.name}" class="img-fluid" style="max-height: 150px; object-fit: contain;">` :
            `<div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
            </div>`;
        
        const sizesOptions = product.sizes.map(size => `<option value="${size}">${size}</option>`).join('');
        
        col.innerHTML = `
            <div class="card h-100 product-card-inner">
                <div class="card-body d-flex flex-column">
                    <div class="text-center mb-3">
                        ${imageHtml}
                    </div>
                    <h6 class="card-title">${product.name}</h6>
                    <p class="card-text text-muted small">${product.type}</p>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-primary">$${product.base_price.toFixed(2)}</span>
                            <button type="button" class="btn btn-outline-primary btn-sm select-product">
                                Select
                            </button>
                        </div>
                        
                        <div class="mt-3">
                            ${product.sizes.length > 0 ? `
                                <select class="form-select form-select-sm size-select mb-2" disabled>
                                    <option value="">Select Size</option>
                                    ${sizesOptions}
                                </select>
                            ` : ''}
                            
                            <div class="input-group input-group-sm">
                                <label class="input-group-text">Qty</label>
                                <input type="number" min="1" value="1" class="form-control quantity-input" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add event listeners to the new card
        addProductCardEventListeners(col);
        
        return col;
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing order page...');
        
        // Initialize allProducts array with current products
        const productCards = document.querySelectorAll('.product-card');
        allProducts = Array.from(productCards).map(card => {
            return {
                id: card.dataset.productId,
                variant_id: card.dataset.variantId,
                type: card.dataset.type,
                sizes: JSON.parse(card.dataset.sizes || '[]'),
                colors: JSON.parse(card.dataset.colors || '[]'),
                base_price: parseFloat(card.dataset.price || 0),
                name: card.querySelector('.card-title').textContent,
                image_url: card.querySelector('img')?.src || null
            };
        });
        
        console.log('Initialized allProducts:', allProducts.length, 'products');
        
        // Set up new filter event listeners
        document.querySelectorAll('input[name="sizeFilter"]').forEach(radio => {
            radio.addEventListener('change', applyFilters);
        });
        
        document.querySelectorAll('input[name="colorFilter"]').forEach(radio => {
            radio.addEventListener('change', applyFilters);
        });
        
        document.querySelectorAll('input[name="typeFilter"]').forEach(radio => {
            radio.addEventListener('change', applyFilters);
        });
        
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearFilters);
        
        // Set up load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadMoreProducts);
            
            // Show load more section if we have products and there might be more
            if (allProducts.length > 0 && allProducts.length >= 15) {
                document.getElementById('loadMoreSection').style.display = 'block';
            }
        }
        
        // Design selection handler
        const designSelect = document.getElementById('designSelect');
        if (designSelect) {
            designSelect.addEventListener('change', function() {
                const selectedDesignId = this.value;
                const designPreview = document.getElementById('designPreview');
                const designPreviewImage = document.getElementById('designPreviewImage');
                const designPreviewName = document.getElementById('designPreviewName');
                
                if (selectedDesignId) {
                    // Fetch design details and show preview
                    fetch(`/designs/${selectedDesignId}/preview`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.design) {
                                designPreviewImage.src = data.design.front_image_url;
                                designPreviewName.textContent = data.design.name;
                                designPreview.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching design preview:', error);
                            designPreview.style.display = 'none';
                        });
                } else {
                    designPreview.style.display = 'none';
                }
            });
            
            // Auto-trigger design preview if a design is pre-selected
            if (designSelect.value) {
                designSelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Add event listeners to existing cards
        console.log('Found', productCards.length, 'product cards');
        
        productCards.forEach((card, index) => {
            console.log('Adding event listeners to card', index + 1);
            addProductCardEventListeners(card);
        });
        
        // Initialize order summary
        updateOrderSummary();
        console.log('Order page initialization complete');
    });

    // Form submission handling
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (selectedProducts.size === 0) {
            alert('Please select at least one product before placing your order.');
            return;
        }
        
        // Update product data before submission
        updateProductData();
        
        // Create form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        // Add design ID if selected
        const designSelect = document.getElementById('designSelect');
        if (designSelect && designSelect.value) {
            formData.append('design_id', designSelect.value);
        }
        
        // Add selected products
        const items = [];
        selectedProducts.forEach(product => {
            const item = {
                product_id: product.id,
                variant_id: product.variant_id, // Use variant ID for Printful
                size: product.size,
                color: product.color,
                quantity: product.quantity
            };
            items.push(item);
            console.log('Adding item to order:', item);
        });
        console.log('Final items array:', items);
        formData.append('items', JSON.stringify(items));
        
        // Add shipping address
        const shippingAddress = {
            name: document.querySelector('input[name="shipping_address[name]"]').value,
            email: document.querySelector('input[name="shipping_address[email]"]').value,
            phone: document.querySelector('input[name="shipping_address[phone]"]').value,
            address: document.querySelector('input[name="shipping_address[address]"]').value,
            city: document.querySelector('input[name="shipping_address[city]"]').value,
            state: document.querySelector('input[name="shipping_address[state]"]').value,
            zip: document.querySelector('input[name="shipping_address[zip]"]').value,
            country: document.querySelector('select[name="shipping_address[country]"]').value
        };
        formData.append('shipping_address', JSON.stringify(shippingAddress));
        
        // Show loading modal
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
        
        // Submit form
        fetch('{{ route("orders.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingModal.hide();
            
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                // Handle specific validation errors
                if (data.errors && Array.isArray(data.errors)) {
                    // Show detailed error messages
                    let errorMessage = 'Some products are no longer available:\n\n';
                    data.errors.forEach(error => {
                        errorMessage += '• ' + error + '\n';
                    });
                    errorMessage += '\nPlease remove these items from your cart and try again.';
                    alert(errorMessage);
                } else {
                    alert(data.message || 'An error occurred while processing your order. Please try again.');
                }
            }
        })
        .catch(error => {
            loadingModal.hide();
            console.error('Error:', error);
            alert('An error occurred while processing your order. Please try again.');
        });
    });
    </script>
    @endpush
@endsection 
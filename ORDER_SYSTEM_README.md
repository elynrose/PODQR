# Order System Implementation

This document describes the complete order system implementation for PODQR, integrating Printful API for fulfillment and Stripe for payments.

## Overview

The order system allows users to:
- Order their custom designs on various products (t-shirts, hoodies, etc.)
- Select product variants (size, color, quantity)
- Process payments securely through Stripe
- Track order status and fulfillment through Printful
- View order history and details

## Architecture

### Components

1. **Database Models**
   - `Product` - Cached Printful product data
   - `Order` - Order information and status
   - `OrderItem` - Individual items in orders

2. **Services**
   - `PrintfulService` - Handles Printful API interactions
   - `StripeService` - Handles Stripe payment processing

3. **Controllers**
   - `OrderController` - Manages order flow and API endpoints

4. **Views**
   - `orders/create.blade.php` - Order form with product selection
   - `orders/history.blade.php` - Order history listing
   - `orders/show.blade.php` - Order details and status

## Database Schema

### Products Table
```sql
- id (primary key)
- printful_id (unique) - Printful product ID
- name - Product name
- description - Product description
- type - Product type (t-shirt, hoodie, etc.)
- brand - Product brand
- model - Product model
- sizes (json) - Available sizes
- colors (json) - Available colors
- base_price - Base product price
- image_url - Product image
- is_active - Whether product is available
```

### Orders Table
```sql
- id (primary key)
- user_id (foreign key) - User who placed order
- order_number (unique) - Custom order number
- printful_order_id - Printful order ID
- stripe_payment_intent_id - Stripe payment intent
- status - Order status (pending, paid, processing, shipped, delivered, cancelled)
- subtotal - Order subtotal
- tax - Tax amount
- shipping - Shipping cost
- total - Total order amount
- currency - Currency code
- shipping_address (json) - Shipping address
- billing_address (json) - Billing address
- notes - Order notes
- paid_at - Payment timestamp
- shipped_at - Shipping timestamp
```

### Order Items Table
```sql
- id (primary key)
- order_id (foreign key) - Associated order
- design_id (foreign key) - User's design
- product_id (foreign key) - Selected product
- printful_variant_id - Printful variant ID
- size - Selected size
- color - Selected color
- quantity - Quantity ordered
- unit_price - Unit price
- total_price - Total price for this item
- design_data (json) - Design configuration
- printful_item_id - Printful item ID
```

## API Integration

### Printful API

The `PrintfulService` handles:
- Product catalog synchronization
- Product creation with custom designs
- Order creation and fulfillment
- Shipping rate calculation
- Order status tracking

Key methods:
- `getProducts()` - Fetch available products
- `createProduct()` - Create product with design
- `createOrder()` - Create order in Printful
- `getOrderStatus()` - Check order status
- `syncProducts()` - Sync products to local database

### Stripe API

The `StripeService` handles:
- Payment intent creation
- Payment confirmation
- Customer management
- Tax calculation
- Refund processing

Key methods:
- `createPaymentIntent()` - Create payment intent
- `confirmPaymentIntent()` - Confirm payment
- `createOrRetrieveCustomer()` - Manage customers
- `calculateTax()` - Calculate tax for orders

## Order Flow

1. **Product Selection**
   - User clicks "Order" button on design card
   - Order form opens with available products
   - User selects products, sizes, colors, quantities

2. **Address Entry**
   - User enters shipping address
   - System validates address information

3. **Payment Processing**
   - Stripe payment intent created
   - User enters payment details
   - Payment processed through Stripe

4. **Order Creation**
   - Order created in local database
   - Payment verified with Stripe
   - Order sent to Printful for fulfillment

5. **Fulfillment**
   - Printful processes order
   - Order status updated
   - User can track progress

## Routes

### Web Routes
```
GET  /orders/create/{design}     - Show order form
GET  /orders/history            - Show order history
GET  /orders/{order}            - Show order details
```

### API Routes
```
GET  /api/products              - Get available products
POST /api/calculate-total       - Calculate order total
POST /api/create-payment-intent - Create Stripe payment intent
POST /api/process-order         - Process complete order
POST /api/sync-products         - Sync products from Printful
```

## Configuration

### Environment Variables

Add to `.env` file:
```env
# Printful API
PRINTFUL_API_KEY=your_printful_api_key

# Stripe API
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret
```

### Services Configuration

The services are configured in `config/services.php`:
```php
'printful' => [
    'api_key' => env('PRINTFUL_API_KEY'),
],

'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
],
```

## Frontend Integration

### Order Button

Added to design cards in `resources/views/designs/index.blade.php`:
```html
<a href="{{ route('orders.create', $design->id) }}" 
   class="btn btn-outline-success">
    <i class="bi bi-cart"></i>
</a>
```

### Navigation

Added to navigation in `resources/views/layouts/navigation.blade.php`:
```html
<li class="nav-item">
    <a class="nav-link" href="{{ route('orders.history') }}">
        <i class="bi bi-cart"></i> Orders
    </a>
</li>
```

## Testing

### Sample Data

Run the ProductSeeder to add sample products:
```bash
php artisan db:seed --class=ProductSeeder
```

This adds 5 sample products:
- Unisex T-Shirt ($15.99)
- Premium T-Shirt ($19.99)
- Hooded Sweatshirt ($29.99)
- Long Sleeve T-Shirt ($18.99)
- Tank Top ($12.99)

### Testing the System

1. **Setup API Keys**
   - Add Printful API key to `.env`
   - Add Stripe keys to `.env`

2. **Test Order Flow**
   - Visit `/designs` to see designs with order buttons
   - Click cart icon to start order
   - Select products and complete payment
   - Check order history at `/orders/history`

3. **Verify Integration**
   - Check Stripe dashboard for payments
   - Check Printful dashboard for orders
   - Verify order status updates

## Security Considerations

1. **Payment Security**
   - Payment data never stored locally
   - Stripe handles all sensitive payment information
   - PCI compliance through Stripe

2. **API Security**
   - API keys stored securely in environment variables
   - CSRF protection on all forms
   - Input validation and sanitization

3. **Order Security**
   - Users can only order their own designs
   - Order ownership verification
   - Secure order number generation

## Error Handling

The system includes comprehensive error handling:
- API failure recovery
- Payment failure handling
- Order status error management
- User-friendly error messages
- Logging for debugging

## Future Enhancements

Potential improvements:
1. **Webhook Integration**
   - Stripe webhooks for payment status
   - Printful webhooks for order updates

2. **Advanced Features**
   - Bulk ordering
   - Discount codes
   - Shipping options
   - Order notifications

3. **Analytics**
   - Order analytics
   - Sales reporting
   - Product performance tracking

## Support

For issues or questions:
1. Check the logs in `storage/logs/`
2. Verify API key configuration
3. Test with sample data first
4. Review error messages in browser console 
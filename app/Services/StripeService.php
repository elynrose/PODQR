<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent
     */
    public function createPaymentIntent($amount, $currency = 'usd', $metadata = [])
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount, // Amount in cents
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Intent Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Confirm a payment intent
     */
    public function confirmPaymentIntent($paymentIntentId, $paymentMethodId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $paymentIntent->confirm([
                'payment_method' => $paymentMethodId,
            ]);

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Confirm Payment Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve a payment intent
     */
    public function getPaymentIntent($paymentIntentId)
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Get Payment Intent Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create or retrieve a customer
     */
    public function createOrRetrieveCustomer($email, $name = null)
    {
        try {
            // First, try to find existing customer
            $customers = Customer::search([
                'query' => "email:'$email'",
            ]);

            if (!empty($customers->data)) {
                return $customers->data[0];
            }

            // Create new customer if not found
            $customerData = ['email' => $email];
            if ($name) {
                $customerData['name'] = $name;
            }

            return Customer::create($customerData);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Customer Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get customer by ID
     */
    public function getCustomer($customerId)
    {
        try {
            return Customer::retrieve($customerId);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Get Customer Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment($paymentIntentId, $amount = null)
    {
        try {
            $refundData = ['payment_intent' => $paymentIntentId];
            if ($amount) {
                $refundData['amount'] = $amount;
            }

            return \Stripe\Refund::create($refundData);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get payment methods for a customer
     */
    public function getCustomerPaymentMethods($customerId)
    {
        try {
            return \Stripe\PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'card',
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Get Payment Methods Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod($paymentMethodId, $customerId)
    {
        try {
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);
            return $paymentMethod;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Attach Payment Method Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate tax for an order
     */
    public function calculateTax($amount, $address)
    {
        try {
            $taxCalculation = \Stripe\Tax\Calculation::create([
                'currency' => 'usd',
                'line_items' => [
                    [
                        'amount' => $amount,
                        'reference' => 'order_total',
                    ],
                ],
                'customer_details' => [
                    'address' => [
                        'line1' => $address['address1'] ?? '',
                        'city' => $address['city'] ?? '',
                        'state' => $address['state_code'] ?? '',
                        'postal_code' => $address['zip'] ?? '',
                        'country' => $address['country_code'] ?? '',
                    ],
                    'address_source' => 'shipping',
                ],
            ]);

            return $taxCalculation;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Tax Calculation Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a Stripe Checkout session
     */
    public function createCheckoutSession($orderData, $successUrl, $cancelUrl)
    {
        try {
            \Log::info('StripeService: Creating checkout session', [
                'orderData' => $orderData,
                'successUrl' => $successUrl,
                'cancelUrl' => $cancelUrl
            ]);
            
            $lineItems = [];
            
            foreach ($orderData['items'] as $item) {
                $productName = is_array($item['product']) ? $item['product']['name'] : $item['product']->name;
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $productName,
                            'description' => "Size: {$item['size']}, Color: {$item['color']}",
                        ],
                        'unit_amount' => (int)($item['unit_price'] * 100), // Convert to cents
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            // Add shipping cost as a separate line item
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Shipping',
                        'description' => 'Standard shipping',
                    ],
                    'unit_amount' => 599, // $5.99 in cents
                ],
                'quantity' => 1,
            ];

            \Log::info('StripeService: Line items prepared', ['lineItems' => $lineItems]);

            // Prepare metadata - only include design_id if it exists
            $metadata = [
                'order_id' => $orderData['order_id'] ?? '',
                'user_id' => $orderData['user_id'] ?? '',
                'total_amount' => $orderData['total'] ?? '',
            ];
            
            // Only add design_id if it's provided
            if (isset($orderData['design_id']) && !empty($orderData['design_id'])) {
                $metadata['design_id'] = $orderData['design_id'];
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'metadata' => $metadata,
                'customer_email' => $orderData['shipping_address']['email'] ?? null,
                'shipping_address_collection' => [
                    'allowed_countries' => ['US', 'CA', 'GB', 'AU'],
                ],
                'automatic_tax' => [
                    'enabled' => true,
                ],
            ]);

            \Log::info('StripeService: Session created successfully', ['session_id' => $session->id]);

            return $session;
        } catch (ApiErrorException $e) {
            \Log::error('StripeService: Stripe API Error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        } catch (\Exception $e) {
            \Log::error('StripeService: General Error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Retrieve a checkout session
     */
    public function getCheckoutSession($sessionId)
    {
        try {
            return \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Get Checkout Session Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process refund for an order using session ID
     */
    public function processRefund($sessionId, $amount)
    {
        try {
            // Get the session to find the payment intent
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            
            if (!$session || !$session->payment_intent) {
                return [
                    'success' => false,
                    'message' => 'No payment intent found for this session'
                ];
            }

            // Process the refund
            $refund = $this->refundPayment($session->payment_intent, $amount * 100); // Convert to cents
            
            if ($refund) {
                \Log::info('Refund processed successfully', [
                    'session_id' => $sessionId,
                    'payment_intent' => $session->payment_intent,
                    'refund_id' => $refund->id,
                    'amount' => $amount
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'refund_id' => $refund->id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to process refund'
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('Error processing refund: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'amount' => $amount
            ]);
            
            return [
                'success' => false,
                'message' => 'Error processing refund: ' . $e->getMessage()
            ];
        }
    }
} 
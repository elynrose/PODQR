<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\StripeService;
use App\Mail\OrderFailedNotification;
use Illuminate\Support\Facades\Mail;

class FixOrderSessionId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:order-session-id {order_id? : Specific order ID to fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix session ID for existing orders and process refunds for failed orders';

    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        if ($orderId) {
            $this->fixSpecificOrder($orderId);
        } else {
            $this->fixAllOrders();
        }

        return 0;
    }

    private function fixSpecificOrder($orderId)
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return;
        }

        $this->info("Processing order #{$order->order_number} (ID: {$order->id})");
        $this->processOrder($order);
    }

    private function fixAllOrders()
    {
        $this->info('Finding orders that need session ID fixes...');
        
        // Find orders that are paid but have no session ID and no Printful order ID
        $orders = Order::where('status', 'paid')
            ->whereNull('stripe_session_id')
            ->whereNull('printful_order_id')
            ->get();

        $this->info("Found {$orders->count()} orders to process");

        foreach ($orders as $order) {
            $this->info("\nProcessing order #{$order->order_number} (ID: {$order->id})");
            $this->processOrder($order);
        }
    }

    private function processOrder(Order $order)
    {
        $this->info("- Status: {$order->status}");
        $this->info("- Total: \${$order->total}");
        $this->info("- Payment Intent: {$order->stripe_payment_intent_id}");
        $this->info("- Session ID: " . ($order->stripe_session_id ?? 'NULL'));
        $this->info("- Printful Order ID: " . ($order->printful_order_id ?? 'NULL'));

        // If order has no Printful order ID, it means Printful submission failed
        if (!$order->printful_order_id) {
            $this->warn("Order has no Printful order ID - this indicates Printful submission failed");
            
            if ($this->confirm('Do you want to process a refund for this order?')) {
                $this->processRefundForOrder($order);
            }
        } else {
            $this->info("Order has Printful order ID - no action needed");
        }
    }

    private function processRefundForOrder(Order $order)
    {
        try {
            $this->info("Processing refund for order #{$order->order_number}...");
            
            // Since we don't have the session ID, we'll use the payment intent directly
            if ($order->stripe_payment_intent_id) {
                $refund = $this->stripeService->refundPayment($order->stripe_payment_intent_id);
                
                if ($refund) {
                    $this->info("✅ Refund processed successfully!");
                    $this->info("Refund ID: {$refund->id}");
                    
                    // Update order status
                    $order->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => 'Printful order creation failed - no design image available (refunded via command)'
                    ]);
                    
                    // Send email notification
                    $this->sendOrderFailedEmail($order, 'Your order could not be processed because no design image was available for printing. A full refund has been processed.');
                    
                    $this->info("Order status updated to 'cancelled'");
                    $this->info("Email notification sent to {$order->user->email}");
                } else {
                    $this->error("❌ Failed to process refund");
                }
            } else {
                $this->error("❌ No payment intent ID found for order");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error processing refund: " . $e->getMessage());
        }
    }

    private function sendOrderFailedEmail(Order $order, string $reason)
    {
        try {
            $this->info("Sending email notification to {$order->user->email}...");
            
            Mail::to($order->user->email)->send(new OrderFailedNotification($order, $reason));
            
            $this->info("✅ Email sent successfully");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;

class FixFailedRefunds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refunds:fix {order_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix failed refunds for orders';

    protected $stripeService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(StripeService $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        if ($orderId) {
            $this->info("Attempting to fix refund for order #{$orderId}...");
            $this->fixOrderRefund($orderId);
        } else {
            $this->info("Finding orders with failed refunds...");
            
            // Find orders that failed refund processing
            $failedOrders = Order::where('status', 'error')
                ->whereNotNull('stripe_session_id')
                ->where(function($query) {
                    $query->where('notes', 'like', '%refund%')
                          ->orWhere('notes', 'like', '%Refund%');
                })
                ->get();
            
            if ($failedOrders->isEmpty()) {
                $this->info("No orders with failed refunds found.");
                return 0;
            }
            
            $this->info("Found {$failedOrders->count()} orders with failed refunds.");
            
            foreach ($failedOrders as $order) {
                $this->info("Processing order #{$order->id}...");
                $this->fixOrderRefund($order->id);
            }
        }
        
        return 0;
    }
    
    protected function fixOrderRefund($orderId)
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            $this->error("Order #{$orderId} not found!");
            return;
        }
        
        $this->info("Order found: {$order->order_number} (Status: {$order->status})");
        
        if (!$order->stripe_session_id) {
            $this->error("Order has no Stripe session ID. Cannot process refund.");
            return;
        }
        
        if ($order->status !== 'error') {
            $this->warn("Order status is '{$order->status}', not 'error'. Skipping.");
            return;
        }
        
        try {
            // Attempt to process refund with corrected logic
            $refundResult = $this->stripeService->processRefund($order->stripe_session_id, $order->total);
            
            if ($refundResult['success']) {
                $this->info("✅ Refund processed successfully!");
                $this->info("Refund ID: {$refundResult['refund_id']}");
                $this->info("Refunded amount: \${$refundResult['refunded_amount']}");
                
                // Update order status
                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Printful order failed - refund processed',
                    'notes' => 'Refund processed successfully on ' . now()->toDateTimeString()
                ]);
                
                $this->info("Order status updated to: cancelled");
                
            } else {
                $this->error("❌ Failed to process refund: {$refundResult['message']}");
                
                // Update order notes with the error
                $order->update([
                    'notes' => 'Refund processing failed: ' . $refundResult['message'] . ' (Attempted on ' . now()->toDateTimeString() . ')'
                ]);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception occurred: " . $e->getMessage());
            Log::error("FixFailedRefunds command error: " . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 
<?php

namespace App\Http\Controllers\Api;

use App\Models\EstoWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Repositories\OrderRepository;

class EstoOrderController
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    /**
     * Get order by Esto reference
     * Public endpoint for WordPress thank-you page
     */
    public function getByReference(Request $request, string $reference): JsonResponse
    {
        // Validate reference format (cart-123-timestamp-random)
        if (! preg_match('/^cart-\d+-\d+-[a-zA-Z0-9]+$/', $reference)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reference format',
            ], 400);
        }

        // Find webhook by reference
        $webhook = EstoWebhook::where('reference', $reference)->first();

        if (! $webhook) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'reference' => $reference,
            ], 404);
        }

        // If order not created yet (webhook received but order creation pending/failed)
        if (! $webhook->order_id) {
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment received, order is being processed',
                'reference' => $reference,
                'payment_status' => $webhook->status,
                'amount' => $webhook->amount,
                'currency' => $webhook->currency,
            ]);
        }

        // Load order
        $order = $this->orderRepository->find($webhook->order_id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'reference' => $reference,
            ], 404);
        }

        // Return safe order data (no sensitive info)
        return response()->json([
            'success' => true,
            'status' => 'completed',
            'order' => [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'status' => $order->status,
                'grand_total' => $order->grand_total,
                'currency_code' => $order->order_currency_code,
                'customer_email' => $order->customer_email,
                'customer_first_name' => $order->customer_first_name,
                'customer_last_name' => $order->customer_last_name,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ],
            'reference' => $reference,
            'payment_status' => $webhook->status,
        ]);
    }
}

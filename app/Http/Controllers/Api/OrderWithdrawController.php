<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderWithdrawnNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderCommentRepository;

/**
 * EU 14-day right-of-withdrawal — one-click endpoint for logged-in customer.
 *
 * Customer presses "Tagane ostust" on their order page; we flip the order
 * status to "withdrawn" and notify the shop owner by email so they see it
 * immediately in their inbox AND in the Bagisto admin order list.
 *
 * We intentionally do NOT touch inventory or refunds here — those are
 * manual / commercial decisions for the shop owner once they see the
 * notification. The status flag is the signal.
 */
class OrderWithdrawController extends Controller
{
    public function __construct(protected OrderCommentRepository $orderCommentRepository) {}

    #[OA\Post(
        path: '/api/v1/customer/orders/{id}/withdraw',
        operationId: 'withdrawOrder',
        summary: 'EU 14-day right-of-withdrawal — one-click order cancellation',
        description: 'Sets order status to "withdrawn" and emails the shop owner. Customer must be authenticated and own the order. Idempotent: orders already in withdrawn/canceled/closed state return 422.',
        tags: ['Aiamaailm Customer'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Order ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'order_id', type: 'integer'),
                        new OA\Property(property: 'status',   type: 'string', example: 'withdrawn'),
                    ]),
                ]
            )),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 404, description: 'Order not found or not owned by customer'),
            new OA\Response(response: 422, description: 'Order already in terminal state'),
        ]
    )]
    public function withdraw(Request $request, int $orderId): JsonResponse
    {
        $customer = $request->user();

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::find($orderId);

        if (! $order || (int) $order->customer_id !== (int) $customer->id) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Already withdrawn or in a terminal state — idempotent / no-op.
        if (in_array($order->status, [Order::STATUS_WITHDRAWN, Order::STATUS_CANCELED, Order::STATUS_CLOSED], true)) {
            return response()->json([
                'message' => 'Order is already in a final state and cannot be withdrawn.',
                'data'    => ['order_id' => $order->id, 'status' => $order->status],
            ], 422);
        }

        $previousStatus = $order->status;

        $order->status = Order::STATUS_WITHDRAWN;
        $order->save();

        // Audit trail: pin a comment on the order so it shows up in admin history.
        try {
            $this->orderCommentRepository->create([
                'order_id'           => $order->id,
                'comment'            => sprintf(
                    'Klient taganes tellimusest %s (EL 14p taganemisõigus). Eelmine staatus: %s.',
                    $order->increment_id ?? '#'.$order->id,
                    $previousStatus,
                ),
                'customer_notified'  => 0,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Order withdrawal: failed to add comment', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        // Notify shop owner.
        try {
            $adminEmail = config('mail.admin.email')
                ?? optional(core()->getAdminEmailDetails())['email']
                ?? config('mail.from.address');

            if ($adminEmail) {
                Mail::to($adminEmail)->send(new OrderWithdrawnNotification($order, $previousStatus));
            }
        } catch (\Throwable $e) {
            Log::warning('Order withdrawal: email to admin failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Tellimus taganetud. Saatsime poele teate.',
            'data'    => [
                'order_id' => $order->id,
                'status'   => $order->status,
            ],
        ]);
    }
}

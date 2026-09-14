<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\RestApi\Http\Resources\V1\Shop\Sales\OrderResource;
use Webkul\Sales\Models\Order;

/**
 * Override of vendor's GET /api/v1/customer/orders.
 *
 * Two reasons for this override:
 *
 *  1. Email-based fallback for legacy NULL-customer_id orders. Some orders
 *     ended up with customer_id = NULL even for a logged-in buyer (Bearer-
 *     token guest-cart flow before we patched save-order). The fallback lets
 *     those still appear under "Minu tellimused".
 *
 *  2. Eager-load relations + return through OrderResource. The vendor
 *     OrderResource always emits `items`, `shipping_address`,
 *     `billing_address`, and `payment_title` — but only when those relations
 *     are actually loaded on the model. Without `with([...])` we serialize
 *     bare Eloquent attributes and the SPA sees an empty `items` array.
 */
class CustomerOrderListController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user();

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = Order::query()
            ->with([
                'items.product',
                'addresses',     // single relation; shipping_address & billing_address are accessors filtering this
                'payment',
                'channel',
                'customer',
                'invoices',
                'shipments',
            ])
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhere(function ($qq) use ($customer) {
                      $qq->whereNull('customer_id')
                         ->where('customer_email', $customer->email);
                  });
            });

        if ($status = $request->input('status')) {
            $statuses = array_map('trim', explode(',', $status));
            $query->whereIn('status', $statuses);
        }

        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query->orderBy($sort, $order);

        $limit = (int) ($request->input('limit') ?? 10);

        $paginate = $request->input('pagination');
        if (is_null($paginate) || filter_var($paginate, FILTER_VALIDATE_BOOLEAN)) {
            $page = $query->paginate($limit);

            return response()->json([
                'data' => OrderResource::collection(collect($page->items())),
                'meta' => [
                    'current_page' => $page->currentPage(),
                    'last_page'    => $page->lastPage(),
                    'per_page'     => $page->perPage(),
                    'total'        => $page->total(),
                ],
            ]);
        }

        return response()->json([
            'data' => OrderResource::collection($query->get()),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $customer = $request->user();

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::query()
            ->with(['items.product', 'addresses', 'payment', 'channel', 'customer', 'invoices', 'shipments', 'comments'])
            ->where('id', $id)
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhere(function ($qq) use ($customer) {
                      $qq->whereNull('customer_id')
                         ->where('customer_email', $customer->email);
                  });
            })
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }
}

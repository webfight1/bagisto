<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\RestApi\Http\Resources\V1\Shop\Customer\CustomerResource;

/**
 * Override vendor login so that an active guest cart (identified by the
 * X-Cart-Token request header) is bound to the customer on login. This is
 * the standard e-commerce "cart merge" pattern that Magento/Shopify/Woo all
 * do for the same reason — without it the SPA loses the cart between guest
 * browsing and customer checkout.
 *
 * It also returns the merge result so the frontend can show a "kindlate
 * tagasi! Su korv lisatud (N toodet)" toast.
 */
class CustomerLoginController extends Controller
{
    public function __construct(protected CustomerRepository $customerRepository) {}

    #[OA\Post(
        path: '/api/v1/customer/login',
        operationId: 'aiamaailmCustomerLogin',
        summary: 'Customer login (with optional guest-cart merge)',
        description: 'Validates credentials, issues a Sanctum token, and — if `X-Cart-Token` header is present — binds the matching guest cart to the customer so checkout endpoints find it. Returns `cart_merged: true` if a merge happened.',
        tags: ['Aiamaailm Customer'],
        parameters: [
            new OA\Parameter(name: 'X-Cart-Token', in: 'header', required: false, description: 'Active guest cart token, for auto-merge on login.', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password', 'device_name'],
            properties: [
                new OA\Property(property: 'email',       type: 'string', format: 'email'),
                new OA\Property(property: 'password',    type: 'string', minLength: 6),
                new OA\Property(property: 'device_name', type: 'string', example: 'browser'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'token',       type: 'string'),
                new OA\Property(property: 'data',        type: 'object', description: 'Customer record'),
                new OA\Property(property: 'cart_merged', type: 'boolean'),
                new OA\Property(property: 'message',     type: 'string'),
            ])),
            new OA\Response(response: 422, description: 'Invalid credentials or validation error'),
        ]
    )]
    public function login(Request $request): Response
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required',
            'device_name' => 'required',
        ]);

        $customer = $this->customerRepository->where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => trans('rest-api::app.shop.customer.accounts.error.credential-error'),
            ]);
        }

        // NB: vendor login deletes all previous tokens (`$customer->tokens()->delete()`).
        // That bricks any other browser tab / device the customer was using and
        // forces the SPA into a guest-flow fallback. We keep tokens around — the
        // SPA logout endpoint already deletes the current token explicitly when
        // the user signs out.

        Event::dispatch('customer.after.login', $customer);

        // Auto-merge: if the request carries a guest-cart token, bind that cart
        // to this customer so subsequent /customer/checkout/* calls see it.
        $cartMerged = $this->maybeMergeGuestCart($request, $customer);

        return response([
            'token'       => $customer->createToken($request->device_name, ['role:customer'])->plainTextToken,
            'data'        => new CustomerResource($customer),
            'cart_merged' => $cartMerged,
            'message'     => trans('rest-api::app.shop.customer.accounts.success.login'),
        ]);
    }

    /**
     * Decrypt the X-Cart-Token (an encrypted cart_id), find the cart, and
     * rebind it to the logged-in customer. Idempotent: returns false on any
     * decrypt / lookup failure so login always succeeds.
     */
    protected function maybeMergeGuestCart(Request $request, $customer): bool
    {
        $token = $request->header('X-Cart-Token')
            ?: $request->query('cart_token')
            ?: $request->input('cart_token');

        if (! $token) {
            return false;
        }

        try {
            $cartId = (int) Crypt::decryptString($token);
        } catch (\Throwable $e) {
            Log::info('login merge: invalid X-Cart-Token, skipping', ['error' => $e->getMessage()]);
            return false;
        }

        $cart = DB::table('cart')->where('id', $cartId)->where('is_active', 1)->first();

        if (! $cart) {
            return false;
        }

        // Already this customer's cart — nothing to do.
        if ((int) $cart->customer_id === (int) $customer->id) {
            return false;
        }

        // If customer has another active cart, retire it; we prefer the guest
        // cart since that's the one the user has been filling right now.
        DB::table('cart')
            ->where('customer_id', $customer->id)
            ->where('id', '!=', $cartId)
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        DB::table('cart')->where('id', $cartId)->update([
            'customer_id'         => $customer->id,
            'customer_email'      => $customer->email,
            'customer_first_name' => $cart->customer_first_name ?: $customer->first_name,
            'customer_last_name'  => $cart->customer_last_name ?: $customer->last_name,
            'is_guest'            => 0,
            'updated_at'          => now(),
        ]);

        Log::info('login merge: guest cart bound to customer', [
            'cart_id'     => $cartId,
            'customer_id' => $customer->id,
        ]);

        return true;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Response;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\CartResource;

class GuestCartController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected ProductRepository $productRepository,
    ) {}

    public function create(Request $request): JsonResource
    {
        $cart = $this->getCartFromToken($request);

        if (! $cart) {
            $cart = Cart::createCart([]);
        } else {
            Cart::setCart($cart);
        }

        if ($cart->items?->count()) {
            Cart::collectTotals();
        }

        return new JsonResource([
            'data' => [
                'cart_token' => $this->makeToken($cart->id),
                'cart'       => new CartResource(Cart::getCart()),
            ],
        ]);
    }

    public function show(Request $request): JsonResource
    {
        $cart = $this->requireCartFromToken($request);

        Cart::setCart($cart);

        if ($cart->items?->count()) {
            Cart::collectTotals();
        }

        return new JsonResource([
            'data' => [
                'cart_token' => $this->makeToken($cart->id),
                'cart'       => new CartResource(Cart::getCart()),
            ],
        ]);
    }

    public function addItem(Request $request): JsonResource
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'is_buy_now' => 'integer|in:0,1',
            'quantity'   => 'integer|min:1',
        ]);

        $cart = $this->getCartFromToken($request);

        if (! $cart) {
            $cart = Cart::createCart([]);
        } else {
            Cart::setCart($cart);
        }

        $product = $this->productRepository->with('parent')->findOrFail($request->input('product_id'));

        try {
            $cart = Cart::addProduct($product, $request->all());

            return new JsonResource([
                'data' => [
                    'cart_token' => $this->makeToken($cart->id),
                    'cart'       => new CartResource($cart),
                ],
            ]);
        } catch (\Exception $e) {
            return (new JsonResource([
                'message' => $e->getMessage(),
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateItems(Request $request): JsonResource
    {
        $cart = $this->requireCartFromToken($request);

        Cart::setCart($cart);

        try {
            Cart::updateItems($request->input());

            return new JsonResource([
                'data' => [
                    'cart_token' => $this->makeToken($cart->id),
                    'cart'       => new CartResource(Cart::getCart()),
                ],
            ]);
        } catch (\Exception $e) {
            return (new JsonResource([
                'message' => $e->getMessage(),
            ]))->response()->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
    }

    public function removeItem(Request $request, int $cartItemId): JsonResource
    {
        $cart = $this->requireCartFromToken($request);

        Cart::setCart($cart);

        Cart::removeItem($cartItemId);

        if (Cart::getCart()?->items?->count()) {
            Cart::collectTotals();
        }

        return new JsonResource([
            'data' => [
                'cart_token' => $this->makeToken($cart->id),
                'cart'       => Cart::getCart() ? new CartResource(Cart::getCart()) : null,
            ],
        ]);
    }

    protected function makeToken(int $cartId): string
    {
        return Crypt::encryptString((string) $cartId);
    }

    protected function getCartFromToken(Request $request)
    {
        $token = $request->header('X-Cart-Token') ?: $request->query('cart_token') ?: $request->input('cart_token');

        if (! $token) {
            return null;
        }

        try {
            $cartId = (int) Crypt::decryptString($token);
        } catch (\Exception $e) {
            return null;
        }

        $cart = $this->cartRepository->find($cartId);

        if (! $cart) {
            return null;
        }

        if (! $cart->is_active) {
            return null;
        }

        if (! $cart->is_guest) {
            return null;
        }

        return $cart;
    }

    protected function requireCartFromToken(Request $request)
    {
        $cart = $this->getCartFromToken($request);

        abort_if(! $cart, 401, 'Guest cart token missing or invalid');

        return $cart;
    }
}

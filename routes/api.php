<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttributeProductController;
use App\Http\Controllers\Api\CategoryProductController;
use App\Http\Controllers\Api\GuestCartController;
use App\Http\Controllers\Api\CustomerCheckoutController;
use App\Http\Controllers\Api\GuestCheckoutController;
use App\Http\Controllers\Api\CartParcelLockerController;
use App\Http\Controllers\Api\DpdController;
use App\Http\Controllers\Api\OmnivaController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SmartpostFiController;
use App\Http\Controllers\Api\SmartpostController;
use App\Http\Controllers\Api\SingleProductController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\ProductPopularityController;
use Webkul\Esto\Http\Controllers\EstoWebhookController;
use Webkul\Shop\Http\Controllers\API\ProductController;
use Webkul\Shop\Http\Controllers\API\ReviewController;

// Override vendor customer checkout save-address (fix: save addresses before collecting rates)
Route::middleware('auth:sanctum')->post('/v1/customer/checkout/save-address', [CustomerCheckoutController::class, 'saveAddress']);

// Save shipping method and parcel locker for authenticated customers
Route::middleware('auth:sanctum')->post('/v1/customer/checkout/save-shipping', [CustomerCheckoutController::class, 'saveShipping']);

// Verify-endpoint for the parcel locker currently saved on the cart. Called
// by the WP frontend right before placeOrder as a safety gate so that an
// order is never placed without a confirmed pickup location.
Route::middleware('auth:sanctum')->get('/v1/customer/checkout/parcel-locker', [CartParcelLockerController::class, 'showForCustomer']);

Route::prefix('attribute')->group(function () {
    Route::get('/brand/{value}', [AttributeProductController::class, 'byBrand']);

    Route::get('/{attribute_code}/{value}', [AttributeProductController::class, 'byAttribute']);
});

Route::get('/products/popular/{limit?}', [ProductPopularityController::class, 'index']);

// Single product by slug
Route::get('/v1/product/{slug}', [SingleProductController::class, 'show']);

// Category products by slug
Route::get('/v1/category/{slug}', [CategoryProductController::class, 'index']);

Route::post('/payments/esto/webhook', [\App\Http\Controllers\Api\EstoWebhookController::class, 'handle'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('esto.webhook');

// Public endpoint for WordPress thank-you page to fetch order by reference
Route::get('/esto/order-by-reference/{reference}', [\App\Http\Controllers\Api\EstoOrderController::class, 'getByReference'])
    ->name('esto.order-by-reference');

// Categories list
Route::get('/v1/category', [CategoryProductController::class, 'categories']);

// Public sitemap data for WordPress
Route::get('/v1/sitemap', [SitemapController::class, 'index']);

// Guest cart (token-based)
Route::prefix('v1/guest')->group(function () {
    Route::post('/cart', [GuestCartController::class, 'create']);
    Route::get('/cart', [GuestCartController::class, 'show']);
    Route::post('/cart/items', [GuestCartController::class, 'addItem']);
    Route::put('/cart/items', [GuestCartController::class, 'updateItems']);
    Route::delete('/cart/items/{cartItemId}', [GuestCartController::class, 'removeItem']);

    Route::post('/cart/coupon', [GuestCartController::class, 'applyCoupon']);
    Route::delete('/cart/coupon', [GuestCartController::class, 'removeCoupon']);

    Route::prefix('checkout')->group(function () {
        Route::post('/addresses', [GuestCheckoutController::class, 'storeAddresses']);
        Route::get('/shipping-methods', [GuestCheckoutController::class, 'shippingMethods']);
        Route::post('/shipping-method', [GuestCheckoutController::class, 'storeShippingMethod']);
        Route::get('/payment-methods', [GuestCheckoutController::class, 'paymentMethods']);
        Route::post('/payment-method', [GuestCheckoutController::class, 'storePaymentMethod']);
        Route::get('/payment-status', [GuestCheckoutController::class, 'paymentStatus']);
        Route::post('/place-order', [GuestCheckoutController::class, 'placeOrder']);

        // Verify endpoint — returns the locker currently persisted on the
        // guest cart (resolved via X-Cart-Token). Called by WP right before
        // placeOrder as a safety gate.
        Route::get('/parcel-locker', [CartParcelLockerController::class, 'showForGuest']);
    });
});

// Catalog product listing with filters via query params (category_id, price_min, price_max, ...)
Route::get('/v1/catalog/products', [ProductController::class, 'index']);

// Price range (min/max) for category filters
Route::get('/v1/catalog/price-range', [ProductController::class, 'priceRange']);

// Public product reviews listing (approved only)
Route::get('/v1/products/{id}/reviews', [ReviewController::class, 'index']);

Route::get('/v1/search', [SearchController::class, 'search']);
Route::get('/v1/dpd/locations', [DpdController::class, 'locations']);
Route::get('/v1/omniva/locations', [OmnivaController::class, 'locations']);
Route::get('/v1/smartpost/locations', [SmartpostController::class, 'locations']);
Route::get('/v1/smartpost/fi/locations', [SmartpostFiController::class, 'locations']);

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/products/{id}/review', [ReviewController::class, 'store']);
});

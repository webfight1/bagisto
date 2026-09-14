<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttributeProductController;
use App\Http\Controllers\Api\CategoryProductController;
use App\Http\Controllers\Api\GuestCartController;
use App\Http\Controllers\Api\CustomerAddressController;
use App\Http\Controllers\Api\CustomerCheckoutController;
use App\Http\Controllers\Api\CustomerLoginController;
use App\Http\Controllers\Api\CustomerOrderListController;
use App\Http\Controllers\Api\CustomerSaveOrderController;
use App\Http\Controllers\Api\FeaturedProductsController;
use App\Http\Controllers\Api\GuestCheckoutController;
use App\Http\Controllers\Api\CartParcelLockerController;
use App\Http\Controllers\Api\DpdController;
use App\Http\Controllers\Api\CustomerResetPasswordController;
use App\Http\Controllers\Api\OmnivaController;
use App\Http\Controllers\Api\OrderWithdrawController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ProductsListGroupPriceController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SmartpostFiController;
use App\Http\Controllers\Api\SmartpostController;
use App\Http\Controllers\Api\SingleProductController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\ProductPopularityController;
use Webkul\Esto\Http\Controllers\EstoWebhookController;
use Webkul\Shop\Http\Controllers\API\ProductController;
use Webkul\Shop\Http\Controllers\API\ReviewController;

// Override vendor customer addresses endpoints to include company_reg field
Route::middleware(['auth:sanctum', 'sanctum.customer'])->prefix('v1/customer/addresses')->group(function () {
    Route::get('', [CustomerAddressController::class, 'allResources']);
    Route::get('{id}', [CustomerAddressController::class, 'getResource']);
    Route::post('', [CustomerAddressController::class, 'store']);
    Route::put('{id}', [CustomerAddressController::class, 'update']);
});

// Override vendor customer checkout save-address (fix: save addresses before collecting rates)
Route::middleware('auth:sanctum')->post('/v1/customer/checkout/save-address', [CustomerCheckoutController::class, 'saveAddress']);

// Save shipping method and parcel locker for authenticated customers
Route::middleware('auth:sanctum')->post('/v1/customer/checkout/save-shipping', [CustomerCheckoutController::class, 'saveShipping']);

// Get available payment methods for authenticated customers
Route::middleware('auth:sanctum')->get('/v1/customer/checkout/payment-methods', [CustomerCheckoutController::class, 'paymentMethods']);
Route::middleware('auth:sanctum')->get('/v1/customer/checkout/shipping-methods', [CustomerCheckoutController::class, 'shippingMethods']);

// Override vendor login so an active X-Cart-Token guest cart is bound to the customer (auto-merge).
Route::post('/v1/customer/login', [CustomerLoginController::class, 'login']);

// Override vendor save-order so customer_id is persisted from Bearer token.
Route::middleware('auth:sanctum')->post('/v1/customer/checkout/save-order', [CustomerSaveOrderController::class, 'saveOrder']);

// Override vendor orders listing — adds email-based fallback for legacy NULL-customer_id orders.
Route::middleware('auth:sanctum')->get('/v1/customer/orders', [CustomerOrderListController::class, 'index']);
Route::middleware('auth:sanctum')->get('/v1/customer/orders/{id}', [CustomerOrderListController::class, 'show'])
    ->whereNumber('id'); // keep /orders/reorder/{id} untouched

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

    // EU 14-day right-of-withdrawal — one-click for logged-in customer.
    Route::post('/customer/orders/{id}/withdraw', [OrderWithdrawController::class, 'withdraw']);
});

// Public CMS pages (managed via Filament at /cms)
Route::get('/v1/pages/{slug}', [PageController::class, 'show']);

// Top navigation menu — Header.tsx frontend'is loeb siin ja renderdab.
Route::get('/v1/menu', [\App\Http\Controllers\Api\MenuController::class, 'index'])->name('menu.index');

// News / blog posts (Filament CMS-i /cms/news-posts alt hallatavad)
Route::get('/v1/news', [\App\Http\Controllers\Api\NewsController::class, 'index'])->name('news.index');
Route::get('/v1/news/{slug}', [\App\Http\Controllers\Api\NewsController::class, 'show'])->name('news.show');

// Category metadata endpoint — name/description/meta/image for SEO + OG-tags.
// (List endpoint /v1/category/{slug} tagastab tooteid — see uus tagastab meta.)
Route::get('/v1/category/{slug}/detail', [\App\Http\Controllers\Api\CategoryDetailController::class, 'show'])->name('category.detail');

// Featured products (admin Bagisto featured=1).
// Path is /featured-products (not /products/featured) to avoid clashing with
// vendor's GET /api/v1/products/{id} which would otherwise match "featured" as id.
Route::get('/v1/featured-products', [FeaturedProductsController::class, 'index']);

// Override vendor GET /api/v1/products so customer-group catalog rule discounts
// are applied transparently. Lovable already calls this URL — we wrap vendor's
// response and adjust the price.
Route::get('/v1/products', [ProductsListGroupPriceController::class, 'index']);

// Public reset-password (Bagisto only exposes forgot; this completes the flow)
Route::post('/v1/customer/reset-password', [CustomerResetPasswordController::class, 'reset']);

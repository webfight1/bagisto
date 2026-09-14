<?php

namespace App\Providers;

use App\Http\Controllers\Api\CustomerCheckoutController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Listeners\CreateMeritInvoice;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register before vendor REST API routes so this takes precedence
        // over the vendor's CheckoutController which calls Shipping::collectRates()
        // before saving the address (causes abort(400) on fresh carts).
        // Middleware mirrors vendor's full stack: RestApiServiceProvider + shop.php + customers-routes.php
        Route::prefix('api')
            ->middleware(['api', 'etag', 'sanctum.locale', 'sanctum.currency', 'auth:sanctum', 'sanctum.customer'])
            ->post('v1/customer/checkout/save-address', [CustomerCheckoutController::class, 'saveAddress']);

        // Override vendor Webkul\RestApi customer profile endpoints to persist
        // company_name / company_reg / vat_id (added on customers table).
        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum', 'sanctum.customer'])
            ->group(function () {
                Route::get('v1/customer/get',      [CustomerProfileController::class, 'get']);
                Route::put('v1/customer/profile',  [CustomerProfileController::class, 'update']);
            });

        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Filament uses <dynamic-component> in its blade templates; Bagisto's
        // component registration apparently drops Laravel's default aliasing of
        // this, so we register it explicitly here.
        Blade::component('dynamic-component', \Illuminate\View\DynamicComponent::class);

        LogViewer::auth(function ($request) {
            $allowed = array_filter(array_map('trim', explode(',', env('LOG_VIEWER_ALLOWED_IPS', ''))));
            return in_array($request->ip(), $allowed);
        });
        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });

        // Register Merit invoice creation listener.
        // Triggers on sales.order.update-status.after which is dispatched both
        // by OrderRepository::updateStatus() and explicitly by the ESTO webhook
        // after it sets the order status to processing.
        // Note: sales.order.save.after does not exist in Bagisto – removed.
        if (config('merit-invoice.enabled', true)) {
            Event::listen('sales.order.update-status.after', CreateMeritInvoice::class);
        }
    }
}

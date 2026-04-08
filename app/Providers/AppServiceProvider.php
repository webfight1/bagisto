<?php

namespace App\Providers;

use App\Listeners\CreateMeritInvoice;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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

<?php

namespace App\Providers;

use App\Listeners\GenerateOrderInvoice;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/aiamaailm-invoice.php'),
            'aiamaailm-invoice'
        );
    }

    public function boot(): void
    {
        // Fired by Bagisto right after an order is persisted (Esto webhook,
        // guest place-order, admin manual order — all paths). Idempotent: the
        // service refuses to regenerate if the PDF already exists on disk.
        Event::listen('checkout.order.save.after', GenerateOrderInvoice::class);
    }
}

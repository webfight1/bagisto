<?php

namespace App\Providers;

use App\Listeners\SeedPriceGroupListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

class SeedPriceGroupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/seed-price-groups-menu.php'),
            'menu.admin'
        );

        $this->mergeConfigFrom(
            base_path('config/seed-price-groups-acl.php'),
            'acl'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            app_path('Resources/views/seed-price-groups'),
            'seed-price-groups'
        );

        Route::middleware(['web', 'admin', NoCacheMiddleware::class])
            ->prefix(config('app.admin_url'))
            ->group(base_path('routes/seed-price-groups.php'));

        Event::listen(
            'bagisto.admin.catalog.product.edit.form.price.after',
            function ($viewRenderEventManager) {
                $viewRenderEventManager->addTemplate('seed-price-groups::product-field');
            }
        );

        Event::listen(
            'catalog.product.update.before',
            SeedPriceGroupListener::class.'@prepareProductPrice'
        );

        Event::listen(
            'catalog.product.update.after',
            SeedPriceGroupListener::class.'@saveProductGroup'
        );

        Event::listen(
            'checkout.order.orderitem.save.after',
            SeedPriceGroupListener::class.'@snapshotOrderItem'
        );
    }
}

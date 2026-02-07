<?php

namespace Webkul\Esto\Providers;

use Illuminate\Support\ServiceProvider;

class EstoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/paymentmethods.php',
            'payment_methods'
        );
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');
    }
}

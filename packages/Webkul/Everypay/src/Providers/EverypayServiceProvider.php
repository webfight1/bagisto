<?php

namespace Webkul\Everypay\Providers;

use Illuminate\Support\ServiceProvider;

class EverypayServiceProvider extends ServiceProvider
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

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Mg\Delivery\Services\Delivery\DeliveryServiceInterface;
use Mg\Delivery\Services\Delivery\Implementations\MeNuvemDeliveryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DeliveryServiceInterface::class, MeNuvemDeliveryService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

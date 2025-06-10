<?php

namespace App\Providers;

use App\Interfaces\ZohoTokenRepositoryInterface;
use App\Repositories\ZohoTokenRepository;
use App\Services\SalesPurchaseOrderService;
use App\Services\ZohoAuthService;
use App\Services\ZohoBaseApiService;
use App\Services\ZohoPurchaseOrderService;
use App\Services\ZohoSalesOrderService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ZohoTokenRepositoryInterface::class, ZohoTokenRepository::class);
        $this->app->singleton(ZohoAuthService::class, fn ($app) => new ZohoAuthService($app->make(ZohoTokenRepositoryInterface::class)));

        $this->app->singleton(ZohoBaseApiService::class, fn ($app) => ZohoBaseApiService::getInstance($app->make(ZohoAuthService::class)));
        $this->app->singleton(ZohoSalesOrderService::class, fn ($app) => new ZohoSalesOrderService());
        $this->app->singleton(ZohoPurchaseOrderService::class, fn ($app) => new ZohoPurchaseOrderService());
        $this->app->singleton(SalesPurchaseOrderService::class, fn ($app) => new SalesPurchaseOrderService(
            $app->make(ZohoSalesOrderService::class),
            $app->make(ZohoPurchaseOrderService::class)
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

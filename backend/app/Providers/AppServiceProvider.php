<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar services específicos do Solar Toolbox
        $this->app->singleton(\App\Services\SeriesCalculatorService::class);
        $this->app->singleton(\App\Services\ParallelCalculatorService::class);
        $this->app->singleton(\App\Services\InverterCapacityService::class);
        $this->app->singleton(\App\Services\DistributionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DolibarrService;

class DolibarrServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DolibarrService::class, function ($app) {
            return new DolibarrService();
        });
    }

    public function boot()
    {
        //
    }
}
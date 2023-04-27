<?php

namespace Briofy\RestLaravel\Providers;

use Illuminate\Support\ServiceProvider;

class BriofyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Config/config.php' => config_path('briofy.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'briofy');
    }
}
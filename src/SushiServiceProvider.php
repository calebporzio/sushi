<?php

namespace Sushi;

use Illuminate\Support\ServiceProvider;

class SushiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/sushi.php' => config_path('sushi'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/sushi.php', 'sushi'
        );
    }
}

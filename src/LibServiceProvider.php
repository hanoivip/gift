<?php

namespace Hanoivip\Gift;

use Hanoivip\Gift\Services\GiftService;
use Illuminate\Support\ServiceProvider;

class LibServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../views' => resource_path('views/vendor/hanoivip'),
        ]);
        
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../views', 'hanoivip');
        
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        
        $this->loadTranslationsFrom( __DIR__.'/../lang', 'hanoivip');
    }
    
    public function register()
    {
        $this->app->bind("IGift", GiftService::class);
    }
}

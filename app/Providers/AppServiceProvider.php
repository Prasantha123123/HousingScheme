<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\PlainTextUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom authentication provider for plain text passwords
        Auth::provider('plain_text_eloquent', function ($app, array $config) {
            return new PlainTextUserProvider($app['hash'], $config['model']);
        });
    }
}

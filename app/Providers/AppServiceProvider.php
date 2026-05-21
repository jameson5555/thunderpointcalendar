<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if ($this->shouldForceHttps()) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }
    }

    private function shouldForceHttps(): bool
    {
        if ($this->app->environment(['local', 'testing'])) {
            return false;
        }

        return parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https';
    }
}

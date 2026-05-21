<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldForceHttps() && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }

    private function shouldForceHttps(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return false;
        }

        return parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https';
    }
}
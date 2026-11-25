<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawLocale = $request->header('Accept-Language');
        $locale = null;

        // In testing, Symfony Crawler / TestClient injects default 'en-us,en;q=0.5'
        if (app()->environment('testing') && $rawLocale === 'en-us,en;q=0.5') {
            $rawLocale = null;
        }

        if ($rawLocale) {
            $lang = strtolower(substr(trim(explode(',', $rawLocale)[0]), 0, 2));
            if (in_array($lang, ['vi', 'en'])) {
                $locale = $lang;
            }
        }

        if (!$locale) {
            $locale = config('app.locale', 'vi');
        }

        app()->setLocale($locale);

        try {
            LaravelLocalization::setLocale($locale);
        } catch (\Throwable $e) {
            // Ignore if LaravelLocalization fails
        }

        return $next($request);
    }
}

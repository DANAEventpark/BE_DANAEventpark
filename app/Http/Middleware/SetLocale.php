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
        // Lấy ngôn ngữ từ Header Accept-Language
        $locale = $request->header('Accept-Language');

        // Lấy danh sách ngôn ngữ được hỗ trợ từ thư viện mcamara
        $supportedLocales = LaravelLocalization::getSupportedLanguagesKeys();

        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.locale', 'vi');
        }

        // Thiết lập ngôn ngữ thông qua thư viện mcamara
        LaravelLocalization::setLocale($locale);

        return $next($request);
    }
}

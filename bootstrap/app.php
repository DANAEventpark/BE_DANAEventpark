<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SetLocale::class);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/login');
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,

            // Bổ sung thêm 3 dòng này của thư viện laravel-localization
            'localize'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect'   => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Tài nguyên hoặc sự kiện không tồn tại hoặc đã bị xóa.',
                ], 404);
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                Log::error('Database query error on API request', [
                    'url' => $request->fullUrl(),
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ hoặc vượt quá giới hạn cho phép.',
                ], 422);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (($request->is('api/*') || $request->expectsJson())
                && ! $e instanceof ValidationException
                && ! $e instanceof HttpExceptionInterface
            ) {
                Log::error('Unhandled API exception', [
                    'url' => $request->fullUrl(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.',
                ], 500);
            }
        });
    })->create();

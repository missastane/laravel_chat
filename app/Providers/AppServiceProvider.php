<?php

namespace App\Providers;

use App\Policies\MediaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;


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
        Gate::define('canDownload', [MediaPolicy::class, 'canDownload']);

        // Response::macro('error', function ($message, $code = 400) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => $message,
        //     ], $code);
        // });
    
        // Response::macro('success', function ($data = [], $message = null, $code = 200) {
        //     return response()->json([
        //         'status' => true,
        //         'message' => $message,
        //         'data' => $data,
        //     ], $code);
        // });

    }
}

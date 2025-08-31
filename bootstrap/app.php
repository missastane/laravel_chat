<?php

use App\Http\Middleware\CheckSystemicBlock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('auth', \Tymon\JWTAuth\Http\Middleware\Authenticate::class);
        $middleware->appendToGroup('EnsureNotBlocked', App\Http\Middleware\CheckConversationNotBlocked::class);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'systemicBlock' => CheckSystemicBlock::class
        ]);


    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'مسیر مورد نظر پیدا نشد'
                ], 404);
            }
        });
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'موردی با این مشخصات پیدا نشد'
                ], 404);
            }
        });
        $exceptions->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید'
                ], 401);
            }
        });
        $exceptions->renderable(function (UnauthorizedException $e, $request) {
            return response()->json([
                'status' => false,
                'message' => 'شما مجوز انجام این عملیات را ندارید',
            ], 403);
        });
        $exceptions->renderable(function (InvalidSignatureException $e, $request) {
            return redirect(env('SPA_URL') . '/verify-expired');
        });

    })->create();

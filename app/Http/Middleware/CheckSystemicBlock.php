<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemicBlock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user->is_blocked == 1) {
            return response()->json([
                'status' => false,
                'message' => '.حساب کاربری شما توسط سیستم مسدود شده است. شما مجوز انجام این عملیات را ندارید'
            ],403);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserOnlineMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        if ($user = auth()->user()) {
            $user->online();
        }

        return $next($request);
    }
}
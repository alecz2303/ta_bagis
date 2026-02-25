<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KaiProbeHeader
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Si ves este header en el 403 => pasó por Laravel
        $response->headers->set('X-KAI-LARAVEL', '1');

        return $response;
    }
}
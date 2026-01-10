<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    /**
     * Handle an incoming request and log it
     */
    public function handle(Request $request, Closure $next)
    {
        $method = $request->method();
        $path = $request->path();
        $ip = $request->ip();
        
        // Log to console (stdout)
        error_log("[$method] $path - $ip");
        
        $response = $next($request);
        
        $statusCode = $response->getStatusCode();
        error_log("[$method] $path - $statusCode");
        
        return $response;
    }
}

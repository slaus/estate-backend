<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsStaticFiles
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Добавляем CORS заголовки для статических файлов
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }
}
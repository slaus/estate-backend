<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();
            $expiresAt = $token->expires_at;
            
            // Проверяем, истек ли токен
            if ($expiresAt && Carbon::parse($expiresAt)->isPast()) {
                $token->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired',
                    'expired' => true
                ], 401);
            }
            
            // Добавляем информацию о времени жизни в заголовки
            if ($expiresAt) {
                $remaining = Carbon::parse($expiresAt)->diffInSeconds(now());
                $request->headers->set('X-Token-Expires-In', $remaining);
                $request->headers->set('X-Token-Expires-At', $expiresAt);
            }
        }

        return $next($request);
    }
}
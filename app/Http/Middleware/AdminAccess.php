<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect('/login');
        }
        
        if ($user->role === null) {
            return redirect('/');
        }
        
        return $next($request);
    }
}
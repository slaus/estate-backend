<?php

use Illuminate\Support\Facades\Route;

// Простой маршрут для API бэкенда
Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'message' => 'API Backend is running',
        'endpoints' => [
            'api' => '/api/v1',
            'test' => '/api/v1/test',
            'login' => '/api/v1/login',
            'register' => '/api/v1/register'
        ]
    ]);
});

Route::any('login', function() {
    return response()->json([
        'error' => 'Method not allowed',
        'message' => 'Use POST /api/v1/login for authentication',
        'correct_endpoint' => [
            'method' => 'POST',
            'url' => '/api/v1/login'
        ]
    ], 405);
})->name('login');
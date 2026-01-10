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


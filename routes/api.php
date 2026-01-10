<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\PartnerController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\PublicController;

Route::group(['prefix' => 'v1', 'as' => 'api.'], function () {
    
    // ==================== ПУБЛИЧНЫЕ МАРШРУТЫ (ДОЛЖНЫ БЫТЬ ПЕРВЫМИ) ====================
    
    // Тест API
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API працює!',
            'status' => 'success',
            'timestamp' => now()->toDateTimeString(),
        ]);
    });
    
    // Публичный API из PublicController - КОРОТКИЕ ПУТИ
    Route::get('pages', [PublicController::class, 'pages']);
    Route::get('pages/{slug}', [PublicController::class, 'page'])->where('slug', '[a-z0-9-]+');
    Route::get('posts', [PublicController::class, 'posts']);
    Route::get('posts/{slug}', [PublicController::class, 'post'])->where('slug', '[a-z0-9-]+');
    Route::get('employees', [PublicController::class, 'employees']);
    Route::get('testimonials', [PublicController::class, 'testimonials']);
    Route::get('partners', [PublicController::class, 'partners']);
    Route::get('menus', [PublicController::class, 'menus']);
    Route::get('settings/{group}', [PublicController::class, 'settings']);
    Route::get('settings', [PublicController::class, 'allSettings']);
    
    // Аутентификация (публичная)
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    
    // Альтернативные публичные маршруты с префиксом /public/ (из контроллеров V1)
    Route::prefix('public')->group(function () {
        Route::get('pages', [PageController::class, 'indexPublic'])->name('public.pages.index');
        Route::get('pages/{slug}', [PageController::class, 'showPublic'])->name('public.pages.show');
        
        Route::get('posts', [PostController::class, 'indexPublic'])->name('public.posts.index');
        Route::get('posts/{slug}', [PostController::class, 'showPublic'])->name('public.posts.show');
        
        Route::get('employees', [EmployeeController::class, 'indexPublic'])->name('public.employees.index');
        Route::get('testimonials', [TestimonialController::class, 'indexPublic'])->name('public.testimonials.index');
        Route::get('partners', [PartnerController::class, 'indexPublic'])->name('public.partners.index');
        Route::get('menus', [MenuController::class, 'indexPublic'])->name('public.menus.index');
        Route::get('settings/{group}', [SettingController::class, 'showPublic'])->name('public.settings.show');
    });
    
    // ==================== ЗАЩИЩЕННЫЕ МАРШРУТЫ (требуют аутентификации) ====================
    
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // Аутентификация
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');
        
        // Ресурсы CRUD
        Route::apiResource('pages', PageController::class);
        Route::apiResource('posts', PostController::class);
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('testimonials', TestimonialController::class);
        Route::apiResource('partners', PartnerController::class);
        Route::apiResource('menus', MenuController::class);
        
        // Специальные маршруты
        Route::put('menus/rebuild', [MenuController::class, 'rebuild'])->name('menus.rebuild');
        Route::get('pages/list', [PageController::class, 'list'])->name('pages.list');
        Route::post('pages/{id}/generate-seo', [PageController::class, 'generateSeo'])->name('pages.generate-seo');
        
        // Теги (только список)
        Route::apiResource('posts/tags', TagController::class)->only(['index']);
        
        // Настройки
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('{group}', [SettingController::class, 'index'])->name('index');
            Route::post('{group}', [SettingController::class, 'store'])->name('store');
        });
        
    });

});
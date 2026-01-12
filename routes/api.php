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
    
    // ==================== ПУБЛИЧНЫЕ МАРШРУТЫ ====================
    
    // Аутентификация
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    
    // Публичный API
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
    
    // Теги (публичный доступ)
    Route::get('tags', [PublicController::class, 'tags']);
    Route::get('posts/{slug}/tags', [PublicController::class, 'postTags']);
    
    // ==================== ЗАЩИЩЕННЫЕ МАРШРУТЫ ====================
    
    Route::middleware(['auth:sanctum', 'token.expiration'])->group(function () {
        
        // Аутентификация
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');
        
        // Ресурсы CRUD (для админки)
        Route::apiResource('admin/pages', PageController::class)->names([
            'index' => 'admin.pages.index',
            'store' => 'admin.pages.store',
            'show' => 'admin.pages.show',
            'update' => 'admin.pages.update',
            'destroy' => 'admin.pages.destroy'
        ]);
        
        Route::apiResource('admin/posts', PostController::class)->names([
            'index' => 'admin.posts.index',
            'store' => 'admin.posts.store',
            'show' => 'admin.posts.show',
            'update' => 'admin.posts.update',
            'destroy' => 'admin.posts.destroy'
        ]);
        
        Route::apiResource('admin/employees', EmployeeController::class);
        Route::apiResource('admin/testimonials', TestimonialController::class);
        Route::apiResource('admin/partners', PartnerController::class);
        Route::apiResource('admin/menus', MenuController::class);
        
        // Специальные маршруты для админки
        Route::put('admin/menus/rebuild', [MenuController::class, 'rebuild'])->name('admin.menus.rebuild');
        Route::get('admin/pages/list', [PageController::class, 'list'])->name('admin.pages.list');
        Route::post('admin/pages/{id}/generate-seo', [PageController::class, 'generateSeo'])->name('admin.pages.generate-seo');
        
        // Теги (управление для админки)
        Route::apiResource('admin/tags', TagController::class);
        
        // Настройки для админки
        Route::prefix('admin/settings')->name('admin.settings.')->group(function () {
            Route::get('{group}', [SettingController::class, 'index'])->name('index');
            Route::post('{group}', [SettingController::class, 'store'])->name('store');
        });
        
    });

});
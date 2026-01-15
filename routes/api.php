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
use App\Http\Middleware\CheckTokenExpiration; // ← ДОБАВЬТЕ ЭТОТ ИМПОРТ

Route::group(['prefix' => 'v1', 'as' => 'api.'], function () {
    
    // ==================== ПУБЛИЧНЫЕ МАРШРУТЫ ====================
    
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    
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
    
    Route::get('tags', [PublicController::class, 'tags']);
    Route::get('posts/{slug}/tags', [PublicController::class, 'postTags']);
    
    // ==================== ЗАЩИЩЕННЫЕ МАРШРУТЫ ====================
    
    // ВАРИАНТ 1: Используйте полное имя класса
    Route::middleware(['auth:sanctum', CheckTokenExpiration::class])->group(function () {
        
    // ВАРИАНТ 2: Или используйте алиас (должен работать после исправления Kernel)
    // Route::middleware(['auth:sanctum', 'token.expiration'])->group(function () {
        
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');
		
		Route::prefix('user')->group(function () {
			Route::post('profile', [AuthController::class, 'updateProfile'])->name('profile.update');
			Route::delete('avatar', [AuthController::class, 'removeAvatar'])->name('avatar.remove');
			Route::get('profile/{id}', [AuthController::class, 'getUserById'])->name('profile.show');
		});
        
        // Управление пользователями (только для админов)
        Route::middleware(['role:admin'])->group(function () {
            Route::get('admin/users', [AuthController::class, 'getUsers']);
            Route::post('admin/users', [AuthController::class, 'register']);
            Route::put('admin/users/{id}', [AuthController::class, 'updateUser']);
            Route::delete('admin/users/{id}', [AuthController::class, 'deleteUser']);
        });
        
        // Посты (доступны всем авторизованным)
        Route::apiResource('admin/posts', PostController::class)->names([
            'index' => 'admin.posts.index',
            'store' => 'admin.posts.store',
            'show' => 'admin.posts.show',
            'update' => 'admin.posts.update',
            'destroy' => 'admin.posts.destroy'
        ]);
		
		// Меню (только для админов и суперадминов)
		Route::middleware([\App\Http\Middleware\CheckRole::class . ':admin,superadmin'])->group(function () {
			Route::apiResource('admin/menus', MenuController::class);
			Route::put('admin/menus/rebuild', [MenuController::class, 'rebuild'])->name('admin.menus.rebuild');
		});
        
        // Страницы (доступны всем авторизованным)
        Route::apiResource('admin/pages', PageController::class)->names([
            'index' => 'admin.pages.index',
            'store' => 'admin.pages.store',
            'show' => 'admin.pages.show',
            'update' => 'admin.pages.update',
            'destroy' => 'admin.pages.destroy'
        ]);
        
        // Специальные маршруты для страниц
        Route::get('admin/pages/list', [PageController::class, 'list'])->name('admin.pages.list');
        Route::post('admin/pages/{id}/generate-seo', [PageController::class, 'generateSeo'])->name('admin.pages.generate-seo');
        
        // Теги (доступны всем авторизованным)
        Route::apiResource('admin/tags', TagController::class);
        
        // Сотрудники (доступны всем авторизованным)
        Route::apiResource('admin/employees', EmployeeController::class);
        
        // Отзывы (доступны всем авторизованным)
        Route::apiResource('admin/testimonials', TestimonialController::class);
        
        // Партнеры (доступны всем авторизованным)
        Route::apiResource('admin/partners', PartnerController::class);
        
        // Настройки (только для админов и суперадминов)
        Route::middleware(['role:admin'])->prefix('admin/settings')->name('admin.settings.')->group(function () {
            Route::get('{group}', [SettingController::class, 'index'])->name('index');
            Route::post('{group}', [SettingController::class, 'store'])->name('store');
        });
        
    });

});
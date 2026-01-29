<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\PartnerController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Middleware\CheckTokenExpiration;
use App\Http\Middleware\CheckRole;

Route::group(['prefix' => 'v1', 'as' => 'api.'], function () {
    
    // ==================== ПУБЛИЧНЫЕ МАРШРУТЫ ====================
    
    // Аутентификация
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('admin/login', [AuthController::class, 'login'])->name('admin.login');
    
    // Регистрация пользователей сайта
    Route::post('users/register', [UsersController::class, 'register'])->name('users.register');
    
    // Публичный доступ к контенту
    Route::get('pages', [PublicController::class, 'pages']);
    Route::get('pages/{slug}', [PublicController::class, 'page'])->where('slug', '[a-z0-9-]+');
    Route::get('posts', [PublicController::class, 'posts']);
    Route::get('posts/{slug}', [PublicController::class, 'post'])->where('slug', '[a-z0-9-]+');
    Route::get('employees', [PublicController::class, 'employees']);
    Route::get('testimonials', [PublicController::class, 'testimonials']);
    Route::get('partners', [PublicController::class, 'partners']);
    Route::get('menus', [PublicController::class, 'menus']);
    Route::get('tags', [PublicController::class, 'tags']);
    Route::get('posts/{slug}/tags', [PublicController::class, 'postTags']);
    
    // Настройки (публичные)
    Route::get('settings/{group}', [PublicController::class, 'settings']);
    Route::get('settings', [PublicController::class, 'allSettings']);
    
    // ==================== ЗАЩИЩЕННЫЕ МАРШРУТЫ АДМИНИСТРАТОРОВ ====================
    
    Route::middleware(['auth:sanctum', CheckTokenExpiration::class])->group(function () {
        
        // ============ АДМИНИСТРАТОРЫ И ПРОФИЛЬ ============
        
        // Выход из админки
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('admin/logout', [AuthController::class, 'logout'])->name('admin.logout');
        
        // Профиль администратора
        Route::prefix('admin')->group(function () {
            Route::get('user', [AuthController::class, 'user'])->name('admin.user');
            Route::post('profile', [AuthController::class, 'updateProfile'])->name('admin.profile.update');
            Route::delete('avatar', [AuthController::class, 'removeAvatar'])->name('admin.avatar.remove');
            Route::get('profile/{id}', [AuthController::class, 'getUserById'])->name('admin.profile.show');
        });
        
        // Старые маршруты для профиля (обратная совместимость)
        Route::prefix('user')->group(function () {
            Route::post('profile', [AuthController::class, 'updateProfile'])->name('user.profile.update');
            Route::delete('avatar', [AuthController::class, 'removeAvatar'])->name('user.avatar.remove');
            Route::get('profile/{id}', [AuthController::class, 'getUserById'])->name('user.profile.show');
        });
        
        // Текущий пользователь
        Route::get('user', [AuthController::class, 'user'])->name('user');
		
		Route::middleware(['auth:sanctum', CheckTokenExpiration::class])->post('/upload', [UploadController::class, 'uploadImage']);
        
        // ============ УПРАВЛЕНИЕ АДМИНИСТРАТОРАМИ (только суперадмины) ============
        
        Route::middleware([CheckRole::class . ':superadmin'])->prefix('admin/admins')->name('admin.admins.')->group(function () {
            Route::get('/', [AuthController::class, 'getAdmins'])->name('index');
            Route::post('/', [AuthController::class, 'createAdmin'])->name('store');
            Route::put('/{id}', [AuthController::class, 'updateAdmin'])->name('update');
            Route::delete('/{id}', [AuthController::class, 'deleteAdmin'])->name('destroy');
        });
        
        // ============ УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ САЙТА ============
        
        Route::middleware([CheckRole::class . ':admin,superadmin'])->prefix('admin/users')->name('admin.users.')->group(function () {
            Route::get('/', [UsersController::class, 'index'])->name('index');
            Route::get('/{id}', [UsersController::class, 'show'])->name('show');
            Route::put('/{id}', [UsersController::class, 'update'])->name('update');
            Route::delete('/{id}', [UsersController::class, 'destroy'])->name('destroy');
        });
        
        // ============ КОНТЕНТ (для всех администраторов) ============
        
        // Страницы
        Route::apiResource('admin/pages', PageController::class)->names([
            'index' => 'admin.pages.index',
            'store' => 'admin.pages.store',
            'show' => 'admin.pages.show',
            'update' => 'admin.pages.update',
            'destroy' => 'admin.pages.destroy'
        ]);
        
        // Дополнительные маршруты для страниц
        Route::get('admin/pages/list', [PageController::class, 'list'])->name('admin.pages.list');
        Route::post('admin/pages/{id}/generate-seo', [PageController::class, 'generateSeo'])->name('admin.pages.generate-seo');
        
        // Посты
        Route::apiResource('admin/posts', PostController::class)->names([
            'index' => 'admin.posts.index',
            'store' => 'admin.posts.store',
            'show' => 'admin.posts.show',
            'update' => 'admin.posts.update',
            'destroy' => 'admin.posts.destroy'
        ]);
        
        // Теги
        Route::apiResource('admin/tags', TagController::class)->names([
            'index' => 'admin.tags.index',
            'store' => 'admin.tags.store',
            'show' => 'admin.tags.show',
            'update' => 'admin.tags.update',
            'destroy' => 'admin.tags.destroy'
        ]);
        
        // Сотрудники
        Route::apiResource('admin/employees', EmployeeController::class)->names([
            'index' => 'admin.employees.index',
            'store' => 'admin.employees.store',
            'show' => 'admin.employees.show',
            'update' => 'admin.employees.update',
            'destroy' => 'admin.employees.destroy'
        ]);
        
        // Отзывы
        Route::apiResource('admin/testimonials', TestimonialController::class)->names([
            'index' => 'admin.testimonials.index',
            'store' => 'admin.testimonials.store',
            'show' => 'admin.testimonials.show',
            'update' => 'admin.testimonials.update',
            'destroy' => 'admin.testimonials.destroy'
        ]);
        
        // Партнеры
        Route::apiResource('admin/partners', PartnerController::class)->names([
            'index' => 'admin.partners.index',
            'store' => 'admin.partners.store',
            'show' => 'admin.partners.show',
            'update' => 'admin.partners.update',
            'destroy' => 'admin.partners.destroy'
        ]);
        
        // ============ СИСТЕМНЫЕ РАЗДЕЛЫ ============
        
        // Меню (для админов и суперадминов)
        Route::middleware([CheckRole::class . ':admin,superadmin'])->group(function () {
            Route::apiResource('admin/menus', MenuController::class)->names([
                'index' => 'admin.menus.index',
                'store' => 'admin.menus.store',
                'show' => 'admin.menus.show',
                'update' => 'admin.menus.update',
                'destroy' => 'admin.menus.destroy'
            ]);
            Route::put('admin/menus/rebuild', [MenuController::class, 'rebuild'])->name('admin.menus.rebuild');
			Route::get('admin/menus/pages', [MenuController::class, 'getPagesForMenu'])->name('admin.menus.pages');
        });
        
        // Настройки (для админов и суперадминов)
        Route::middleware([CheckRole::class . ':admin,superadmin'])->prefix('admin/settings')->name('admin.settings.')->group(function () {
            Route::get('{group}', [SettingController::class, 'index'])->name('index');
            Route::post('{group}', [SettingController::class, 'store'])->name('store');
        });
        
    });
    
    // ==================== ЗАЩИЩЕННЫЕ МАРШРУТЫ ПОЛЬЗОВАТЕЛЕЙ САЙТА ====================
    
    Route::middleware(['auth:sanctum'])->prefix('user')->name('user.')->group(function () {
        Route::get('profile', [UsersController::class, 'profile'])->name('profile');
        Route::put('profile', [UsersController::class, 'updateProfile'])->name('profile.update');
    });

});
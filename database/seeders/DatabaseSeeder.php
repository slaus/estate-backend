<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Создаем суперадмина
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'superadmin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Создаем тестового админа
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Создаем тестового менеджера
        DB::table('users')->insert([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'manager',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Создаем тестовых обычных пользователей (пустая роль)
        DB::table('users')->insert([
            [
                'name' => 'Иван Петров',
                'email' => 'ivan@example.com',
                'password' => Hash::make('12345678'),
                'role' => '', // Пустая строка - обычный пользователь
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Мария Сидорова',
                'email' => 'maria@example.com',
                'password' => Hash::make('12345678'),
                'role' => '', // Пустая строка - обычный пользователь
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Алексей Коваленко',
                'email' => 'alex@example.com',
                'password' => Hash::make('12345678'),
                'role' => '', // Пустая строка - обычный пользователь
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Страницы
        DB::table('pages')->insert([
            [
                'slug' => 'home',
                'name' => json_encode(['uk' => 'Головна', 'en' => 'Home']),
                'content' => json_encode(['uk' => 'Контент головної сторінки', 'en' => 'Home page content']),
                'seo' => json_encode([
                    'meta_title' => ['uk' => 'Головна сторінка', 'en' => 'Home page'],
                    'meta_description' => ['uk' => 'Опис головної сторінки', 'en' => 'Home page description'],
                ]),
                'visibility' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'about',
                'name' => json_encode(['uk' => 'Про нас', 'en' => 'About us']),
                'content' => json_encode(['uk' => 'Інформація про компанію', 'en' => 'Company information']),
                'seo' => json_encode([
                    'meta_title' => ['uk' => 'Про нас', 'en' => 'About us'],
                    'meta_description' => ['uk' => 'Детальна інформація про компанію', 'en' => 'Detailed company information'],
                ]),
                'visibility' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Посты
        DB::table('posts')->insert([
            [
                'user_id' => 1,
                'slug' => 'welcome',
                'name' => json_encode(['uk' => 'Ласкаво просимо', 'en' => 'Welcome']),
                'description' => json_encode(['uk' => 'Вітальний пост', 'en' => 'Welcome post']),
                'content' => json_encode(['uk' => 'Контент вітального поста', 'en' => 'Welcome post content']),
                'author' => json_encode(['uk' => 'Адміністратор', 'en' => 'Administrator']),
                'seo' => json_encode([
                    'meta_title' => ['uk' => 'Ласкаво просимо', 'en' => 'Welcome'],
                    'meta_description' => ['uk' => 'Вітальний пост на сайті', 'en' => 'Welcome post on the site'],
                ]),
                'visibility' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Настройки
        DB::table('settings')->insert([
            [
                'group' => 'general',
                'name' => 'site_name',
                'value' => json_encode(['uk' => 'Моя компанія', 'en' => 'My company']),
            ],
            [
                'group' => 'general',
                'name' => 'site_description',
                'value' => json_encode(['uk' => 'Опис сайту', 'en' => 'Site description']),
            ],
        ]);

        $this->call([
            TagSeeder::class,
        ]);

        $this->command->info('✅ Тестовые данные созданы!');
        $this->command->info('👤 Суперадмин: superadmin@example.com / 12345678');
        $this->command->info('👤 Админ: admin@example.com / 12345678');
        $this->command->info('👤 Менеджер: manager@example.com / 12345678');
        $this->command->info('👤 Обычные пользователи: ivan@example.com, maria@example.com, alex@example.com / 12345678');
    }
}
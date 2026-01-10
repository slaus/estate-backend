<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run()
    {
        $tags = [
            ['name' => ['uk' => 'Новини', 'en' => 'News', 'ru' => 'Новости'], 'slug' => 'news'],
            ['name' => ['uk' => 'Статті', 'en' => 'Articles', 'ru' => 'Статьи'], 'slug' => 'articles'],
            ['name' => ['uk' => 'Огляди', 'en' => 'Reviews', 'ru' => 'Обзоры'], 'slug' => 'reviews'],
            ['name' => ['uk' => 'Поради', 'en' => 'Tips', 'ru' => 'Советы'], 'slug' => 'tips'],
            ['name' => ['uk' => 'Аналітика', 'en' => 'Analytics', 'ru' => 'Аналитика'], 'slug' => 'analytics'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
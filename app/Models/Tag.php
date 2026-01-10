<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'slug',
        'order_column'
    ];

    protected $casts = [
        'name' => 'array',
    ];

    // Полиморфная связь с постами и другими моделями
    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function scopePublished($query)
    {
        return $query; // Теги всегда опубликованы
    }
}
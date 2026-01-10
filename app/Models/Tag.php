<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'order_column'
    ];

    protected $casts = [
        'name' => 'array'
    ];
    
    // Связь с постами
    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
    
    // Связь со страницами (если нужно)
    public function pages()
    {
        return $this->morphedByMany(Page::class, 'taggable');
    }
}
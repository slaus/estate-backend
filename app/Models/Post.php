<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Spatie\Tags\HasTags;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'content',
        'author',
        'image',
        'seo',
        'visibility',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'content' => 'array',
        'author' => 'array',
        'seo' => 'array',
        'visibility' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('visibility', true);
    }

    public function getTitleAttribute()
    {
        $lang = app()->getLocale();
        return $this->name[$lang] ?? array_values($this->name)[0] ?? '';
    }

    public function getExcerptAttribute()
    {
        $lang = app()->getLocale();
        return $this->description[$lang] ?? array_values($this->description)[0] ?? '';
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
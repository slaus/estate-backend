<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Spatie\Tags\HasTags;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'content',
        'seo',
        'visibility',
    ];

    protected $casts = [
        'name' => 'array',
        'content' => 'array',
        'seo' => 'array',
        'visibility' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('visibility', true);
    }

    public function getTitleAttribute()
    {
        $lang = app()->getLocale();
        return $this->name[$lang] ?? array_values($this->name)[0] ?? '';
    }

    public function getMetaTitleAttribute()
    {
        $lang = app()->getLocale();
        return $this->seo['meta_title'][$lang] ?? $this->seo['meta_title']['uk'] ?? $this->title;
    }

    public function getMetaDescriptionAttribute()
    {
        $lang = app()->getLocale();
        return $this->seo['meta_description'][$lang] ?? $this->seo['meta_description']['uk'] ?? '';
    }
}
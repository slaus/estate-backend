<?php

namespace App\Traits;

use App\Models\Tag;

trait HasTags
{
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
    
    public function scopeWithAllTags($query, $tags)
    {
        $tagIds = is_array($tags) ? $tags : [$tags];
        
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('id', $tagIds);
        }, '=', count($tagIds));
    }
    
    public function scopeWithAnyTags($query, $tags)
    {
        $tagIds = is_array($tags) ? $tags : [$tags];
        
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('id', $tagIds);
        });
    }
}
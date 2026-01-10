<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Menu extends Model
{
    use HasFactory, NodeTrait;

    protected $fillable = [
        'layout',
        'name',
        'properties',
        'visibility',
    ];

    protected $casts = [
        'name' => 'array',
        'properties' => 'array',
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
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'description',
        'details',
        'image',
        'order',
        'visibility',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'details' => 'array',
        'visibility' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('visibility', true);
    }

    public function getNameAttribute($value)
    {
        $lang = app()->getLocale();
        $name = json_decode($value, true);
        return $name[$lang] ?? array_values($name)[0] ?? '';
    }
}
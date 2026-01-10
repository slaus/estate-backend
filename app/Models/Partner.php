<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'image',
        'order',
        'visibility',
    ];

    protected $casts = [
        'description' => 'array',
        'visibility' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('visibility', true);
    }
}
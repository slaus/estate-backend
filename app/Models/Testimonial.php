<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'text',
        'image',
        'video',
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'name',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function getValue($group, $name, $default = null)
    {
        $setting = static::where('group', $group)
            ->where('name', $name)
            ->first();
            
        if (!$setting) {
            return $default;
        }
        
        $lang = app()->getLocale();
        $value = $setting->value;
        
        return is_array($value) ? ($value[$lang] ?? $value['uk'] ?? array_values($value)[0] ?? $default) : $value;
    }
}
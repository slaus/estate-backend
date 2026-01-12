<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Добавляем роль
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Методы проверки ролей
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'superadmin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager' || $this->role === 'admin' || $this->role === 'superadmin';
    }

    public function hasRole(string $role): bool
    {
        $roles = [
            'superadmin' => ['superadmin'],
            'admin' => ['admin', 'superadmin'],
            'manager' => ['manager', 'admin', 'superadmin'],
        ];

        return in_array($this->role, $roles[$role] ?? []);
    }

    // Скоупы для фильтрации по ролям
    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['admin', 'superadmin']);
    }

    public function scopeSuperAdmins($query)
    {
        return $query->where('role', 'superadmin');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'phone', 'avatar', 'status', 'type',
    'supplier_id', 'distributor_id', 'email_verified_at', 'remember_token',
])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles;

    protected $guard_name = 'web';

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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isPlatform(): bool
    {
        return $this->type === 'platform';
    }

    public function isSupplier(): bool
    {
        return $this->type === 'supplier';
    }

    public function isDistributor(): bool
    {
        return $this->type === 'distributor';
    }

    public function isRegionalAgent(): bool
    {
        return $this->type === 'regional_agent';
    }

    public function isWholesaler(): bool
    {
        return $this->type === 'wholesaler';
    }
}

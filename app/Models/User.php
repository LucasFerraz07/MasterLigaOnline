<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Company;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, Tenantable;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'ativo',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'ativo'      => 'boolean',
            'last_login' => 'datetime',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vitalabs(): HasMany
    {
        return $this->hasMany(Vitalab::class);
    }
}

<?php

namespace App\Models;

use App\Enums\UserType;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements JWTSubject
{
    use HasUuids, SoftDeletes, HasRoles, Tenantable;

    protected $guard_name = 'api';

    protected $appends = ['is_active'];

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'league_id',
        'balance',
        'user_type',
    ];

    protected $hidden = [
        'password',
    ];


    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'balance'  => 'decimal:2',
            'user_type' => UserType::class,
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

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->deleted_at);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function clubIdentity(): HasOne
    {
        return $this->hasOne(ClubIdentity::class);
    }
}
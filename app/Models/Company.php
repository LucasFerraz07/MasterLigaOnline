<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['nome', 'ativo'];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class, 'owner_has_companies');
    }
}

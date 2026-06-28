<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Owner extends Model
{
    use Tenantable;

    protected $fillable = [
        'cpf',
        'company_id',
        'user_id',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'owner_has_companies');
    }
}

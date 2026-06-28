<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vitalab extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'company_id',
        'esp_code',
        'last_sync',
    ];

    protected function casts(): array
    {
        return [
            'last_sync' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }
}

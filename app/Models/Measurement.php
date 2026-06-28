<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'company_id',
        'vitalab_id',
        'temperature',
        'humidity',
        'measured_at',
        'failed_measurement',
    ];

    protected function casts(): array
    {
        return [
            'temperature'         => 'decimal:2',
            'humidity'            => 'decimal:2',
            'measured_at'         => 'datetime',
            'failed_measurement'  => 'boolean',
        ];
    }

    public function vitalab(): BelongsTo
    {
        return $this->belongsTo(Vitalab::class);
    }
}

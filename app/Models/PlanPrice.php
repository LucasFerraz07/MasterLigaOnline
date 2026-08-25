<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPrice extends Model
{
    use HasUuids;

    public const ALLOWED_INTERVALS = [1, 2, 6, 12];

    protected $fillable = ['plan_id', 'code', 'version', 'interval_months', 'amount_cents', 'currency', 'active'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'interval_months' => 'integer', 'amount_cents' => 'integer', 'active' => 'boolean'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }
}

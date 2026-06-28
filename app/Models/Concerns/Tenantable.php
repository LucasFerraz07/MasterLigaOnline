<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

/** @mixin \Illuminate\Database\Eloquent\Model */
trait Tenantable
{
    protected static function bootTenantable(): void
    {
        static::addGlobalScope(new TenantScope());
    }
}

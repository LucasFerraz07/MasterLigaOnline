<?php

namespace App\Models\Scopes;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    private static bool $resolving = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$resolving) return;

        self::$resolving = true;

        try {
            $auth = Auth::user();

            if (! $auth instanceof User) return;
            if ($auth->hasRole(UserType::SYSTEM_ADMIN->value)) return;
            if (! $auth->league_id) return;

            $builder->where($model->getTable().'.league_id', $auth->league_id);
        } finally {
            self::$resolving = false;
        }
    }
}

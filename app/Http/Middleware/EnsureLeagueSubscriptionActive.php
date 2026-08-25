<?php

namespace App\Http\Middleware;

use App\Builder\ReturnApi;
use App\Enums\UserType;
use App\Models\League;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLeagueSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole(UserType::SYSTEM_ADMIN->value) && $user->league_id) {
            $league = League::with('leagueSubscription')->find($user->league_id);

            if ($league && ! $league->is_active) {
                return ReturnApi::error('A assinatura da liga está vencida. Renove para continuar.', null, 402);
            }
        }

        return $next($request);
    }
}

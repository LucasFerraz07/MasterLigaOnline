<?php

namespace App\Http\Middleware;

use App\Builder\ReturnApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (TokenInvalidException) {
            return ReturnApi::error('Token inválido', null, 401);
        } catch (TokenExpiredException) {
            return ReturnApi::error('Token expirado', null, 401);
        } catch (\Exception) {
            return ReturnApi::error('Token não encontrado', null, 401);
        }

        return $next($request);
    }
}

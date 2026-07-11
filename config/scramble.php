<?php

use App\Scramble\NotFoundExceptionToResponseExtension;
use App\Scramble\RemovePathParametersFromRequestOperationExtension;
use App\Scramble\ValidationExceptionToResponseExtension;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;

return [
    'extensions' => [
        ValidationExceptionToResponseExtension::class,
        NotFoundExceptionToResponseExtension::class,
        RemovePathParametersFromRequestOperationExtension::class,
    ],

    /*
     * O projeto usa o middleware 'auth.api' (JwtMiddleware) em vez do
     * 'auth'/'auth:*' padrão do Laravel, então precisamos apontar isso
     * explicitamente para o Scramble documentar o esquema Bearer e marcar
     * como público qualquer rota sem esse middleware (ex.: /auth/login).
     */
    'security_strategy' => [
        MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['auth.api'],
        ],
    ],
];

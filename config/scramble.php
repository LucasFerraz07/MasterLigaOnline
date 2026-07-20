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

    /*
     * Renderer options are copied from the package defaults so this override only changes
     * `theme` — Laravel's config merge replaces this whole key wholesale, not per sub-key,
     * so a partial array here would silently drop the other Stoplight Elements options.
     */
    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'dark',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => true,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],
];

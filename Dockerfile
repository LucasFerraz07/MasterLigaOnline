# syntax=docker/dockerfile:1

# ---------- estágio 1: dependências PHP ----------
FROM composer:2 AS vendor

WORKDIR /app

# Copiar estes arquivos primeiro permite reutilizar o cache quando somente o
# código da aplicação for alterado.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . ./
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ---------- estágio 2: imagem final ----------
FROM dunglas/frankenphp:php8.4

# A aplicação usa PostgreSQL e recursos de internacionalização do PHP.
RUN install-php-extensions opcache pdo_pgsql intl bcmath

ENV SERVER_NAME=:80

WORKDIR /app

# Copia a aplicação e as dependências de produção, sem a ferramenta Composer.
COPY --from=vendor /app /app
RUN php artisan storage:link

# Laravel precisa escrever logs, caches, sessões e arquivos enviados.
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=3 \
    CMD php -r '$headers = @get_headers("http://127.0.0.1/up"); exit($headers && str_contains($headers[0], "200") ? 0 : 1);'

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

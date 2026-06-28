# Laravel Starter Pack

Projeto base genérico e reutilizável com **multi-tenancy**, **autenticação JWT** e **autorização via Spatie Permission**.

## Stack

- **Laravel 13**
- **SQLite** (padrão, sem configuração extra)
- **tymon/jwt-auth 2.x** — autenticação stateless
- **spatie/laravel-permission 8.x** — roles e permissions por guard


## Como rodar
### 1. Instale as dependências PHP

```bash
composer install
```

### 2. Configure o ambiente

```bash
cp .env.example .env
```

Abra o `.env` e defina as variáveis do admin padrão:

```env
ADMIN_EMAIL=admin@exemplo.com
ADMIN_PASSWORD=sua_senha_segura
```

> Se não definir, os valores padrão serão `admin@teste.com` / `password`.

### 3. Gere as chaves da aplicação

```bash
php artisan key:generate
php artisan jwt:secret
```

### 4. Crie o banco de dados SQLite

```bash
touch database/database.sqlite
```

### 5. Execute as migrations e seeds

```bash
php artisan migrate --seed
```

Isso criará as tabelas e o usuário **System Admin** com as credenciais definidas no `.env`.

### 6. Suba o servidor

```bash
php artisan serve
```

A aplicação estará disponível em `http://localhost:8000`.

---

## Autenticação

A API usa JWT. Para obter um token, faça um `POST /api/auth/login` com:

```json
{
  "email": "admin@exemplo.com",
  "password": "sua_senha_segura"
}
```

Use o token retornado no header `Authorization: Bearer <token>` nas demais requisições.

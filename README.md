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

---

## Importação de jogadores

A base de jogadores é alimentada a partir de um CSV com o header `Posição,Nome,Time,Nacionalidade,Overall,categoria` (a coluna `Time` é ignorada; não há campo correspondente na tabela `players`).

### Rodar manualmente

```bash
php artisan players:import {caminho-do-csv}
```

- Se omitido, o caminho padrão é `storage/app/imports/players.csv`.
- Aceita tanto caminho relativo (à raiz do projeto) quanto absoluto.
- Faz upsert por `name`: jogadores existentes são atualizados, novos são criados. Linhas com categoria inválida (fora de `white`, `bronze`, `silver`, `gold`, `black`) são ignoradas com aviso.

### Sincronização automática semanal

O CSV é gerado pelo repositório `efootballDBWebScraping` (scraping + tratamento dos dados do eFootball), em um diretório separado deste projeto. O script [scripts/sync_players_weekly.sh](scripts/sync_players_weekly.sh) roda o scraping, o tratamento e em seguida o `players:import`, e está agendado via cron para todo domingo às 03:00:

```
0 3 * * 0 /home/lucas/Documentos/MasterLigaOnline/scripts/sync_players_weekly.sh >> storage/logs/sync_players.log 2>&1
```

Para rodar fora do agendamento, basta executar o script diretamente:

```bash
./scripts/sync_players_weekly.sh
```

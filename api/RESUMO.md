# Resumo da execução — Marco 1 do strangler-fig

> **Status:** ✅ O novo projeto Laravel 13 está em pé, rodando, e os endpoints de auth respondem com o mesmo contrato HTTP do MGAuth.
> **Nada que afeta produção foi tocado** — só foi adicionado. O cutover (trocar `AUTH_API_URL` dos consumidores) fica pra você fazer manualmente após validar os testes.

## O que existe agora

### Novo projeto: `/opt/www/MGspa/api`
- **Laravel 13.11.2** + **Laravel Passport 13** + **PHP 8.3.31**
- Skeleton slim do L13 (`bootstrap/app.php` com `Application::configure()`, sem `app/Http/Kernel.php`, sem `app/Console/Kernel.php`, `bootstrap/providers.php`)
- Container Docker próprio: imagem `mgspa-api`, container `mgspa-api`, porta interna **9007**
- Conectado no MESMO banco `mgsis.mgsis` (mesmo `tblusuario`, mesmas `oauth_*`, mesmas chaves OAuth do MGAuth)
- Git iniciado, primeiro commit `995cd76`

### Domínios novos
- **Dev:** `https://api-dev.mgpapelaria.com.br` — funcionando, já está em `/etc/hosts` apontando pro `192.168.1.199`
- **Prod:** `https://api.mgpapelaria.com.br` — config nginx já criada em `/opt/www/MGweb/data/nginx/api.conf`, falta DNS/LetsEncrypt apontar (você cuida quando for a hora)
- Reload do nginx do MGweb já foi feito (dev)

### Endpoints implementados (paridade exata com MGAuth)
| Método | Path | Função |
|---|---|---|
| `POST` | `/api/oauth/token` | Form login + cookie + redirect (rota nomeada `auth`) |
| `POST` | `/api/oauth/token/json` | Login JSON (frontends Quasar) |
| `POST` | `/api/refresh` | Refresh token |
| `GET`  | `/api/check-token` | Validação de Bearer (throttle 600/min) |
| `POST` | `/api/logout` | Revoga tokens + limpa cookies |
| `GET`  | `/login` | Tela Blade portada do MGAuth (mesmo visual, mesmo form) |
| `GET`  | `/` | Redirect pra `/login` |
| `GET`  | `/up` | Health-check do L13 |

### Cookies emitidos (idênticos ao MGAuth)
- `access_token` em `.mgpapelaria.com.br`, `Secure`, `SameSite=None`, `HttpOnly=false`
- `user_id` em `.mgpapelaria.com.br`, mesmas flags

### Smoke-tests validados
✅ `GET /` → 302 → `/login`
✅ `GET /login` → 200, HTML com form de username/password (CSRF, btn-warning)
✅ `GET /up` → 200 (health-check)
✅ `GET /api/check-token` sem token → **401 com JSON `{"message":"Usuario nao logado!","expires_in":null,"user_id":null,"usuario":null}`** (resposta IDÊNTICA ao MGAuth)
✅ Conexão com `mgsis.tblusuario` funcionando (artisan db:show lista todas as tabelas)

**O que NÃO foi testado** (precisa de credenciais reais — fica pra você amanhã):
- `POST /api/oauth/token/json` com usuário/senha válidos → emissão de token
- `GET /api/check-token` com token vivo → 200 + payload do user
- `POST /api/logout` → revoga
- Login pelo form em `/login` com redirect_uri real

## Estrutura dos usuários/grupos — preservada

Você pediu pra manter usuários e grupos existentes. Mantido **100%**:
- O novo app usa o MESMO `tblusuario` do banco `mgsis`, sem migrations, sem alterações de schema
- Usa as MESMAS chaves OAuth (md5sums conferem com as do MGAuth e MGspa/laravel)
- Usa o MESMO client OAuth (UUID `991af1fd-a8a0-4aea-aa61-181ed084c062`, secret `9XnTTSglqcDuJyKhe56J7li4zUNKWrGNed0u7Hjq`) já existente em `mgsis.oauth_clients`
- Tokens emitidos pelo MGAuth continuam válidos no api novo (mesma chave de assinatura + mesma tabela `oauth_access_tokens`)

## Como testar amanhã

### Testes rápidos via curl
```bash
# 1) Health
curl -k https://api-dev.mgpapelaria.com.br/up

# 2) Login JSON (substituir USER/SENHA)
curl -k -X POST https://api-dev.mgpapelaria.com.br/api/oauth/token/json \
  -H 'Content-Type: application/json' \
  -d '{
    "grant_type": "password",
    "client_id": "991af1fd-a8a0-4aea-aa61-181ed084c062",
    "client_secret": "9XnTTSglqcDuJyKhe56J7li4zUNKWrGNed0u7Hjq",
    "username": "SEU_USUARIO",
    "password": "SUA_SENHA"
  }'

# 3) Validar token (pegar access_token do passo 2)
curl -k https://api-dev.mgpapelaria.com.br/api/check-token \
  -H "Authorization: Bearer SEU_TOKEN"

# 4) Tela de login no navegador
# Abrir: https://api-dev.mgpapelaria.com.br/login?redirect_uri=https://sistema-dev.mgpapelaria.com.br
```

### Compare com o MGAuth (mesmo teste no antigo)
```bash
# Mesmas chamadas trocando api-dev por auth-dev — payload deve ser IDÊNTICO
```

## Cutover dos consumidores (depois que você validar)

Quando der a OK, é trocar **1 variável de ambiente em cada consumidor**:

| Consumidor | Arquivo | Linha a trocar |
|---|---|---|
| MGLara | `/opt/www/MGLara/.env` | `AUTH_API_URL="https://api-dev.mgpapelaria.com.br"` |
| MGsis | `/opt/www/MGsis/protected/.env.php` | `define('AUTH_API_URL', 'https://api-dev.mgpapelaria.com.br');` |
| Frontends Quasar (pessoas/contas/negocios/notas) | `/opt/www/MGspa/pessoas/.env` (symlink) | `API_AUTH_URL=https://api-dev.mgpapelaria.com.br` |
| MGspa/laravel | `/opt/www/MGspa/laravel/.env` | `AUTH_API_URL` e `SSO_HOST_QUASAR` |

Depois disso o MGAuth pode ser desligado: `cd /opt/www/MGAuth && docker compose down`.

**Backup recomendado antes do cutover:**
```bash
cd /opt/www/MGdb
docker compose exec -T mgdb pg_dump -U mgsis -d mgsis -t 'oauth_*' > /tmp/oauth_backup_$(date +%F).sql
```

## Detalhes técnicos não-óbvios (pra você não tropeçar)

1. **Permissões do container**: o `php-fpm` roda como `www-data` (uid 82) e precisa escrever em `storage/` e `bootstrap/cache/`. Já foi ajustado (`chown -R www-data:www-data` + `chmod 0775`, com chaves OAuth em `0600`). Se você precisar mexer nesses dirs do host, lembre que o owner é www-data.

2. **`laravel/tinker` removido** do `composer.json`: a versão 2.11.1 (mais recente) ainda não declara compatibilidade com Laravel 13. Quando sair uma release compatível, é só rodar `composer require laravel/tinker`. **Não é essencial pra auth**.

3. **`Passport::ignoreRoutes()` em `register()`, não em `boot()`**: o PassportServiceProvider roda boot ANTES dos providers da app, então a flag tem que ser setada antes (no `register()`). Detalhe sutil que dá problema se você for olhar o `AuthServiceProvider` de outro projeto.

4. **`Passport::enablePasswordGrant()` é obrigatório no Passport 11+**: vem desligado por padrão. Esquecer isso quebra todo o fluxo de login. Está habilitado em `AuthServiceProvider::boot()`.

5. **`AccessTokenController` resolvido via container** (`app(AccessTokenController::class)`), não via `new`. Em Passport 13 a assinatura do construtor mudou — instanciar manual quebra.

6. **`config/services.php`** centraliza os `env()` que o AuthController usa (cliente OAuth, domínio do cookie, redirect padrão). Tirei os `env()` de dentro do controller (padrão L13 — `env()` só em config).

7. **Proxy fallback (catch-all) pra rotas legadas** está em `routes/api.php` mas **comentado**. Marco 1 é só auth — quando começar a migrar controllers (Marcos 3+), descomentar.

8. **CORS** liberado por regex pra `*.mgpapelaria.com.br` + `localhost` (qualquer porta), com `supports_credentials: true`. Vê `config/cors.php`.

9. **Tela de login (`/login`)**: porta literal do MGAuth, incluindo o CSS `app-azul.css` (3.4 MB), os JPGs de fundo e o favicon. Visual idêntico.

10. **`/etc/hosts`** já tinha `api-dev.mgpapelaria.com.br` apontando pro IP da máquina — não precisei configurar DNS no host.

## Arquivos criados / modificados

### `/opt/www/MGspa/api/` (novo, 54 arquivos commitados)
- `composer.json`, `composer.lock`, `.env`, `.env.example`
- `docker/Dockerfile`, `docker-compose.yaml`
- `bootstrap/app.php`, `bootstrap/providers.php`
- `artisan`, `public/index.php`, `public/.htaccess`
- `routes/api.php`, `routes/web.php`, `routes/console.php`
- `config/` (12 arquivos: app, auth, cache, cors, database, filesystems, hashing, logging, mail, passport, queue, services, session, view)
- `app/Models/Usuario.php` (porta enxuta do `Mg\Usuario\Usuario`)
- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/Auth/AuthController.php` (porta literal do MGAuth)
- `app/Providers/AppServiceProvider.php`, `app/Providers/AuthServiceProvider.php`
- `resources/views/layouts/app.blade.php`, `resources/views/login.blade.php`
- `public/css/app-azul.css`, `public/fundologin.jpg`, `public/fundologinazul.jpg`, `public/favicon.ico`, `public/robots.txt`
- `storage/oauth-private.key`, `storage/oauth-public.key` (mesmas chaves do MGAuth, md5 conferido)

### `/opt/www/MGweb/` (commit `4a6700a`)
- `data/nginx-dev/api.conf` — server block pra `api-dev.mgpapelaria.com.br` → `host.docker.internal:9007`
- `data/nginx/api.conf` — server block pra `api.mgpapelaria.com.br` (prod, com LetsEncrypt)

### Não tocado
- `/opt/www/MGspa/laravel/` (continua em Laravel 8 rodando como sempre)
- `/opt/www/MGAuth/` (continua rodando em paralelo até você fazer o cutover)
- `/opt/www/MGLara/`, `/opt/www/MGsis/`, frontends Quasar (não precisam de mudança até o cutover)

## Próximos passos sugeridos

1. **Você testa o login real amanhã** (curl com credenciais + login via `/login` no navegador).
2. **Se passar:** cutover dos consumidores (uma env var por app) → desligar MGAuth.
3. **Marco 3+:** começar a migrar controllers do MGspa/laravel pro api novo, um por commit. Recomendação de ordem (do mais leve pro mais pesado) está no plano em `/home/usuario/.claude/plans/tem-como-manter-somete-starry-falcon.md`.
4. **Marco final:** quando MGspa/laravel ficar vazio → desligar.

## Comandos úteis

```bash
# Status do container
cd /opt/www/MGspa/api && docker compose ps

# Ver logs do app
docker compose logs -f api
docker compose exec api tail -f /opt/www/MGspa/api/storage/logs/laravel.log

# Restart
docker compose restart

# Rebuild (depois de mexer no Dockerfile)
docker compose build && docker compose up -d --force-recreate

# Reload nginx do MGweb (depois de mexer em data/nginx-dev/*.conf)
cd /opt/www/MGweb && docker compose -f docker-compose.dev.yml exec mgweb nginx -s reload

# Listar rotas
docker compose -f /opt/www/MGspa/api/docker-compose.yaml exec api php artisan route:list

# Status geral
docker compose -f /opt/www/MGspa/api/docker-compose.yaml exec api php artisan about
```

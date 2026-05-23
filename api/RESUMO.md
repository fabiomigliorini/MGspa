# Resumo da execução — Marcos 1+3 do strangler-fig

> **Status:** ✅ O novo projeto Laravel 13 está em pé, com **paridade total de auth com o MGAuth** (Marco 1) **+ 11 controllers já migradas** do MGspa/laravel (Marco 3) **+ proxy fallback ativo** pras rotas ainda não migradas. **Login real validado e testado pelo usuário no frontend Quasar `pessoas`.**
> **Nada em produção foi tocado** — só foi adicionado. O cutover (trocar `AUTH_API_URL` dos consumidores) fica pra você fazer manualmente após validar os testes.

## O que existe agora

### Novo projeto: `/opt/www/MGspa/api`
- **Laravel 13.11.2** + **Laravel Passport 13** + **PHP 8.3.31**
- Skeleton slim do L13 (`bootstrap/app.php` com `Application::configure()`, sem `app/Http/Kernel.php`, sem `app/Console/Kernel.php`, `bootstrap/providers.php`)
- Container Docker próprio: imagem `mgspa-api`, container `mgspa-api`, porta interna **9007**
- Conectado no MESMO banco `mgsis.mgsis` (mesmo `tblusuario`, mesmas `oauth_*`, mesmas chaves OAuth do MGAuth)
- Autoload com namespaces `App\\` e `Mg\\` (preservado do legacy)
- Git: branch master, **7 commits** até aqui

### Domínios novos
- **Dev:** `https://api-dev.mgpapelaria.com.br` — funcionando, já está em `/etc/hosts`
- **Prod:** `https://api.mgpapelaria.com.br` — config nginx já criada em `/opt/www/MGweb/data/nginx/api.conf`, falta DNS/LetsEncrypt apontar (você cuida quando for a hora)

## Endpoints implementados

### Auth (paridade com MGAuth — Marco 1)
| Método | Path | Função |
|---|---|---|
| `POST` | `/api/oauth/token` | Form login + cookie + redirect |
| `POST` | `/api/oauth/token/json` | Login JSON |
| `POST` | `/api/refresh` | Refresh token |
| `GET`  | `/api/check-token` | Validação Bearer (throttle 600/min) |
| `POST` | `/api/logout` | Revoga tokens + limpa cookies |
| `GET`  | `/login` | Tela Blade portada do MGAuth |
| `GET`  | `/` | Redirect pra `/login` |
| `GET`  | `/up` | Health-check L13 |

### Controllers migradas (Marco 3) — 11 controllers, 74 rotas v1

Todas sob `/api/v1/` com `auth:api` aplicado, **paths idênticos ao legacy**.

| # | Controller | Domínio | Rotas | Autorização | Notas |
|---|---|---|---|---|---|
| 1 | `Feriado` | Mg\Feriado | 7 | `Recursos Humanos` | + `gerar-ano` (BrasilAPI) |
| 2 | `TipoSetor` | Mg\Filial | 6 | `Recursos Humanos` | bloqueia destroy se vinculado a Setor |
| 3 | `Setor` | Mg\Filial | 6 | `Recursos Humanos` | eager-load UnidadeNegocio + TipoSetor |
| 4 | `UnidadeNegocio` | Mg\Filial | 7 | `Meta` | bloqueia destroy se vinculado a Meta |
| 5 | `Etnia` | Mg\Pessoa | 7 | (qualquer auth) | filtro `etnia` + `status` |
| 6 | `EstadoCivil` | Mg\Pessoa | 7 | (qualquer auth) | filtro `estadocivil` + `status` |
| 7 | `GrauInstrucao` | Mg\Pessoa | 7 | (qualquer auth) | filtro `grauinstrucao` + `status` |
| 8 | `Cargo` | Mg\Colaborador | 8 | `Recursos Humanos` (mutations) | SQL raw + `pessoas-cargo` |
| 9 | `GrupoCliente` | Mg\Pessoa | 8 | `Administrador`/`Financeiro` | paginação 25/pg + tratamento FK 23503 |
| 10 | `Empresa` | Mg\Filial | 5 | (qualquer auth) | CRUD básico, sem ativar/inativar |
| 11 | `CertidaoEmissor` | Mg\Certidao | 6 | (qualquer auth) | paginação, MgController::filtros() + scopes |

### Proxy fallback (transparente)
Catch-all em `Route::any('{any}')` repassa rotas ainda não migradas pro MGspa/laravel antigo. Controlado por `LEGACY_PROXY_ENABLED=true` no `.env`. Quando o cutover dos consumidores for feito, os frontends apontam só pra `api-dev` e a migração das outras controllers fica transparente (cada controller migrada simplesmente "promove" da camada de proxy pra nativa).

### Smoke-tests validados
✅ Auth: `/api/check-token` sem token → 401 com JSON idêntico ao MGAuth
✅ Auth: `/login` → 200 com form Blade
✅ Migradas: as 9 controllers sem token → 401 `Unauthenticated`
✅ Proxy: rota não migrada (`/api/v1/grupoeconomico/select`) → 401 do legacy (proxiado)
✅ `php artisan about` → Laravel 13, Postgres, intl, bcmath, gd, zip
✅ `php artisan route:list` → 71 rotas (auth + 63 v1 nativas + storage + up + login)

## Bases trazidas (úteis pra próximas migrações)

| Arquivo | Função |
|---|---|
| `app/Mg/MgModel.php` | Base de todos os models do domínio MG (audit codusuariocriacao/alteracao, timestamps criacao/alteracao, scopes ativo/inativo/palavras) |
| `app/Mg/MgController.php` | Base de controllers do MG (helper `filtros()` pra parse de query params) |
| `app/Mg/Usuario/Autorizador.php` | Verificação de grupos (consulta tblgrupousuariousuario) |
| `app/Models/Usuario.php` | Model de auth do Passport (enxuto — tbl `tblusuario`, trait HasApiTokens) |

**Stubs minimais** (vão ser substituídos por versão completa quando o domínio for migrado):
- `app/Mg/Filial/Filial.php`
- `app/Mg/Pessoa/Pessoa.php`
- `app/Mg/Pessoa/PessoaCertidao.php`
- `app/Mg/Meta/MetaUnidadeNegocio.php`
- `app/Mg/Rh/PeriodoColaboradorSetor.php`

**Base classes** (`app/Mg/`):
- `MgModel.php` — audit trail + scopes
- `MgController.php` — helper `filtros()` pra sort/fields/page
- `MgService.php` — ativar/inativar + qryOrdem/qryColunas
- `Usuario/Autorizador.php` — verificação de grupos de usuário

## Estrutura dos usuários/grupos — preservada

- Usa o MESMO `tblusuario` do banco `mgsis`, sem migrations, sem alterações de schema
- Usa as MESMAS chaves OAuth (md5sums conferidos com MGAuth e MGspa/laravel)
- Usa o MESMO client OAuth (UUID `991af1fd-a8a0-4aea-aa61-181ed084c062`) já em `mgsis.oauth_clients`
- Tokens emitidos pelo MGAuth continuam válidos no api novo (mesma chave de assinatura + mesma tabela `oauth_access_tokens`)
- `Autorizador` consulta `tblgrupousuariousuario` direto — os grupos de cada usuário (Administrador, Recursos Humanos, Meta, Financeiro etc.) permanecem inalterados

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

# 3) Validar token
curl -k https://api-dev.mgpapelaria.com.br/api/check-token \
  -H "Authorization: Bearer SEU_TOKEN"

# 4) Testar um endpoint migrado (Feriado é o mais simples)
curl -k https://api-dev.mgpapelaria.com.br/api/v1/feriado/ \
  -H "Authorization: Bearer SEU_TOKEN"

# 5) Testar uma rota NÃO migrada (deve ir via proxy pro legacy)
curl -k https://api-dev.mgpapelaria.com.br/api/v1/grupoeconomico/select \
  -H "Authorization: Bearer SEU_TOKEN"

# 6) Comparar: mesmo endpoint diretamente no legacy
curl -k https://api-mgspa-dev.mgpapelaria.com.br/api/v1/feriado/ \
  -H "Authorization: Bearer SEU_TOKEN"
# (deve retornar exatamente o mesmo payload do passo 4 — paridade)
```

### Tela de login no navegador
```
https://api-dev.mgpapelaria.com.br/login?redirect_uri=https://sistema-dev.mgpapelaria.com.br
```

## Cutover dos consumidores (depois que você validar)

Quando der a OK, é trocar **1 variável de ambiente em cada consumidor**:

| Consumidor | Arquivo | Linha a trocar |
|---|---|---|
| MGLara | `/opt/www/MGLara/.env` | `AUTH_API_URL="https://api-dev.mgpapelaria.com.br"` |
| MGsis | `/opt/www/MGsis/protected/.env.php` | `define('AUTH_API_URL', 'https://api-dev.mgpapelaria.com.br');` |
| Frontends Quasar | `/opt/www/MGspa/pessoas/.env` (symlink) | `API_AUTH_URL=https://api-dev.mgpapelaria.com.br`<br>`API_URL=https://api-dev.mgpapelaria.com.br/api/` |
| MGspa/laravel | `/opt/www/MGspa/laravel/.env` | `AUTH_API_URL` e `SSO_HOST_QUASAR` |

**Importante:** quando os frontends Quasar mudarem `API_URL` para `api-dev`:
- As 9 controllers migradas serão atendidas NATIVAMENTE pelo novo api
- Todas as outras rotas serão proxied transparentemente pro legacy
- Da perspectiva do frontend, nada muda

Depois do cutover, MGAuth pode ser desligado: `cd /opt/www/MGAuth && docker compose down`.

**Backup recomendado antes do cutover:**
```bash
cd /opt/www/MGdb
docker compose exec -T mgdb pg_dump -U mgsis -d mgsis -t 'oauth_*' > /tmp/oauth_backup_$(date +%F).sql
```

## Próximos passos sugeridos

1. **Testar manualmente** o que foi entregue (auth + 9 controllers migradas).
2. **Cutover de auth** (trocar `AUTH_API_URL` nos consumidores → MGAuth fora do ar).
3. **Continuar migrando controllers** seguindo [MIGRAR-CONTROLLER.md](MIGRAR-CONTROLLER.md). Próximas sugestões (em ordem de complexidade):
   - **Médios**: `pessoa` (nested telefone/email/endereco/certidao/conta), `colaborador` (cargo, ferias), `meta` + `meta-dashboard`
   - **Pesados / integrações**: PDFs (dompdf 2→3, mpdf, phpspreadsheet, picqer/barcode), NFe (`nfephp-org/*`), Pix BB, Stone, PagarMe, Saurus, Woo
4. **Marco final:** quando MGspa/laravel ficar vazio → desligar (`cd /opt/www/MGspa/laravel && docker compose down`) + remover proxy fallback.

## Detalhes técnicos não-óbvios

1. **Permissões do container**: `php-fpm` roda como `www-data` (uid 82). `storage/` e `bootstrap/cache/` foram `chown`-eados pra `www-data` (`0775`). Chaves OAuth em `0600` (Passport exige).

2. **`laravel/tinker` removido** do `composer.json`: a versão 2.11.1 (mais recente) ainda não declara compatibilidade com Laravel 13. Quando sair release, é só `composer require laravel/tinker`.

3. **`Passport::ignoreRoutes()` em `register()`, não em `boot()`**: o PassportServiceProvider roda boot ANTES dos providers da app, então a flag tem que ser setada antes.

4. **`Passport::enablePasswordGrant()` é obrigatório no Passport 11+**: vem desligado por padrão.

5. **`AccessTokenController` resolvido via container** (`app(AccessTokenController::class)`), não via `new`. Em Passport 13 a assinatura mudou.

6. **`config/services.php`** centraliza os `env()` que o AuthController usa.

7. **Proxy fallback** controlado por `LEGACY_PROXY_ENABLED` no `.env`. SSL do legacy não verificado (`LEGACY_VERIFY_SSL=false` em dev — cert autoassinado).

8. **CORS** liberado por regex pra `*.mgpapelaria.com.br` + `localhost` (qualquer porta), com `supports_credentials: true`.

9. **CRUDs migrados omitem `protected $dates`** (deprecated no L13) e usam `casts` com `datetime`.

10. **Models stubs**: pra evitar pull tipo "trazer todo o domínio" pra cada controller migrada, criei stubs minimais nos models que só são referenciados por `belongsTo`/`hasMany`. Quando um domínio for migrado integralmente, sobrescrevem o stub.

## Arquivos críticos

### `/opt/www/MGspa/api/`
- `composer.json` (com autoload `App\\` + `Mg\\`)
- `bootstrap/app.php`, `bootstrap/providers.php`
- `routes/api.php` (auth + 9 controllers migradas + proxy fallback)
- `routes/web.php` (login form Blade)
- `config/auth.php`, `config/passport.php`, `config/cors.php`, `config/services.php`
- `app/Models/Usuario.php` (auth)
- `app/Mg/MgModel.php`, `app/Mg/MgController.php`, `app/Mg/Usuario/Autorizador.php`
- `app/Mg/Feriado/`, `app/Mg/Filial/`, `app/Mg/Pessoa/`, `app/Mg/Colaborador/`
- `storage/oauth-{private,public}.key`
- [MIGRAR-CONTROLLER.md](MIGRAR-CONTROLLER.md) — guia pra migrar próximas controllers

### `/opt/www/MGweb/`
- `data/nginx-dev/api.conf` — server block pra `api-dev.mgpapelaria.com.br` → porta 9007
- `data/nginx/api.conf` — server block pra `api.mgpapelaria.com.br` (prod, com LetsEncrypt)

### Não tocado
- `/opt/www/MGspa/laravel/` (continua em Laravel 8 rodando como sempre)
- `/opt/www/MGAuth/` (continua rodando em paralelo até cutover)
- `/opt/www/MGLara/`, `/opt/www/MGsis/`, frontends Quasar

## Comandos úteis

```bash
# Status do container
cd /opt/www/MGspa/api && docker compose ps

# Logs
docker compose logs -f api
docker compose exec api tail -f /opt/www/MGspa/api/storage/logs/laravel.log

# Restart
docker compose restart

# Rebuild (depois de mexer no Dockerfile)
docker compose build && docker compose up -d --force-recreate

# Reload nginx do MGweb
cd /opt/www/MGweb && docker compose -f docker-compose.dev.yml exec mgweb nginx -s reload

# Listar rotas
docker compose -f /opt/www/MGspa/api/docker-compose.yaml exec api php artisan route:list

# Status geral
docker compose -f /opt/www/MGspa/api/docker-compose.yaml exec api php artisan about

# Conferir banco
docker compose -f /opt/www/MGspa/api/docker-compose.yaml exec api php artisan db:show
```

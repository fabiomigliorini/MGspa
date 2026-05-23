# Guia: migrar uma controller do MGspa/laravel pro api novo

Pattern usado na migração do `Feriado` (commit de referência). Cada migração = 1 commit pequeno.

## Pré-requisitos (já feitos)

- Autoload `Mg\\` → `app/Mg/` configurado em [composer.json](composer.json)
- `MgModel` base portada em [app/Mg/MgModel.php](app/Mg/MgModel.php)
- `Autorizador` portado em [app/Mg/Usuario/Autorizador.php](app/Mg/Usuario/Autorizador.php)
- Proxy fallback ativo em [routes/api.php](routes/api.php) — rotas ainda não migradas passam transparente pro MGspa/laravel

## Passo a passo (cada controller)

### 1. Identificar arquivos no legacy

```bash
ls -la /opt/www/MGspa/laravel/app/Mg/<Dominio>/
```

Tipicamente:
- `<Dominio>.php` — model
- `<Dominio>Controller.php`
- `<Dominio>Service.php` (se tiver lógica de negócio)
- `<Dominio>StoreRequest.php`, `<Dominio>UpdateRequest.php`, ...
- `<Dominio>Resource.php`, ...

### 2. Encontrar as rotas no legacy

```bash
grep -nE "<dominio>" /opt/www/MGspa/laravel/routes/api.php
```

Anotar os paths exatos (eles precisam ficar IDÊNTICOS no api novo pra o cutover ser transparente).

### 3. Portar os arquivos pra `/opt/www/MGspa/api/app/Mg/<Dominio>/`

Pode ser literalmente `cp` + ajustes mínimos:

```bash
mkdir -p /opt/www/MGspa/api/app/Mg/<Dominio>
cp /opt/www/MGspa/laravel/app/Mg/<Dominio>/*.php /opt/www/MGspa/api/app/Mg/<Dominio>/
```

**Ajustes que normalmente são necessários:**
- `use Mg\Usuario\Usuario;` → `use App\Models\Usuario;` (no model)
- `use Illuminate\Routing\Controller;` → `use App\Http\Controllers\Controller;` (no controller)
- Remover `protected $dates = [...]` (deprecated no L13 — usar `casts` com `datetime`)
- Validar que `Carbon`, `DB`, `Http` ainda estão importados via `use Carbon\Carbon; use Illuminate\Support\Facades\DB;` etc. (deveria estar)
- **Models que referenciam classes de outros domínios ainda não migrados**: deixar como string (`$this->belongsTo('Mg\OutroDominio\Outro', ...)`) OU criar stub model no api novo se for usado de fato. No 99% dos casos, basta criar o(s) model(s) básicos sob demanda.

### 4. Registrar as rotas em [routes/api.php](routes/api.php)

Dentro do `Route::middleware(['auth:api'])->prefix('v1')->group(...)`, replicando os MESMOS paths do legacy. Exemplo:

```php
Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    // ... outras controllers ...

    // <Dominio> (migrado em DD/MM/YYYY)
    Route::get('<dominio>/', [\Mg\<Dominio>\<Dominio>Controller::class, 'index']);
    Route::post('<dominio>/', [\Mg\<Dominio>\<Dominio>Controller::class, 'store']);
    // ... etc
});
```

### 5. Rodar dump-autoload (só na 1ª vez que adicionar um novo namespace `Mg\<X>\`)

```bash
docker compose exec api composer dump-autoload -o
```

Quando você só adiciona arquivos em namespaces já existentes, não precisa.

### 6. Smoke-test

```bash
# Listar rotas — deve mostrar as novas + sumir do "ANY api/{any}" o que era proxiado
docker compose exec api php artisan route:list | grep <dominio>

# Sem token (esperado: 401 "Unauthenticated.")
curl -sk -H "Accept: application/json" https://api-dev.mgpapelaria.com.br/api/v1/<dominio>/

# Com token válido (depois pega um do MGAuth ou do api novo via /api/oauth/token/json)
curl -sk -H "Accept: application/json" -H "Authorization: Bearer SEU_TOKEN" \
  https://api-dev.mgpapelaria.com.br/api/v1/<dominio>/

# Comparar com o legacy (mesmo token, vai pra api-mgspa-dev)
curl -sk -H "Accept: application/json" -H "Authorization: Bearer SEU_TOKEN" \
  https://api-mgspa-dev.mgpapelaria.com.br/api/v1/<dominio>/

# Diff dos dois retornos deveria ser zero (ou só timestamps/ordem)
```

### 7. Commit

```bash
cd /opt/www/MGspa/api
git add app/Mg/<Dominio>/ routes/api.php
git commit -m "[MIGRA] <Dominio> do MGspa/laravel — paridade com legacy

- app/Mg/<Dominio>/<Dominio>.php
- app/Mg/<Dominio>/<Dominio>Controller.php
- app/Mg/<Dominio>/<Dominio>Service.php (se aplicável)
- app/Mg/<Dominio>/<Dominio>{Store,Update,...}Request.php
- app/Mg/<Dominio>/<Dominio>{,...}Resource.php
- routes/api.php — paths v1/<dominio>/* idênticos ao legacy
"
```

## Ordem sugerida (do plano, leve → pesado)

**CRUDs pequenos** (1-2h cada):
1. ~~`feriado`~~ ✅ feito
2. `tipo-setor`
3. `setor`
4. `unidade-negocio`
5. `etnia`
6. `estado-civil`
7. `grau-instrucao`
8. `cargo`
9. `grupo-cliente`

**Médios** (1 dia cada):
10. `pessoa` (com nested telefone/email/endereco/certidao/conta — pode quebrar em sub-PRs)
11. `colaborador` (com cargo, ferias)
12. `meta` + `meta-dashboard`

**Pesados / integrações externas** (avaliar caso a caso):
13. PDFs (dompdf 2→3, mpdf, phpspreadsheet, picqer/barcode)
14. NFe (`nfephp-org/*`)
15. Pix BB
16. Stone, PagarMe, Saurus
17. Woo

## Quando todos os controllers estiverem migrados

1. Setar `LEGACY_PROXY_ENABLED=false` no `.env`
2. Smoke-test geral (rota qualquer não migrada deve dar 404, não proxia)
3. Remover o bloco `if (filter_var(env('LEGACY_PROXY_ENABLED'...` de [routes/api.php](routes/api.php)
4. Remover as 3 envs `LEGACY_*` do `.env`/`.env.example`
5. `docker compose down` em `/opt/www/MGspa/laravel` — desliga o backend antigo
6. Festa

# SPEC — Motor de imagens (CDN)

Task-mãe: **TASK-82**. Absorve a TASK-64 (URL única) e a TASK-208 (foto da categoria da prancheta).
Decisões tomadas item a item com o Fábio em 25/10/2026; documento escrito em 26/10/2026 para a
implementação na volta da viagem.

## Contexto

As fotos de produto, marca e usuário vivem numa pasta única com 94 mil `{codimagem}.jpg`, servidas
por symlink público e com a URL montada à mão em 14 pontos do backend e 2 do front, dois hosts
diferentes (MGLara e API). O domínio `api/app/Mg/Imagem` existe mas está quebrado: o
`ImagemService` instancia `ProdutoImagemService` (não existe) e o `SlimAdapter` chama `read()`/
`toJpeg()`, que não existem no Intervention 4.1.2 instalado. Não há lixeira usável, nem tamanhos
para listagem, nem controle de quem pode mexer.

Números do banco em 25/10/2026: 94.402 imagens, 9.149 inativas, 8.283 órfãs (848 ativas), 48.469
produtos com imagem, 162 marcas, 85 registros com `arquivo` nulo. Dev não tem os arquivos: o symlink
`api/public/imagens → /opt/www/Arquivos/Imagens` está pendurado e o script `copiar-imagens` traz tudo
de prod por rsync.

## Decisões fechadas (não reabrir)

| # | Tema | Decisão |
|---|---|---|
| 1 | Vínculo | FKs existentes ficam; `ImagemVinculoService` conhece a lista e responde "onde está amarrada" e "órfãs" |
| 2 | Tipo | coluna `tipo` em `tblimagem`; lista fixa em PHP (`ImagemTipo`) |
| 3 | Arquivo | nome = uuid v4, subpastas 2+2 (`ab/cd/{uuid}.{ext}`) |
| 4 | Original | formato mantido (jpeg/png/webp), EXIF aplicado e removido, lado maior ≤ 2048 px (reduz só se passar) |
| 5 | Presets | fixos no código (`thumb` 160, `card` 400, `detalhe` 1024, `full` = original); formato pela extensão (`.webp` apps, `.jpg` integrações) |
| 6 | Entrega | nginx serve do cache público via symlink `api/public/imagem` + `try_files` que já existe; miss cai no Laravel, que gera, grava e responde |
| 7 | URL/cache | imutável: original novo = uuid novo; `Cache-Control: public, max-age=1y, immutable` |
| 8 | Acesso | tipo decide: produto/marca/hierarquia/prancheta públicos; `usuario` (e futuros de pessoa) privado, cache fora do public, GET com `auth_or_signed` |
| 9 | Permissão | herdada do dono, lida de `tblpermissao` pelo `Mg\Permissao\Autorizador\Autorizador` (string): produto `ProdutoPolicy.update`, marca `MarcaPolicy.update`, hierarquia `SecaoProdutoPolicy.update`/`FamiliaProduto…`/`GrupoProduto…`/`SubGrupoProduto…`, prancheta `PranchetaPolicy.update`, usuário `UsuarioPolicy.update`; biblioteca `ImagemPolicy.listing`, restaurar `ImagemPolicy.update`, esvaziar `ImagemPolicy.delete` |
| 10 | Lixeira | `inativo` como no resto do sistema: Excluir marca `inativo` (continua amarrada, some das telas); Restaurar limpa; Esvaziar apaga de vez (original, caches, linha), manual, com filtro "há mais de N dias" |
| 11 | Editor | `MgImagemUpload` em `@components` com **Cropper.js v2** (cortar, girar, zoom, proporção); o `MgSlim` fica nas 5 telas atuais |
| 12 | Migração | comando move de vez `{cod}.jpg` → `original/ab/cd/{uuid}.jpg`; sem redirect; MGLara perde as fotos (aceito); symlink `public/imagens` sai |
| 13 | Front | API devolve `imagem` = uuid; helper único `urlImagem(uuid, preset)` em `@components/utils/imagem.js` |

Suposições a confirmar na implementação: tipos iniciais `produto, marca, secaoproduto,
familiaproduto, grupoproduto, subgrupoproduto, prancheta, usuario` (o último só porque
`tblusuario.codimagem` já existe; sem tela nova); na galeria do produto, "Remover" vira "Excluir"
(lixeira) e a primeira imagem enviada vira a principal do produto e das variações/embalagens sem
imagem (igual ao MGLara).

Por que cada escolha (resumo): hash como nome foi recusado (duas fotos iguais em produtos diferentes
são duas imagens); presets fixos evitam abuso e cache sem fim; URL imutável porque limpar cache em
disco não limpa navegador, service worker nem Dexie; permissão por string porque já está no banco e
é configurável sem deploy (os 79 controllers da API autorizam por grupo e nenhum lê `tblpermissao`
hoje — o motor é o primeiro); Cropper.js porque o Slim 5.3.2 vendorado parou em 2021 e o
vue-advanced-cropper em 2024 (Cropper.js 2.3.1 é de 16/10/2026, MIT).

## Arquitetura

```
/opt/www/Arquivos/Imagens/                 (env IMAGENS_PATH; disk 'imagem' em config/filesystems.php)
  original/ab/cd/{uuid}.{jpg|png|webp}
  publico/ab/cd/{uuid}/{preset}.{webp|jpg}   ← api/public/imagem -> aqui (symlink commitado, como o atual)
  privado/ab/cd/{uuid}/{preset}.{ext}        ← fora do public
```

- **URL pública**: `https://api…/imagem/ab/cd/{uuid}/{preset}.{ext}` (sem `/api`: rotas de `api.php`
  nascem em `/api/v1`; esta vai em `routes/web.php` com `withoutMiddleware('web')`, como `/oauth/*`).
  Hit = nginx entrega o arquivo. Miss = `index.php` → `ImagemPublicaController@mostrar`: valida
  `ab/cd` = início do uuid, acha a imagem ativa e pública (senão 404 sem gerar), gera o preset com
  lock `Cache::lock("imagem:{uuid}:{preset}.{ext}")`, grava em `.tmp` + `rename()`, responde
  `response()->file()` com os mesmos headers de cache (padrão de `PdvCatalogoController@arquivo`).
- **URL privada**: `GET /api/v1/imagem/privado/{uuid}/{preset}.{ext}` com `->withoutMiddleware('auth:api')
  ->middleware('auth_or_signed')` (padrão de `etiqueta.arquivo`); `ImagemService::url()` devolve
  `URL::temporarySignedRoute` pra tipo privado.
- **Intervention 4.1.2 (API real do vendor, não a da doc v3)**: `ImageManager::usingDriver(Gd\Driver::class)`,
  `decodePath()`/`decodeBinary()`, `orient()`, `scaleDown(2048, 2048)`,
  `encodeUsingFileExtension('webp', quality: 82)`, `width()/height()`. PNG com alpha → `.jpg`:
  achatar em branco antes (`createImage(w, h)->fill('ffffff')->place($img)`). GD não carrega EXIF, o
  encode já sai limpo. GD precisa de WebP: Dockerfile `libwebp-dev` +
  `docker-php-ext-configure gd --with-jpeg --with-webp` (hoje `gd_info()["WebP Support"]` é falso).
- **Permissão**: `ImagemService::autoriza(ImagemTipo)` → `Autorizador::autoriza($tipo->permissao())`.
  Grupos já amarrados em `tblpermissao`: `ProdutoPolicy.update`, `SecaoProdutoPolicy.update`,
  `PranchetaPolicy.update`, `ImagemPolicy.*` = Administrador + Estoque; `MarcaPolicy.update`,
  `GrupoProdutoPolicy.update`, `UsuarioPolicy.update` = Administrador.
- **Permissões do php-fpm**: uid 82; criar pastas com `mkdir(0775, true)` + `chmod` explícito e arquivos
  0664; comandos que mexem em arquivo rodam `docker compose exec -u 82 …`.

## Backend — `api/app/Mg/Imagem/`

- `ImagemTipo.php` (enum string): `publico()`, `permissao()`, `vinculo()` → `[tabela, colunaFK, chave]`
  (produto é especial: galeria `tblprodutoimagem` com `ordem`).
- `ImagemPreset.php`: `PRESETS = ['thumb'=>160,'card'=>400,'detalhe'=>1024,'full'=>null]`,
  `EXTENSOES = ['webp'=>82,'jpg'=>85]`.
- `ImagemMotor.php`: só Intervention + filesystem. `normalizarOriginal(tmp, destino): [ext, largura,
  altura, bytes]`, `gerarPreset(original, destino, lado, ext)` (tmp + rename + lock),
  `caminhoOriginal/caminhoCache(uuid, preset, ext, publico)`.
- `ImagemVinculoService.php`: `vinculos(Imagem)` (UNION ALL nas tabelas do enum + Mercos),
  `orfas()` (NOT EXISTS encadeado), `amarrar(Imagem, ImagemTipo, int)`, `desamarrar(Imagem)` (SET NULL
  nas FKs sem cascade, antes do delete).
- `ImagemService.php` (reescrever, estático): `enviar(UploadedFile, ImagemTipo, int $codigo): Imagem`,
  `substituir(Imagem, UploadedFile)` (uuid novo, apaga original+cache antigo), `url(Imagem|uuid,
  preset='thumb', ext='webp')`, `gerarPreset`, `inativar`, `restaurar`, `esvaziar(int $dias)`,
  `autoriza(ImagemTipo)`.
- `ImagemController.php` (reescrever; transação no controller): `index` (filtros `tipo`, `ativo|inativo`,
  `orfa`, paginado), `store` (`ImagemStoreRequest`: `imagem|image|mimes:jpeg,png,webp|max:16384`,
  `tipo|in:…`, `codigo|integer`), `show($uuid)` com vínculos, `inativar/ativar`, `esvaziar`.
  `ImagemResource`: `codimagem, uuid, tipo, extensao, largura, altura, bytes, inativo, vinculos`.
- `ImagemPublicaController.php`: `mostrar($a,$b,$uuid,$arquivo)` e `privado($uuid,$arquivo)`.
- `Imagem.php`: fillable/casts novos; acessor `url` → `ImagemService::url($this, 'full', 'jpg')` (resolve
  de graça 9 dos 14 pontos da TASK-64 já no M1).
- Rotas: `api.php` grupo v1 — `GET/POST imagem`, `DELETE imagem/lixeira` (antes de `{uuid}`),
  `GET imagem/{uuid}`, `POST/DELETE imagem/{uuid}/inativo`, `GET imagem/privado/{uuid}/{arquivo}`;
  `web.php` — `GET imagem/{a}/{b}/{uuid}/{arquivo}` com `where` restritivo (`[0-9a-f]{2}`, uuid v4,
  `(thumb|card|detalhe|full)\.(webp|jpg)`) e `withoutMiddleware('web')`. Remover o
  `apiResource('imagem')` e o `SlimAdapter.php`. Conferir com `php artisan route:list --path=imagem`.
- `app/Console/Commands/ImagemMigrarCommand.php` (`imagem:migrar {--dry-run} {--limite=} {--sobras}`):
  chunks de 1000 em `bytes IS NULL`; `arquivo` nulo ou ausente → `inativo = now()` + log; existindo →
  `getimagesize` (mime real), normaliza (teto 2048, EXIF), `rename` pra `original/ab/cd/{uuid}.{ext}`,
  `UPDATE` extensao/largura/altura/bytes. Erro numa linha não aborta. `--sobras` move `^\d+\.jpg$` sem
  linha pra `_sem_registro/`. O uuid nasce no SQL (abaixo), o comando só mexe em arquivo.
- `api/database/imagem_motor.sql` (padrão reaplicável dos outros scripts): colunas `uuid uuid`, `tipo
  varchar(20)`, `extensao varchar(5)`, `largura int`, `altura int`, `bytes bigint`; backfill
  `uuid = gen_random_uuid()` e `tipo` pela FK (produto → marca → hierarquia → usuario; órfã = produto);
  NOT NULL + `uk_tblimagem_uuid` + `idx (tipo, inativo)`; índices nos `codimagem` filhos (a query de
  órfãs depende deles); `tblpranchetacategoria.codimagem bigint` + FK ON DELETE SET NULL. Sem CHECK em
  `tipo` (fonte única é o PHP). `arquivo`/`observacoes` ficam até o M5.
- `config/filesystems.php`: disk `imagem` (`root => env('IMAGENS_PATH', '/opt/www/Arquivos/Imagens')`,
  permissões 0664/0775 como os demais). O `.env` tem `IMAGEM_PATH` morto apontando pra pasta errada:
  não reaproveitar o nome.

## Front

- `components/utils/imagem.js`: `urlImagem(uuid, preset='thumb', ext='webp')` →
  `${new URL(process.env.API_URL).origin}/imagem/${ab}/${cd}/${uuid}/${preset}.${ext}`; uuid nulo →
  placeholder `components/assets/semimagem.svg`. Único lugar do front que monta URL de imagem.
- `components/MgImagem.vue`: `q-img` com `:src="urlImagem(uuid, preset)"`, `crossorigin="anonymous"`
  (sem isso o workbox guarda resposta opaca), erro → placeholder. Props `uuid`, `preset`, `ratio`.
- `components/MgImagemUpload.vue`: botão (arquivo ou câmera) → `q-dialog` com o editor Cropper.js v2
  (`cropper-canvas`/`cropper-image`/`cropper-selection`/`cropper-handle`; botões girar 90°, zoom,
  proporção 1:1 / livre) → `selection.$toCanvas({maxWidth: 2048})` → `canvas.toBlob` (webp; jpeg se o
  navegador não gerar webp; png se a origem tiver alpha) → `FormData{imagem, tipo, codigo}` →
  `api.post('v1/imagem')` → emite `enviada(imagem)`. Props `tipo`, `codigo`, `proporcao`.
  Dep `cropperjs@^2` em `estoque/package.json` e `negocios/package.json` (`components/` não tem
  package.json); `quasar.config.js` dos dois:
  `build.viteVuePluginOptions.template.compilerOptions.isCustomElement = tag => tag.startsWith('cropper-')`.
- Regras da casa: `MgInput`/`MgSelect`, `q-card flat bordered`, botões `flat round size="sm"`, nada de
  `dense`, notify `green-5`/`red-5`, excluir com `$q.dialog({cancel:true}).onOk`.

## Milestones (do core pras beiradas; cada um com onde conferir)

**M0 — infra.** Dockerfile (GD + WebP), `imagem_motor.sql`, disk `imagem`, `IMAGENS_PATH`, symlink
`api/public/imagem` (commitado), `.env`. Confere: `gd_info()["WebP Support"]`, `\d tblimagem`.

**M1a — motor.** Arquivos do backend acima + `imagem:migrar`. Confere: em dev, criar
`/opt/www/Arquivos/Imagens`, rodar `copiar-imagens` (traz os 94 mil de prod), `imagem:migrar --dry-run`
e depois real; `curl -I` numa URL de preset duas vezes (miss gera, hit sai do nginx); acessor
`Imagem->url` já respondendo a URL nova nas telas que usam o model (marca, produto, etiqueta, conferência).

**M1b — biblioteca no estoque** (`estoque/src/pages/imagem/Index.vue`, rota `/imagem`, item no menu):
grade de thumbs com filtros tipo / ativas / lixeira / órfãs e pesquisa por código; card abre vínculos;
ações Excluir, Restaurar; na lixeira, Esvaziar com "há mais de N dias"; upload solto por tipo. Confere:
contar as 9.149 inativas e 8.283 órfãs, restaurar uma, esvaziar um lote pequeno.

**M2 — componente + produto.** `MgImagemUpload`, `MgImagem`, helper. `estoque/src/pages/produto/Detalhe.vue`:
galeria com `card`, Excluir (inativo), ordem, upload pelo componente; `ProdutoResource`/
`ProdutoImagemResource` devolvem `imagem` uuid e ignoram inativas; principal da variação/embalagem cai
pra primeira ativa quando a sua está inativa; `ProdutoController@imagemRemover` sai (vira inativar) e
`imagemOrdem` fica. Confere: subir foto do celular (girar/cortar), ver na lista e no detalhe.

**M3 — marca + hierarquia.** `marca/Detalhe.vue` e `secao-produto/Index.vue` (árvore: imagem em cada
nível) com o componente; `MarcaController`/`MarcaService` devolvem uuid. Confere: logo PNG com
transparência subindo e aparecendo sem fundo preto.

**M4 — prancheta (TASK-208).** `PranchetaPage.vue`: campo de texto da imagem da categoria vira
`MgImagemUpload` (tipo `prancheta`); `PdvPranchetaService` grava/devolve `codimagem`/uuid; migrar as 99
categorias que já têm imagem (as 88 de `negocios/public/categorias` e as 3 da api sobem pelo comando;
as 8 externas ficam como estão até alguém subir); PDV: workbox `runtimeCaching` CacheFirst em `/imagem/`
(`extendGenerateSWOptions`, `cacheName: 'imagem'`, `maxEntries 5000`, `maxAgeSeconds 1 ano`) +
aquecimento no sync (`fetch` dos thumbs das categorias em lotes de 20); acrescentar produto pelo
`MgInputProdutoBarras` (AC #5 da 208). Confere: categoria com foto nova aparecendo offline no PDV.

**M5 — consumidores (TASK-64).** a) backend: `SelectProdutoBarraController` (3 SQLs), `vwprodutobarra`,
`ValeModeloProdutoBarraResource`, `NotaFiscalProdutoBarraResource`, `NegocioProdutoBarraResource`,
`ProdutoService::detalhe`, `EstoqueSaldoConferenciaService`, `EtiquetaResource`, `UsuarioController`,
`GrupoUsuarioController`, `PdvCatalogoService` (campo `imagem` = uuid, join só em imagem ativa),
`PdvPranchetaService`; b) negocios: some `produtoStore.urlImagem` e `SEM_IMAGEM`, 12 usos passam pro
helper; Dexie `version(12)` (produto reindexa; força ressincronização); c) integrações: Woo
`src = ImagemService::url($img, 'full', 'jpg')` (o Woo baixa a URL, precisa ser pública), Mercos lê
`caminhoOriginal` (hoje lê `./public/imagens/` relativo); d) limpeza: `SlimAdapter`, colunas
`arquivo`/`observacoes` (script DDL próprio), `services.mglara.imagens_url`, symlink `public/imagens`.
Critério: `grep -rn "imagens_url\|asset(\"imagens\|urlImagem\|MGLara/public/imagens"` vazio fora do service.

**M6 — go-live.** Ordem: rebuild da imagem Docker da API → `imagem_motor.sql` → deploy do código
→ `imagem:migrar` em prod (rename, minutos) → deploy dos apps → `api.conf` no MGweb:
```nginx
location ^~ /imagem/ {
    try_files $uri /index.php$is_args$args;
    add_header Cache-Control "public, max-age=31536000, immutable";
    add_header Access-Control-Allow-Origin "*";
}
```
→ remover symlink velho. Até o nginx ganhar o header, o miss do Laravel já manda `Cache-Control`; o hit
sai sem header (funciona, só não cacheia 1 ano). Os PDVs ressincronizam pela versão nova do Dexie.

## Verificação

- Backend: `php -l`; `php artisan route:list --path=imagem` (pública sem `web`, privada com
  `auth_or_signed`); `curl -I` miss/hit; `imagem:migrar --dry-run` e real em dev sobre a cópia dos 94 mil;
  `select count(*) from tblimagem where bytes is null` = só as sem arquivo.
- Front: prettier + eslint; telas: biblioteca, produto, marca, hierarquia, prancheta online e offline,
  pesquisa F1 do PDV, quiosque, etiqueta, conferência, vale, nota fiscal.

## Riscos

- Mover os arquivos em prod quebra os consumidores que ainda montam URL antiga: M5 tem que ir no
  mesmo deploy da migração (ordem do M6).
- Dev não tem os arquivos: M1a depende do `copiar-imagens` (rsync de prod) antes.
- `ProdutoController`/`MarcaController` não checam permissão: a foto fica mais restrita que o cadastro
  até eles passarem a checar (fora do escopo).
- Rota pública fora do `throttle:api`; custo de um 404 é uma query.
- Grid frio: primeira abertura de uma listagem gera N presets no PHP; opcional `imagem:aquecer
  --preset=thumb --tipo=produto` depois da migração.

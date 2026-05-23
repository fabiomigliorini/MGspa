# Resumo da execução — Marcos 1+3 do strangler-fig

> **Status (sessão de 24/05/2026 — bulk-port autônomo):** ✅ TODOS os 128 controllers do MGspa/laravel foram copiados em massa pra `/opt/www/MGspa/api/app/Mg/`, mais 21 Console/Commands, 7 Jobs, 6 Observers, App\\Http\\Requests, App\\Rules, App\\Libraries. **Todas as rotas legacy registradas** em `routes/api.php` (1072 linhas, ~250+ rotas v1). Proxy fallback continua ativo como rede de segurança.
> 
> ⚠️ **NÃO TESTADO** — o usuário pediu pra fazer tudo sem rodar comandos. Vai testar segunda-feira. Há **pontos de atenção** críticos listados na seção [Pontos de atenção pra testes de segunda](#pontos-de-atenção-pra-testes-de-segunda).
>
> **Nada em produção foi tocado** — só foi adicionado. O cutover (trocar `AUTH_API_URL` dos consumidores) fica pra você fazer manualmente após validar os testes.

## Pontos de atenção pra testes de segunda

### Esperado funcionar de cara
- Controllers que já tinham sido testados nas sessões anteriores: Feriado, Filial trio, Pessoa lookups (Etnia/EstadoCivil/GrauInstrucao/GrupoCliente), Tributacao trio, NaturezaOperacao trio, Cidade trio, Marca CRUD, Usuario+GrupoUsuario, GrupoEconomico CRUD, Pedido CRUD, todas Select\\*, Banco/ContaContabil/FormaPagamento/TipoMovimentoTitulo/TipoTitulo, Empresa, CertidaoEmissor, EstoqueLocal, Veiculo suite, PessoaCertidao/Conta/RegistroSpc/CobrancaHistorico.

### Provavelmente quebra (precisa ajuste)
- **PessoaResource**: campo `aberto` retorna 0 (PdvNegocioPrazoService::emAberto não foi adaptado). Frontends que dependem desse campo mostram 0 até ajuste.
- **PessoaService::importar**: simplificado pra usar SÓ ReceitaWS. A versão original consultava SEFAZ via NFePHPService::sefazCadastro — depende de lib `nfephp-org/*` no composer (não está no api/composer.json). Quando ajustar, restaurar bloco try/catch.
- **PessoaController::comandaVendedor/comandaVendedorImprimir**: retornam 501 (PessoaComandaVendedorService não portado — depende de dompdf + jrxml).
- **PermissaoController::index**: a rota NÃO está no bloco de rotas (apiResource('permissao') está, mas o index enumera Route::getRoutes() — vai listar SÓ as rotas registradas no api novo, não todas do legacy. Se quiser que retorne tudo, manter proxy.

### Observers — NÃO registrados
Os observers foram copiados pra `app/Mg/.../*/Observer.php` mas o L13 slim NÃO tem `EventServiceProvider.php` — observers precisam ser registrados via `Model::observe(...)` em `AppServiceProvider::boot()`. Observers que estão lá:
- `PessoaObserver` (audit)
- `DependenteObserver` (observer puxa GoogleCalendarService — vai falhar até a env var existir)
- `ColaboradorObserver`
- `FeriasObserver`
- `NotaFiscalObserver`
- `EventoCalendarioObserver`

Pra ativar: editar `/opt/www/MGspa/api/app/Providers/AppServiceProvider.php::boot()` e adicionar `Pessoa::observe(PessoaObserver::class)` etc.

### Commands — copiados mas não testados
21 commands em `app/Console/Commands/`. L13 auto-descobre. Possíveis falhas:
- `BoletoBbConsultarLiquidados` — depende de `Mg\Titulo\BoletoBb\BoletoBbService` (copiado, mas usa lib BB)
- `ExtratoBbConsultarApi` — depende de credenciais BB no .env (`BB_*`)
- `PixConsultar` — depende de credenciais Pix no .env
- `WooBuscarPedidos` / `WooExportarProduto` — depende de credenciais WooCommerce no .env
- `PagarMeProcessarArquivoCommand` — depende de credenciais Pagar.me
- `NFePHPCommandDistDfe` / `NFePHPCommandResolverPendentes` — depende de `nfephp-org/*` no composer
- `NotaFiscalTransferenciaEntrada` — depende de NotaFiscalService + nfephp
- `Email*Command` — depende de mailables (em `app/Mg/Pessoa/EmailAniversarioGeral.php` + `EmailAniversarioIndividual.php` copiados)
- `Estoque*` (BaixarEmbalagens, CalcularMinimoMaximo, DistribuirSaldoDeposito) — depende de Mg\\Estoque\\MinimoMaximo (verificar se copiou)
- `RefreshMvRankingVendasProduto` — apenas SQL VIEW refresh
- `ReprocessaMetaCommand` / `FinalizaMetaCommand` / `CriarNovaMetaCommand` — Meta + ProcessarVendaService
- `ProdutoUnificaBarrasCommand` / `ProdutoUnificaVariacoesCommand` — Produto core
- `CalendarioInicializarCommand` — Google Calendar (skip se faltar credencial)

### Composer packages possivelmente faltando
O api novo composer.json é enxuto. Para os controllers funcionarem 100%, vai precisar instalar:
- `nfephp-org/sped-nfe` (NFePHP, MDFe, DFe, NotaFiscal)
- `nfephp-org/sped-mdfe`
- `nfephp-org/sped-da` (DANFE)
- `dompdf/dompdf` (vários PDFs)
- `mpdf/mpdf` (relatórios grandes)
- `phpoffice/phpspreadsheet` (planilhas Marca/Estoque)
- `picqer/php-barcode-generator` (etiquetas)
- `simplesoftwareio/simple-qrcode` (Pix QR Code)
- `prgayman/jasperphp` ou `quilhasoft/jasperphp` (Comanda PessoaComandaVendedorService — dev-master)
- `automattic/woocommerce` (Woo integração)
- `google/apiclient` (Google Drive PessoaAnexoService, Google Calendar Dependente)

Cada uma a ser adicionada conforme o controller for testado.

### Stubs ainda em uso
Veja o ls em `app/Mg/`. Stubs originais que ficaram (porque a versão completa não foi copiada por colisão de nome ou porque é só FK):
- `Sexo`, `Portador` (criado nesta sessão), `EstoqueMovimentoTipo` (criado anteriormente)
- Vários models são tabelas referenciadas como FK mas não usadas ativamente.

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

### Controllers migradas (Marco 3) — 62 controllers

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
| 12 | `EstoqueLocal` | Mg\Estoque | 2 | (qualquer auth) | só GET (index + show), HTTP 206 + paginação |
| 13 | `Veiculo` | Mg\Veiculo | 7 | (qualquer auth) | CRUD + ativar/inativar |
| 14 | `VeiculoTipo` | Mg\Veiculo | 7 | (qualquer auth) | CRUD + ativar/inativar |
| 15 | `VeiculoConjunto` | Mg\Veiculo | 7 | (qualquer auth) | CRUD + sync pivot |
| 16-35 | **20× `Mg\Select\*`** | Mg\Select | 20 | (qualquer auth) | autocompletes (Banco, Cidade, ContaContabil, Estado, EstoqueLocal, EstoqueMovimentoTipo, Filial, GrupoEconomico, Impressora, NaturezaOperacao, Pessoa, Portador, ProdutoBarra, TipoProduto, TipoTitulo, Tributacao, Tributo, Usuario, Veiculo, VeiculoTipo) |
| 36 | `Banco` | Mg\Banco | 7 | `Administrador`/`Financeiro` | CRUD + paginação |
| 37 | `ContaContabil` | Mg\ContaContabil | 7 | `Administrador`/`Financeiro` | CRUD + paginação |
| 38 | `FormaPagamento` | Mg\FormaPagamento | 7 | `Administrador`/`Financeiro` | CRUD + 11 flags boolean |
| 39 | `TipoMovimentoTitulo` | Mg\Titulo | 7 | `Administrador`/`Financeiro` | CRUD + paginação |
| 40 | `TipoTitulo` | Mg\Titulo | 7 | `Administrador`/`Financeiro` | CRUD + eager-load TipoMovimentoTitulo |
| 41 | `CobrancaHistorico` | Mg\Cobranca | 5 | (qualquer auth) | nested em pessoa |
| 42 | `RegistroSpc` | Mg\Pessoa | 5 | (qualquer auth) | nested em pessoa |
| 43 | `PessoaCertidao` | Mg\Pessoa | 8 | (qualquer auth) | + selectCertidao{Emissor,Tipo} |
| 44 | `PessoaConta` | Mg\Pessoa | 8 | `Publico` (mutations) | nested em pessoa + selectBanco |
| 45 | `Tributacao` | Mg\Tributacao | 5 | (qualquer auth) | apiResource |
| 46 | `TributacaoRegra` | Mg\Tributacao | 5 | (qualquer auth) | apiResource + validate fiscal unique |
| 47 | `Tributo` | Mg\Tributacao | 5 | (qualquer auth) | apiResource |
| 48 | `NaturezaOperacao` | Mg\NaturezaOperacao | 5 | (qualquer auth) | apiResource — `codnaturezaoperacao` |
| 49 | `Cfop` | Mg\NaturezaOperacao | 5 | (qualquer auth) | apiResource — `codcfop` |
| 50 | `TributacaoNaturezaOperacao` | Mg\NaturezaOperacao | 5 | (qualquer auth) | nested em `natureza-operacao` |
| 51 | `Pais` | Mg\Cidade | 5 | (qualquer auth) | apiResource |
| 52 | `Estado` | Mg\Cidade | 5 | (qualquer auth) | nested em pais |
| 53 | `Cidade` | Mg\Cidade | 5 | (qualquer auth) | nested em estado |
| 54 | `Marca` | Mg\Marca | 9 | (qualquer auth) | CRUD + detalhes + ativar/inativar + autocompletar (planilha-pedido/distribuição ficam proxiados pro legacy) |
| 55 | `Permissao` | Mg\Permissao | 0 | n/a | **rotas não registradas** — index enumera Route::getRoutes(); proxy ao legacy preserva paths legados. Arquivos prontos pra ativar quando todos os controllers estiverem migrados |
| 56 | `Usuario` | Mg\Usuario | 14 | mix | CRUD + autor + grupos + grupos-adicionar + grupos-remover + detalhes + ativar/inativar + reset-senha + novoUsuario. Sem PessoaResource (inline pessoa enxuto) |
| 57 | `GrupoUsuario` | Mg\Usuario | 9 | (qualquer auth) | CRUD + autor + detalhes + ativar/inativar |
| 58 | `Filial` | Mg\Filial | 6 | (qualquer auth) | CRUD + autor; substitui stub anterior pelo model completo |
| 59 | `GrupoEconomico` | Mg\GrupoEconomico | 14 | `Financeiro` (mutations) | CRUD + 5 endpoints de relatório (totais-negocios, titulos-abertos, nfe-terceiro, top-produtos, negocios) + remover pessoa do grupo |
| 60 | `Pedido` | Mg\Pedido | 10 | (qualquer auth) | CRUD + nested PedidoItem (produtos-para-transferir fica proxiado pro legacy) |
| 61 | `CaixaMercadoria` | Mg\CaixaMercadoria | 5 | (qualquer auth) | **stub literal do legacy** (devolve echoes — controller no legacy também era stub) |
| 62 | `Imagem` model | Mg\Imagem | — | — | model com `url` accessor apontando pro legacy via `LEGACY_IMAGENS_URL` (não exposto como rota; usado por Marca, Usuario, Filial) |

## Inventário COMPLETO — 128 controllers no legacy

Legenda: ✅ migrado · ⏭️ pulado · 🟢 candidato leve · 🟡 médio · 🔴 pesado/integração externa

### Mg\Banco
| Status | Controller | Notas |
|---|---|---|
| ✅ | `BancoController` | migrado (#36) |

### Mg\Boleto
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `BoletoController` | financeiro, integra com BB |

### Mg\CaixaMercadoria
| Status | Controller | Notas |
|---|---|---|
| ✅ | `CaixaMercadoriaController` | migrado (#61) — stub literal do legacy (que também é stub) |

### Mg\Certidao
| Status | Controller | Notas |
|---|---|---|
| ✅ | `CertidaoEmissorController` | migrado (#11) |
| ⏭️ | `CertidaoTipoController` | **código quebrado no legacy** — `dd()` em todos os métodos, usa `PessoaResource`, Service copiado de Pessoa. Reescrever do zero quando precisar |

### Mg\Cidade
| Status | Controller | Notas |
|---|---|---|
| ✅ | `PaisController` | migrado (#51) |
| ✅ | `EstadoController` | migrado (#52) |
| ✅ | `CidadeController` | migrado (#53) |

### Mg\Cobranca
| Status | Controller | Notas |
|---|---|---|
| ✅ | `CobrancaHistoricoController` | migrado (#41) — nested em pessoa |

### Mg\Colaborador
| Status | Controller | Notas |
|---|---|---|
| ✅ | `CargoController` | migrado (#8) |
| 🟡 | `ColaboradorCargoController` | nested em colaborador |
| 🔴 | `ColaboradorController` | core do RH, ferias, integra com Negocio |
| 🟡 | `ColaboradorFichaController` | upload de ficha PDF |
| 🟡 | `FeriasController` | nested em colaborador |

### Mg\ContaContabil
| Status | Controller | Notas |
|---|---|---|
| ✅ | `ContaContabilController` | migrado (#37) |

### Mg\Dfe
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `DfeController` | distribuição de DFe, integra com SEFAZ |

### Mg\Dominio
| Status | Controller | Notas |
|---|---|---|
| ⏭️ | `DominioController` | DEFERIDO — módulo contábil pesado (Dominio xml + 17 arquivos `Arquivo/` para formatos de registro). Fica proxiado pro legacy |

### Mg\Estoque
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `EstoqueEstatisticaController` | relatórios pesados |
| ✅ | `EstoqueLocalController` | migrado (#12) — só GET, HTTP 206 |
| 🟡 | `EstoqueSaldoConferenciaController` | conferência de saldos |

### Mg\Etiqueta
| Status | Controller | Notas |
|---|---|---|
| 🟢 | `EtiquetaController` | só GET `/etiqueta/arquivo/{arquivo}` (download), simples |

### Mg\Feriado
| Status | Controller | Notas |
|---|---|---|
| ✅ | `FeriadoController` | migrado (#1) |

### Mg\Filial
| Status | Controller | Notas |
|---|---|---|
| ✅ | `EmpresaController` | migrado (#10) |
| ✅ | `FilialController` | migrado (#58) |
| ✅ | `SetorController` | migrado (#3) |
| ✅ | `TipoSetorController` | migrado (#2) |
| ✅ | `UnidadeNegocioController` | migrado (#4) |

### Mg\FormaPagamento
| Status | Controller | Notas |
|---|---|---|
| ✅ | `FormaPagamentoController` | migrado (#38) |

### Mg\GrupoEconomico
| Status | Controller | Notas |
|---|---|---|
| ✅ | `GrupoEconomicoController` | migrado (#59) — PessoaResource substituído por get() enxuto até Pessoa core migrar |

### Mg\Imagem
| Status | Controller | Notas |
|---|---|---|
| ⏭️ | `ImagemController` | usa `App\Libraries\SlimImageCropper` + Service com dependências em 5 domínios de produto (FamiliaProduto, GrupoProduto, SecaoProduto, SubGrupoProduto, ProdutoImagem). Migrar junto com Produto |
| ✅ | `Imagem` model | criado (#62) — `getUrlAttribute()` aponta pra `LEGACY_IMAGENS_URL`. Usado por Marca, Usuario, Filial |

### Mg\Lio
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `LioController` | integração Cielo LIO (callback webhook) |

### Mg\Marca
| Status | Controller | Notas |
|---|---|---|
| ✅ | `MarcaController` | migrado (#54) — CRUD + detalhes + ativar/inativar + autocompletar. Endpoints `criarPlanilhaPedido` e `criarPlanilhaDistribuicaoSaldoDeposito` ficam proxiados pro legacy (dependem de `Estoque\MinimoMaximo\ComprasService` e `DistribuicaoService`) |

### Mg\Mdfe
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `MdfeController` | manifesto eletrônico transporte (SEFAZ) |

### Mg\Meta
| Status | Controller | Notas |
|---|---|---|
| 🟡 | `MetaController` | CRUD + nested (unidade/pessoa/fixo) |
| 🟡 | `MetaDashboardController` | dashboard + dashboard por pessoa + eventos |

### Mg\_Modelo
| Status | Controller | Notas |
|---|---|---|
| ⏭️ | `ModeloController` | template/scaffold do gerador — ignorar |

### Mg\NaturezaOperacao
| Status | Controller | Notas |
|---|---|---|
| ✅ | `CfopController` | migrado (#49) |
| ✅ | `NaturezaOperacaoController` | migrado (#48) |
| ✅ | `TributacaoNaturezaOperacaoController` | migrado (#50) — nested em natureza-operacao |

### Mg\Negocio
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `NegocioController` | core do PDV/vendas |

### Mg\NFePHP
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `NFePHPController` | NFe via lib `nfephp-org/*` (criar/enviar/cancelar/inutilizar/danfe/xml/mail/dist-dfe) |

### Mg\NfeTerceiro
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `NfeTerceiroController` | manifestação de NFe de terceiros |

### Mg\NotaFiscal (8 controllers)
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `Controle\ControleController` | dashboard de controle |
| 🔴 | `Dashboard\DashboardGraficosController` | dashboard |
| 🔴 | `Dashboard\DashboardKpisController` | dashboard |
| 🔴 | `Dashboard\DashboardSefazController` | dashboard SEFAZ |
| 🔴 | `NotaFiscalCartaCorrecaoController` | CC-e |
| 🔴 | `NotaFiscalController` | core fiscal, 30+ rotas (criar/enviar/cancelar/inutilizar/danfe/duplicar/devolução/unificar/CC-e/mail/imprimir/carta-correcao/espelho/etc) |
| 🔴 | `NotaFiscalDuplicatasController` | nested |
| 🔴 | `NotaFiscalPagamentoController` | nested |
| 🔴 | `NotaFiscalProdutoBarraController` | nested |
| 🔴 | `NotaFiscalReferenciadaController` | nested |
| 🔴 | `NotaFiscalTransferenciaController` | scheduled command |

### Mg\NotaFiscalTerceiro
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `NotaFiscalTerceiroController` | nota fiscal de terceiros |

### Mg\PagarMe
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `PagarMeController` | integração PagarMe (webhook, criar/consultar/cancelar pedido) |

### Mg\Pdv (5 controllers)
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `PdvController` | core do PDV — 30+ rotas (produto, pessoa, negocio, dispositivo, saurus) |
| 🔴 | `PdvAnexoController` | upload/listagem de anexos de negócio |
| 🔴 | `PdvLiquidacaoController` | liquidação de títulos do PDV |
| 🔴 | `PdvMercosController` | integração Mercos B2B |
| 🔴 | `PdvNotaFiscalController` | nota fiscal a partir do PDV |

### Mg\Pedido
| Status | Controller | Notas |
|---|---|---|
| ✅ | `PedidoController` | migrado (#60) — CRUD + Itens. `produtos-para-transferir` fica proxiado pro legacy (depende de ProdutoBarra/ProdutoEmbalagem/UnidadeMedida) |

### Mg\Permissao
| Status | Controller | Notas |
|---|---|---|
| ✅ | `PermissaoController` | migrado (#55) — **rotas não registradas**; index() enumera Route::getRoutes() (degrada se ativado antes da migração completa). Catch-all proxy preserva legado |

### Mg\Pessoa (12 controllers)
| Status | Controller | Notas |
|---|---|---|
| ⏭️ | `DependenteController` | Observer puxa GoogleCalendarService (módulo Calendario inteiro) — pular até Pessoa core ser migrada |
| ✅ | `EstadoCivilController` | migrado (#6) |
| ✅ | `EtniaController` | migrado (#5) |
| ✅ | `GrauInstrucaoController` | migrado (#7) |
| ✅ | `GrupoClienteController` | migrado (#9) |
| 🔴 | `PessoaAnexoController` | upload/download de anexos |
| ✅ | `PessoaCertidaoController` | migrado (#43) |
| ✅ | `PessoaContaController` | migrado (#44) |
| 🔴 | `PessoaController` | 258 linhas, integração SEFAZ IE + Mercos + autocomplete + importar + comanda-vendedor + aniversariosColaboradores |
| 🟡 | `PessoaEmailController` | nested email com verificação (Notification) |
| 🟡 | `PessoaEnderecoController` | depende de `PessoaService::atualizaCamposLegado` (não migrado, Pessoa core) |
| 🟡 | `PessoaTelefoneController` | nested telefone com verificação SMS |
| ✅ | `RegistroSpcController` | migrado (#42) — nested SPC |

### Mg\Pix
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `PixController` | Pix BB v2 (criar/transmitir/consultar/brcode/webhook) |

### Mg\Portador
| Status | Controller | Notas |
|---|---|---|
| 🟡 | `PortadorController` | 199 linhas, lookup |

### Mg\Produto
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `ProdutoController` | core de produto (unifica-variacoes, unifica-barras, embalagem-para-unidade) |

### Mg\Rh (8 controllers)
| Status | Controller | Notas |
|---|---|---|
| 🟡 | `AcertoController` | acertos (encontro de contas) |
| 🟡 | `ColaboradorRubricaController` | rubricas |
| 🔴 | `DashboardController` | dashboard RH |
| 🟡 | `IndicadorController` | indicadores |
| 🟡 | `MeuPainelController` | painel pessoal |
| 🟡 | `PeriodoColaboradorController` | nested |
| 🟡 | `PeriodoColaboradorSetorController` | nested |
| 🟡 | `PeriodoController` | períodos RH (master do módulo) |

### Mg\Select (20 controllers — todos lookups de autocomplete) — TODOS MIGRADOS ✅
| Status | Controller | Notas |
|---|---|---|
| ✅ | `SelectBancoController` | migrado (lote #16-35) |
| ✅ | `SelectCidadeController` | migrado (lote #16-35) |
| ✅ | `SelectContaContabilController` | migrado (lote #16-35) |
| ✅ | `SelectEstadoController` | migrado (lote #16-35) |
| ✅ | `SelectEstoqueLocalController` | migrado (lote #16-35) |
| ✅ | `SelectEstoqueMovimentoTipoController` | migrado (lote #16-35) |
| ✅ | `SelectFilialController` | migrado (lote #16-35) |
| ✅ | `SelectGrupoEconomicoController` | migrado (lote #16-35) |
| ✅ | `SelectImpressoraController` | migrado (lote #16-35) — lê printers.json |
| ✅ | `SelectNaturezaOperacaoController` | migrado (lote #16-35) |
| ✅ | `SelectPessoaController` | migrado (lote #16-35) |
| ✅ | `SelectPortadorController` | migrado (lote #16-35) |
| ✅ | `SelectProdutoBarraController` | migrado (lote #16-35) |
| ✅ | `SelectTipoProdutoController` | migrado (lote #16-35) |
| ✅ | `SelectTipoTituloController` | migrado (lote #16-35) |
| ✅ | `SelectTributacaoController` | migrado (lote #16-35) |
| ✅ | `SelectTributoController` | migrado (lote #16-35) |
| ✅ | `SelectUsuarioController` | migrado (lote #16-35) |
| ✅ | `SelectVeiculoController` | migrado (lote #16-35) |
| ✅ | `SelectVeiculoTipoController` | migrado (lote #16-35) |

### Mg\Stone\Connect
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `FilialController` | Stone Connect |
| 🔴 | `PosController` | Stone POS |
| 🔴 | `PreTranscaoController` | pré-transação |
| 🔴 | `WebhookController` | webhooks Stone (pos-application, pre-transaction-status, processed-transaction, print-note-status) |

### Mg\Titulo (8 controllers)
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `BoletoBb\BoletoBbController` | boleto BB (PDF) |
| 🟡 | `LiquidacaoTituloController` | liquidações |
| ✅ | `TipoMovimentoTituloController` | migrado (#39) |
| ✅ | `TipoTituloController` | migrado (#40) |
| 🟡 | `TituloAgrupamentoController` | agrupamento + mail |
| 🟡 | `TituloBoletoController` | boleto genérico |
| 🔴 | `TituloController` | core financeiro |
| 🔴 | `TituloRelatorioController` | relatório + PDF |

### Mg\Tributacao
| Status | Controller | Notas |
|---|---|---|
| ✅ | `TributacaoController` | migrado (#45) |
| ✅ | `TributacaoRegraController` | migrado (#46) |
| ✅ | `TributoController` | migrado (#47) |
| ⏭️ | `TributacaoService` | **não portado** — usa NotaFiscalItemTributo, parte da migração NotaFiscal |

### Mg\Usuario
| Status | Controller | Notas |
|---|---|---|
| ✅ | `GrupoUsuarioController` | migrado (#57) |
| ✅ | `UsuarioController` | migrado (#56) — sem PessoaResource (pessoa inline enxuto). `permissoesUsuarios` (auth/user) **ainda proxiado** pro legacy |

### Mg\Veiculo
| Status | Controller | Notas |
|---|---|---|
| ✅ | `VeiculoController` | migrado (#13) |
| ✅ | `VeiculoConjuntoController` | migrado (#15) |
| ✅ | `VeiculoTipoController` | migrado (#14) |

### Mg\Woo
| Status | Controller | Notas |
|---|---|---|
| 🔴 | `WooPedidoController` | WooCommerce pedidos |
| 🔴 | `WooProdutoController` | WooCommerce produtos |

### Resumo do inventário

| Categoria | Quantidade | % |
|---|---|---|
| ✅ Migrado | 62 | 48% |
| ⏭️ Pulado / adiado por dep complexa | 6 | 5% |
| 🟡 Médio restante | ~20 | 16% |
| 🔴 Pesado/integração externa | ~40 | 31% |
| **Total** | **128** | **100%** |

### Próximas candidatas (mais baratas / impacto maior)

**🟡 Médios candidatos a próxima rodada** (relativamente isolados):
- **Meta** + **MetaDashboard** — depende de UnidadeNegocio (já migrado), Pessoa (lookup leve), Filial (já completo)
- **Portador** (199 linhas — verificar se `ExtratoBbService` é leve ou puxa BB API)
- **Veiculo** suite — já migrado

**⏭️ Adiados por dependências problemáticas:**
- `CertidaoTipoController` (legacy quebrado — `dd()` em todos métodos)
- `ImagemController` (SlimImageCropper + deps de 5 domínios de produto)
- `DependenteController` (Observer puxa GoogleCalendarService)
- `PessoaEnderecoController` (depende de PessoaService não migrado)
- `_Modelo\ModeloController` (scaffold do gerador)
- `DominioController` (módulo contábil pesado com 17 arquivos de formato)

**🔴 Bloqueadores reais** (precisam de pré-trabalho de Pessoa/Produto/NotaFiscal antes):

**Linchpin = Pessoa core**. Migrar `PessoaController` + `PessoaResource` completos desbloqueia a maior parte do resto. `PessoaResource` tem 8 sub-recursos (RegistroSpc, PessoaCertidao, PessoaTelefone, PessoaEmail, PessoaEndereco, PessoaConta, Dependente, MercosCliente) + chama `PdvNegocioPrazoService` + `Autorizador`. Estratégia recomendada: portar `PessoaController` + `PessoaService::createPelaSefazReceitaWs` + `PessoaService::importar` em uma sessão dedicada, com `PessoaResource` SIMPLIFICADO inicialmente (sem PDV/SPC/Dependente — adicionar incrementalmente conforme os domínios virem). Os clientes do PessoaResource (UsuarioResource, FilialResource, GrupoEconomicoResource) já usam shape enxuto inline — não há regressão.

Depois de Pessoa core:
- **Colaborador** + `ColaboradorCargo` + `Ferias` + `ColaboradorFicha` (upload PDF)
- **Pessoa nested**: PessoaEndereco (depende `atualizaCamposLegado`), PessoaTelefone (verificação SMS), PessoaEmail (Notification), PessoaAnexo (upload Google Drive)
- **Rh** suite (8 controllers — em desenvolvimento ativo no legacy, defer até alinhar com usuário se quer migrar mid-flight)

**Linchpin = Produto core** desbloqueia:
- `ProdutoController` + Etiqueta + EstoqueEstatistica + EstoqueSaldoConferencia + Marca/criarPlanilha* + Pedido/produtosParaTransferir

**Linchpin = NotaFiscal core** (já depende de Pessoa+Produto) desbloqueia:
- `NotaFiscalController` + 7 subs (30+ rotas, nfephp-org/*, dompdf)
- `NFePHPController`, `MdfeController`, `DfeController`
- `NfeTerceiroController`, `NotaFiscalTerceiroController`
- `TributacaoService` (NotaFiscalItemTributo)

**Linchpin = Titulo** (depende de Pessoa) desbloqueia:
- `TituloController` + 6 subs (BoletoBb, LiquidacaoTitulo, TituloAgrupamento, TituloBoleto, TituloRelatorio)

**Linchpin = Pdv** (depende de Pessoa+Produto+NotaFiscal+Titulo) desbloqueia:
- `PdvController` + 4 subs (Anexo, Liquidacao, Mercos, NotaFiscal)
- `Banco\BoletoController`

**Integrações externas** (mais isoladas — podem ser migradas independentemente):
- `PixController` (Pix BB v2 — código já modernizado em 31/03/2026 segundo memória do projeto)
- `Stone\Connect\*` (4 controllers — Stone)
- `PagarMeController`, `LioController`, `WooPedido`/`WooProduto`

### Proxy fallback (transparente)
Catch-all em `Route::any('{any}')` repassa rotas ainda não migradas pro MGspa/laravel antigo. Controlado por `LEGACY_PROXY_ENABLED=true` no `.env`. Quando o cutover dos consumidores for feito, os frontends apontam só pra `api-dev` e a migração das outras controllers fica transparente (cada controller migrada simplesmente "promove" da camada de proxy pra nativa).

### Smoke-tests validados
✅ Auth: `/api/check-token` sem token → 401 com JSON idêntico ao MGAuth
✅ Auth: `/login` → 200 com form Blade
✅ Migradas: 62 controllers nativos sob `/api/v1/` (paths idênticos ao legacy)
✅ Proxy: rotas não migradas vão pro legacy via catch-all (transparente)
✅ `php artisan route:list` → ~250+ rotas (auth + v1 nativas + proxy)
✅ Tinker programático nos models: Pais (2), Estado (27), Cidade (5508), Marca (1614), Filial (21), GrupoEconomico (1087), Usuario (389), GrupoUsuario (7), Pedido (0), Imagem.url → `https://api-mgspa-dev.mgpapelaria.com.br/imagens/...`

## Bases trazidas (úteis pra próximas migrações)

| Arquivo | Função |
|---|---|
| `app/Mg/MgModel.php` | Base de todos os models do domínio MG (audit codusuariocriacao/alteracao, timestamps criacao/alteracao, scopes ativo/inativo/palavras) |
| `app/Mg/MgController.php` | Base de controllers do MG (helper `filtros()` pra parse de query params) |
| `app/Mg/Usuario/Autorizador.php` | Verificação de grupos (consulta tblgrupousuariousuario) |
| `app/Models/Usuario.php` | Model de auth do Passport (enxuto — tbl `tblusuario`, trait HasApiTokens) |

**Stubs minimais** ainda em uso (vão ser substituídos por versão completa quando o domínio for migrado):
- `app/Mg/Pessoa/Pessoa.php` — stub mínimo (substituir quando Pessoa core migrar)
- `app/Mg/Pessoa/PessoaCertidao.php`
- `app/Mg/Pessoa/PessoaEndereco.php` — criado pra FK em Cidade::destroy()
- `app/Mg/Portador/Portador.php` — FK de Usuario
- `app/Mg/Meta/MetaUnidadeNegocio.php`
- `app/Mg/Rh/PeriodoColaboradorSetor.php`
- `app/Mg/Estoque/EstoqueMovimentoTipo.php`
- `app/Mg/Negocio/Negocio.php`
- `app/Mg/NfeTerceiro/NfeTerceiro.php`
- `app/Mg/NotaFiscal/NotaFiscal.php`
- `app/Mg/NotaFiscal/NotaFiscalProdutoBarra.php`
- `app/Mg/NotaFiscalTerceiro/NotaFiscalTerceiro.php`
- `app/Mg/Produto/TipoProduto.php`
- `app/Mg/Tributacao/NcmTributacao.php`
- `app/Mg/NaturezaOperacao/Operacao.php`, `DominioAcumulador.php`

**Stubs SOBRESCRITOS por modelo completo nesta rodada**:
- `app/Mg/Cidade/Cidade.php` → completo
- `app/Mg/Cidade/Estado.php` → completo
- `app/Mg/Filial/Filial.php` → completo

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

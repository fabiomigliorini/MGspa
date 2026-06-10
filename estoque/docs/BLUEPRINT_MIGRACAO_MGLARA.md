# Blueprint — Reformulação MGLara → estoque (Quasar)

> Documento de planejamento da migração do antigo sistema **MGLara** (Laravel/Blade, MVC monolítico)
> para o novo app **estoque** (Quasar / Vue 3 + Pinia, consumindo a API).
>
> Gerado em 2026-06-09. Escopo acordado: **portar todas as funcionalidades existentes no MGLara**
> (estoque, imagens e demais), com a stack e a UX reformuladas.

---

## 1. Visão geral das stacks

| | MGLara (antigo) | estoque (novo) |
|---|---|---|
| Camada | Laravel 5.x, Blade server-side | Quasar v2 / Vue 3 SPA |
| UI | Bootstrap 3 + jQuery | Quasar components + utility classes |
| Estado | sessão PHP | Pinia stores |
| Auth | sessão + SSO/OAuth | OAuth/OIDC unificado (igual contas/notas/pessoas) |
| Backend | controllers próprios | API (api-dev) |
| Navegação | navbar dropdown (Comercial / Estoque / Financeiro / Admin) | left drawer (Operações / Cadastros) |

**Princípio da reformulação:** telas CRUD repetitivas viram listagens com filtros em *drawer* +
formulário/detalhe; hierarquias que eram várias telas separadas viram **uma árvore**; abas de "show"
viram um **Detalhe único** com abas Quasar.

---

## 2. Inventário completo de telas do MGLara

Legenda de status: ✅ feito · 🚧 parcial · ❌ a criar · 🔗 pertence a outro app MGspa · ➖ avaliar/descontinuar

### 2.1 Estoque — núcleo

| Rota / view MGLara | Função | Status estoque |
|---|---|---|
| `produto/index` | Listagem e busca de produtos | ✅ `produto/Index.vue` |
| `produto/create · edit · form` | Cadastro/edição de produto | ✅ `produto/Form.vue` |
| `produto/show` (abas) | Ficha completa do produto | ✅ `produto/Detalhe.vue` — 8 abas (Detalhes, Estoque, NCM, Negócios, Notas, Compras, Mercos, Woo) + CRUD barras/embalagens/variações + imagens |
| `produto/quiosque` · `consulta/{barras}` | Consulta de preço (balcão/quiosque) — rede confiável sem login | 🔗 **negocios** (`QuiosquePage.vue`) — só linkar |
| `produto/min-max-editar` | Editar estoque mínimo/máximo | ❌ falta backend (`EditarMinMax`/`SalvarMinMax` só no MGLara) |
| `produto/converter-embalagem` | Converter embalagem para unidade | ✅ **feito** (Detalhe → embalagem; `v1/produto/embalagem-para-unidade`) |
| `produto/transferir-variacao` | Transferir/unificar variação | ✅ **feito** via unificar variação (mesmo produto); transferir p/ outro produto ainda ❌ |
| `produto/unifica-variacao` | Unificar variações duplicadas | ✅ **feito** (Detalhe → variação; `v1/produto/unifica-variacoes`) |
| `produto/unifica-barras` | Unificar códigos de barra duplicados | ✅ **feito** (Detalhe → barra; `v1/produto/unifica-barras`) |
| `produto/cobre-estoque-negativo` | Cobrir saldo negativo | ❌ falta backend (só no MGLara) |
| `produto-historico-preco` · `/relatorio` | Histórico de alterações de preço | ❌ a criar |
| `produto-barra` | Códigos de barra do produto | 🚧 sub-form do produto |
| `produto-embalagem` | Embalagens do produto | 🚧 sub-form do produto |
| `produto-variacao` · `/descontinuar` | Variações do produto | 🚧 sub-form do produto |
| `estoque-local-produto-variacao` | Localização física (endereço) por variação | ❌ a criar (sub-form) |
| `estoque-saldo/index` | Grid de saldos por local/filial | ✅ `estoque-saldo/Index.vue` |
| `estoque-saldo/resumo-produto` | Resumo de saldo de um produto | 🚧 dentro do Detalhe |
| `estoque-saldo/{id}/zera` | Zerar saldo | ❌ ação a criar |
| `estoque-saldo-conferencia` | Conferência/inventário de saldos | ✅ `conferencia/Setup.vue` + `Listagem.vue` |
| `estoque-movimento/create · edit · show` | Lançamento manual de movimento de estoque | ❌ a criar |
| `estoque-mes` | Fechamento de estoque por mês | ➖ avaliar (estava oculto no menu) |
| — (recurso novo) | Etiquetas / código de barras | ✅ `etiqueta/Index.vue` |

#### Relatórios de estoque (`estoque-saldo/relatorio-*`)
| Relatório | Função | Status |
|---|---|---|
| `relatorio-analise` | Análise de saldos de estoque | 🚧 via `relatorios/Index.vue` |
| `relatorio-comparativo-vendas` | Vendas filial × saldo depósito | 🚧 idem |
| `relatorio-fisico-fiscal` | Saldo físico × fiscal | 🚧 idem |
| `relatorio-transferencias` | Transferências de estoque | 🚧 idem |

> Cada relatório tinha **tela de filtro** + **tela de resultado**. Reformular: filtro no drawer, resultado na página.

### 2.2 Classificação de produtos (eram 5+ CRUDs separados)

| Rota MGLara | Função | Status |
|---|---|---|
| `secao-produto` · `/inativar` | Seção (nível 1 da hierarquia) | ✅ consolidado |
| `familia-produto` · `/inativar` · `/listagem-json` | Família (nível 2) | ✅ consolidado |
| `grupo-produto` · `/inativar` · `/busca-codproduto` | Grupo (nível 3) | ✅ consolidado |
| `sub-grupo-produto` · `/inativar` · `/busca-codproduto` | Subgrupo (nível 4) | ✅ consolidado |
| `produto-variacao` | Variação | 🚧 sub-form |

> **Reformulação-chave:** as 4 telas de hierarquia viraram **uma árvore** em `secao-produto/Index.vue`
> ("Hierarquia de Produtos"). Validar que inativação e busca-por-produto foram cobertas.

### 2.3 Cadastros auxiliares

| Rota MGLara | Função | Status |
|---|---|---|
| `marca` · `/inativar` · `/busca-codproduto` | Marcas | ✅ `marca/Index.vue` + `Detalhe.vue` |
| `tipo-produto` | Tipos de produto | ✅ `tipo-produto/Index.vue` |
| `unidade-medida` | Unidades de medida | ✅ `unidade-medida/Index.vue` |
| `ncm` · `/listagem-json` | NCM | ✅ `ncm/Index.vue` |
| `cest` · `/listagem-json` | CEST (fiscal) | ➖ não criar tela — hoje é **campo** (produto/notas) |
| `tributacao` | Tributações | 🔗 **notas** (`TributacaoCadastro*`, `TributacaoNaturezaOperacao*`) |
| `gerador-codigo/model/{tabela}` | Gerador de código/EAN | ❌ utilitário a avaliar |

### 2.4 Imagens (em escopo)

| Rota MGLara | Função | Status |
|---|---|---|
| `imagem/index` | Galeria/biblioteca de imagens | ❌ a criar |
| `imagem/produto` · `produtostore` · `produtoDelete` | Vincular/upload/remover imagens do produto | ❌ a criar (aba do produto) |
| `imagem/edit` · `show` | Editar/ver imagem | ❌ a criar |
| `imagem/lixeira` · `esvaziar-lixeira` | Lixeira de imagens | ❌ a criar |
| `imagem/inativar` | Inativar imagem | ❌ a criar |

### 2.5 Integrações externas (eram acionadas via tela/rota)

| Rota MGLara | Função | Status sugerido |
|---|---|---|
| `dominio/estoque` | Exportação para sistema contábil Domínio | 🔗 **notas** (`DominioPage.vue`) — não recriar |
| `mercos/produto/{id}/exporta · atualiza` | Integração Mercos — **um produto** | ❌ aba/ação no produto (única parte do estoque) |
| `mercos/pedido · cliente` (import/export) | Pedidos/clientes Mercos | 🔗 **negocios** (`stores/mercos.js`, `MercosPedido.vue`) |
| WooCommerce (abas `show-woo*`) | Integração e-commerce | 🔗 **negocios** (`WooPage`/`WooPainelPage`); no estoque, no máximo aba de leitura |

### 2.6 Domínios JÁ existentes em outros apps MGspa — **não recriar no estoque**

> ⚠️ **Verificado por varredura nos apps** (`contas`, `notas`, `negocios`, `pessoas`) em 2026-06-09.
> Recomendação: **integrar via link** para o app dono, não reconstruir.

| Rota / função MGLara | App dono | Tela existente (confirmada) |
|---|---|---|
| `produto/quiosque` · `consulta/{barras}` (consulta de preço) | **negocios** | ✅ `QuiosquePage.vue` (usa `produtoStore`, mostra variações + imagem) |
| `tributacao` | **notas** | ✅ `TributacaoCadastro*` + `TributacaoNaturezaOperacao*` |
| `dominio/estoque` (export contábil) | **notas** | ✅ `DominioPage.vue` |
| `cidade` · `pais` · `estado` | **notas** | ✅ `CidadeListPage.vue` |
| WooCommerce (export/painel) | **negocios** | ✅ `WooPage.vue` + `WooPainelPage.vue` |
| Mercos — pedidos/clientes | **negocios** | ✅ `stores/mercos.js`, `components/offline/MercosPedido.vue` |
| `nota-fiscal` · `nota-fiscal-produto-barra` · `gera-transferencias` | **notas** | ✅ `NotaFiscalFormPage` / `ViewPage` / etc. |
| `negocio` · `negocio-produto-barra` | **negocios** | ✅ `ListagemPage` / `negocio/:codnegocio` |
| `cheque` · `cheque-repasse` · `cheque-motivo-devolucao` | **contas** | ✅ `cheque`, `chequeRepasse`, `chequeMotivoDevolucao` |
| `portador` · `forma-pagamento` | **contas** | ✅ `portador`, `forma-pagamento` (+ banco, pix, boleto, titulo, liquidacao, conta-contabil) |
| `pessoa` · `cobranca` · `spc` · `grupo-cliente` | **pessoas** | ✅ `pessoa`, `grupo-cliente` |
| `usuario` · `grupo-usuario` | **pessoas** | ✅ `usuarios`, `grupo-usuario` |
| `permissao` | **permissoes** | ✅ app dedicado (RBAC) |
| `meta` | **pessoas** | ✅ `meta` / `rh` (já redirecionava no MGLara) |
| `dashboard/home` | **estoque** | ✅ `IndexPage.vue` |

#### Lacunas reais (não achadas em nenhum app — decidir destino)
| Função MGLara | Observação |
|---|---|
| `caixa` (Totais de Caixa) | Não há tela dedicada; negocios tem comanda/liquidação. Provável relatório → definir se vai pra `negocios`/`contas`. |
| `vale-compra` · `vale-compra-modelo` | Não migrado para nenhum app Quasar. Comercial — destino provável `contas`/`negocios`, **não** estoque. |
| `cest` | Não tem CRUD dedicado em lugar nenhum; hoje é **campo** (no produto e em notas). Não criar tela — manter como campo do NCM/produto. |

---

## 3. Ficha do produto (`produto/show`) — abas do MGLara (decidir aba a aba)

| Aba (view) | Função |
|---|---|
| `show-estoque` | Saldos por local/filial |
| `show-embalagens` | Embalagens cadastradas |
| `show-variacoes` | Variações (cor/tamanho/etc.) |
| `show-ncm` | Dados fiscais / NCM |
| `show-compras` | Histórico de compras |
| `show-notasfiscais` | Notas fiscais que contêm o produto |
| `show-negocios` | Negócios/vendas do produto |
| `show-ultima-compra-venda` | Última compra e última venda |
| `show-imagens` | Galeria de imagens |
| `show-mercos` / `show-mercos-item` | Integração Mercos |
| `show-woo` / `show-woo-cabecalho` | Integração WooCommerce |

> **Auditoria 2026-06-09:** `produto/Detalhe.vue` já implementa 8 abas — Detalhes, Estoque, NCM,
> Negócios, Notas Fiscais, Compras, Mercos, WooCommerce — com CRUD de variações/barras/embalagens,
> imagens (add/remover/reordenar) e export Mercos/Woo. "Última compra/venda" aparece diluída em
> Compras/Negócios (sem destaque próprio). Decisão pendente apenas se vale um card "última
> compra/venda" dedicado.

---

## 4. Plano de execução por complexidade (do simples ao complexo)

> Atualizado após auditoria de 2026-06-09. Achado-chave: o `produto/Detalhe.vue` já está
> essencialmente completo, e vários endpoints já estão portados no `api-dev`. Logo o "simples"
> agora é **UI sobre endpoint existente**; o "complexo" é o que **ainda exige backend novo (PHP)**.

Legenda: 🟩 simples · 🟨 média · 🟥 complexa · ⚙️ = depende de endpoint no `api-dev`.

### ✅ Feito nesta sessão (frontend sobre endpoint existente) 🟩
- **Converter embalagem para unidade** — Detalhe → embalagem → `swap_horiz`; `POST v1/produto/embalagem-para-unidade`.
- **Unificar variação** — Detalhe → variação → `call_merge`; `POST v1/produto/unifica-variacoes` (mesmo produto).
- **Unificar código de barras** — Detalhe → barra → `call_merge`; `POST v1/produto/unifica-barras` (filtra destino por mesma variação + embalagem, regra do backend).

### Fase A — Quick wins de validação 🟩 (sem código novo)
- `relatorios/Index.vue`: confirmar cobertura dos 4 relatórios (análise, físico×fiscal, comparativo vendas, transferências). Backend tem `comparativo-vendas`, `fisico-fiscal`, `transferencias`; **conferir "análise"**.
- `secao-produto`: árvore inativa nós e filtra; **falta** "listar produtos do nó".

### Fase B — Ações que exigem BACKEND novo no api-dev ⚙️ 🟨🟥
> Ordem dentro da fase: do menor esforço de backend ao maior.
1. **Editar mín/máx** ⚙️ — portar `EditarMinMax`/`SalvarMinMax` do MGLara → `Mg\Produto\ProdutoController`; UI = dialog. 🟨
2. **Zerar saldo** ⚙️ — portar `EstoqueController@zeraSaldo`; UI = ação em estoque-saldo. 🟨
3. **Cobrir estoque negativo** ⚙️ — portar `ProdutoController@cobreEstoqueNegativo`. 🟨
4. **Histórico de preços** ⚙️ — model `ProdutoHistoricoPreco` já existe; criar controller+rota; UI = aba/tela read-only (+ PDF). 🟨
5. **Transferir variação p/ OUTRO produto** ⚙️ — `unificaVariacoes` exige mesmo produto; precisa endpoint dedicado. 🟨

### Fase C — Domínio pesado / múltiplas telas / upload 🟥 ⚙️
6. **Estoque movimento** (listagem + lançamento manual) — model `EstoqueMovimento` existe; falta controller/rotas CRUD + regras de saldo/custo médio. **Núcleo.**
7. **Biblioteca de imagens** (index global + lixeira/esvaziar) — upload no produto já existe (`v1/imagem`); falta a tela de biblioteca e lixeira.
8. **`estoque-mes` (fechamento mensal)** — **decisão antes de codar:** manter ou descontinuar?

### Fora de escopo do app estoque — integrar via LINK para o app dono
| Função | App dono |
|---|---|
| Consulta de preço / quiosque | `negocios` |
| Tributação, Domínio, Cidade/localidades, Notas fiscais | `notas` |
| Negócios/vendas, WooCommerce, Mercos (pedidos), Comanda, PDV, Liquidação | `negocios` |
| Cheques, Portador, Forma de pagamento, Banco, Pix, Boleto, Título, Liquidação título | `contas` |
| Pessoas, Grupo de cliente, Usuários, Grupo de usuários, Metas/RH | `pessoas` |
| Permissões (RBAC) | `permissoes` |

**Lacunas reais a destinar:** Caixa (Totais) e Vale-compra/Modelos — não estão em nenhum app; comercial,
provavelmente `negocios`/`contas`, **não** estoque.

### Endpoints já portados vs faltando no `api-dev` (referência)
| Já existe ✅ | Falta portar ❌ (só no MGLara) |
|---|---|
| `v1/produto/unifica-variacoes` | `EstoqueController@zeraSaldo` |
| `v1/produto/unifica-barras` | `ProdutoController@cobreEstoqueNegativo` |
| `v1/produto/embalagem-para-unidade` | `ProdutoController@EditarMinMax`/`SalvarMinMax` |
| `v1/estoque-saldo-conferencia/zerar-produto` | `EstoqueMovimentoController` (CRUD) |
| `v1/imagem` (upload/CRUD) | `ProdutoHistoricoPrecoController` (model existe) |
| relatórios comparativo-vendas / físico-fiscal / transferências | — |

---

## 5. Estado atual do app estoque (jun/2026)

**Telas já implementadas** (`src/pages` + `router/routes.js`):
- `home` (IndexPage)
- `produto` (Index / Form / Detalhe)
- `marca` (Index / Detalhe)
- `secao-produto` (Hierarquia de Produtos — árvore)
- `unidade-medida`, `tipo-produto`, `ncm`
- `estoque-saldo`
- `conferencia` (Setup / Listagem)
- `etiqueta`, `relatorios`

**Navegação** (`MainLayout.vue`): grupos **Operações** (Saldos, Conferência, Etiquetas, Relatórios)
e **Cadastros** (Produtos, Marcas, Hierarquia, Unidades, Tipos, NCM).

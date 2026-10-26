---
id: TASK-258
title: >-
  Repor a loja exige olhar o PDF de transferências e digitar requisição por
  requisição
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-25 22:55'
updated_date: '2026-10-26 03:11'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 267000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Para repor uma loja, alguém olha o PDF Sugestão de Transferências e digita no formulário uma requisição por vez. O gerador (botão no painel de requisições) monta a reposição de uma loja marca por marca, ou em todas as marcas: mostra o que está zerado ou abaixo do mínimo, o estoque/mín/máx das outras lojas e o que já foi pedido, arredonda pelo múltiplo de transferência, guarda um rascunho que sobrevive a fechar a tela e no fim gera uma requisição de Reposição por loja que atende, com prioridade. Plano e decisões (24-25/10/2026) nas notas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Sai o Sugerir abaixo do mínimo do formulário da requisição (o PDF do estoque fica)
- [x] #2 O local de estoque tem a flag Defeito e o local marcado fica fora da requisição
- [x] #3 A requisição tem prioridade (Baixa/Normal/Alta/Urgente), trocada pelo botão com motivo e histórico
- [x] #4 O F1 e o gerador usam o mesmo motor de busca e o F1 passa a achar pela referência
- [x] #5 O gerador mostra loja, marcas, produtos e variações com contadores, furo em R$ e filtros (urgência, ABC, ordem)
- [x] #6 As quantidades arredondam pelo múltiplo de transferência e ficam num rascunho persistente
- [x] #7 A revisão gera uma requisição de Reposição por loja que atende, com a prioridade pré-selecionada
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
## Decisões (Fábio, 24–25/10/2026)

**Onde e quem**
- Botão no **painel de requisições do negocios**, ao lado do "Todas". **Qualquer usuário do PDV**,
  sem permissão nova. A loja padrão é a do dispositivo (`sNegocio.padrao.codestoquelocal`).
- Lojas, tanto a que pede quanto as de onde pedir, são as das abas do painel: `db.estoqueLocal`
  ativas, com `controlaestoque !== false` e **sem a flag Defeito**. O **Depósito** entra nos dois
  papéis (pedir é raro: quando foi mercadoria demais para uma loja).

**Regras**
- Loja = `codestoquelocal`. Mín/máx = `tblestoquelocalprodutovariacao`. Saldo = `tblestoquesaldo`
  com `fiscal = false`. A marca é **só `p.codmarca`**. Produto e variação inativos ficam fora.
- **Trabalha** = `coalesce(máx,0) > 1`. **Abaixo** = trabalha e `coalesce(saldo,0) ≤ coalesce(mín,0)`.
  **Zerado** = trabalha e `coalesce(saldo,0) ≤ 0`. São as regras do PDF; o saldo negativo entra como
  está. O saldo das outras lojas não entra nos contadores.
- **Faltam** = soma de `máx − saldo` das variações abaixo, na unidade do produto. **Furo** = soma de
  `(máx − saldo) × p.preco` das variações abaixo, em R$ a preço de venda.

**Filtros (valem para marcas e produtos; ficam no persist)**
- **Urgência**, um select:
  - **Todos** (padrão): tudo aparece.
  - **Abaixo do mínimo**: urgente, inclui os zerados.
  - **Só zerados**: muito urgente.

  Nos dois últimos, a marca e o produto sem variação na situação somem da lista, e contador e furo
  contam só essas variações.
- **ABC**, um select com Todos, A, A+B e A+B+C (`p.abc`), como no PDF. Contadores, furo e listas
  contam só o que passa.
- **Ordem**, alfabética ou **furo** (radio/combo). Desempates:
  - **Marcas:** furo, depois variações abaixo, depois o nome.
  - **Produtos:** furo, depois unidades faltando, depois o nome.

  Somar unidades de UN, KG e MT não dá número, por isso a ordem é pelo furo em R$.

**Telas**
- **Marcas**: **todas as ativas**, inclusive as que a loja não trabalha (com 0). O primeiro item é
  **"Todas as marcas"**. Cada marca mostra "12 de 140 · R$ 3.450" (abaixo de trabalha · furo). Há
  pesquisa pelo nome.
- **Produtos**: a mesma lista com ou sem marca. **Todos os ativos**, inclusive os que a loja não
  trabalha ("0 de 0"). Cada produto mostra a letra ABC e "3 de 8 · faltam 46 UN · R$ 380". O produto
  que já está no rascunho leva um ícone e o total pedido. A lista é paginada e tem pesquisa com a
  **regra de palavras do F1 sobre tudo de cada barra**: nome, variação, sigla/embalagem, barras, os 6
  finais, referência da variação e da barra; todas as palavras na mesma barra. Uma linha por produto.
- **Variações** (tela do produto): **todas as variações ativas**, em vermelho a zerada e em laranja a
  abaixo do mínimo. A linha da loja que pede mostra saldo, mín, máx e **"faltam N"**; a variação sem
  mín/máx na loja mostra "—". Embaixo, uma linha por loja de onde pedir, com saldo, mín, máx,
  **"pedido: N"** e o **campo de quanto pedir** (vazio; a loja com saldo 0 aceita). O "pedido: N" é a
  soma em unidades dos itens dessa variação nas requisições de Reposição da loja para aquela loja
  ainda abertas (`status in A,S,P,T,D`), só como informação. O cabeçalho tem o botão que abre o
  **produto no app estoque** (`${process.env.ESTOQUE_URL}/produto/{codproduto}`, nova aba), para
  conferir mín/máx e embalagem.
- **Próximo produto**: o voltar devolve a lista como estava: pesquisa, ordem, páginas carregadas e
  posição. Fica na memória da store, não no persist.

**Quantidade e rascunho**
- Digita-se em **unidades**, e o valor **sobe para o múltiplo** da embalagem de transferência
  (`tblproduto.codprodutoembalagemtransferencia` → `tblprodutoembalagem.quantidade`): 7 com CX c/12
  vira 12 e mostra "1 CX c/12". O item sai na barra dessa embalagem com quantidade = unidades /
  múltiplo, ou na barra da unidade, se a variação não tem a da embalagem. **Sem embalagem de
  transferência não arredonda**, e a fração segue a unidade (`fracaoRecusada` como `:rules`).
- **Rascunho único** no Pinia persistente (localStorage; serve assim). Guarda a loja, as quantidades
  com o texto que a revisão mostra, os filtros e a ordem. **Nunca guarda saldo.** Com quantidade
  preenchida, a troca de loja é recusada: "gere ou limpe o rascunho do <loja> antes". "Limpar
  rascunho" só limpa.

**Revisão e geração**
- A revisão mostra um card por loja de onde pedir, com os itens (produto, variação, quantidade na
  embalagem), o botão de tirar item e o **radio de prioridade**. A prioridade vem pré-selecionada pela
  situação atual dos itens na loja que pede:
  - algum **zerado** → **Urgente**;
  - algum **abaixo** → **Normal**;
  - senão → **Baixa**.
- "Gerar requisições" cria uma por loja e **imprime as duas vias, como hoje**. A loja que deu certo
  sai do rascunho; a que falhou fica, com o erro.

**Prioridade da requisição**
- Campo `prioridade` com os valores **Baixa, Normal, Alta, Urgente**. A pré-seleção no formulário
  sai do destino:
  - **Cliente retira (R)** → **Urgente**;
  - **Entrega (E)** → **Alta**;
  - **Reposição (L)** → **Normal**.

  Quem pede troca na revisão da requisição nova.
- Depois de criada, a prioridade **só muda pelo botão "Prioridade"** do detalhe: em qualquer status
  aberto, pelos dois lados, com **motivo obrigatório**. O Editar não mostra a prioridade.
- Cada troca grava a **`tblauditoria`** com o tipo novo "Prioridade alterada", antes/depois e o motivo
  como justificativa. O detalhe ganha o **Histórico** (`MgHistorico`).
- O **painel ordena** por prioridade (Urgente → Baixa) e depois pela mais antiga. Cada card leva um
  **chip** (Urgente `red-5`, Alta `orange-5`). O detalhe e as **duas vias** mostram a prioridade, e a
  listagem ganha o filtro.

**Flag Defeito**
- O local 199001 guarda mercadoria com defeito à espera de substituição. O cadastro do local de
  estoque ganha a flag **"Defeito"**, e o local marcado fica fora da requisição inteira (painel,
  formulário e gerador).

## Etapas (do core para as beiradas; cada uma conferível sozinha)

### 0. Limpeza do "Sugerir abaixo do mínimo"
- `negocios/src/pages/RequisicaoFormPage.vue:250-265`: o botão e o tooltip.
- `negocios/src/stores/requisicao.js:246-270`: `sugerindo`/`sugerir()` e o que o `return` expõe deles.
- `api/routes/api.php:542`, `RequisicaoController::sugestao` (:67-81) e `RequisicaoService::sugestao`
  (:308-328), mais o `use` do `EstoqueSaldoRelatorioService` se ficar sem uso. O `itensComProduto`
  fica, porque o `rascunhoDoNegocio` usa.
- `api/app/Mg/Estoque/EstoqueSaldoRelatorioService.php:301-361`: o SQL do `transferenciasLinhas` volta
  para dentro de `transferencias()`, sem as duas subselects de `codprodutobarra`.
- Cuidado: `RequisicaoService.php` tem alteração não commitada do AC #29 da TASK-55
  (`ehRequisitante`/`ehRequisitada`). Não mexer nela.
- Conferir: `/requisicao/nova` sem o botão; `GET v1/requisicao/sugestao` dá 404; o PDF Sugestão de
  Transferências (estoque → Relatórios) sai igual.

### 0.5. Flag "Defeito" no local de estoque
- `api/database/estoquelocal_defeito.sql`: `alter table tblestoquelocal add column if not exists
  defeito boolean not null default false` e `update … set defeito = true where codestoquelocal =
  199001`.
- Backend:
  - `EstoqueLocal.php`, fillable e cast;
  - `EstoqueLocalRequest.php`;
  - `EstoqueLocalService::lojas()` (`:127`): o `codfilial != 199` dá lugar à flag;
  - `PdvService::estoqueLocal()` (`:345`): devolve `t.defeito` junto do `t.controlaestoque`.
- App estoque: o checkbox em `components/estoque-local/EstoqueLocalDialog.vue`, ao lado de Depósito e
  Controla estoque, e a exibição em `pages/estoque-local/Index.vue`/`Detalhe.vue` e no
  `stores/estoqueLocalStore.js`.
- Negocios: os filtros de `RequisicaoPainelPage.vue:108` e `RequisicaoFormPage.vue:79` ganham
  `&& !l.defeito`. O PDV que ainda não sincronizou não tem o campo, e aí o local aparece como antes.
- Conferir: marcar e desmarcar no app estoque. Depois de sincronizar o PDV, o Defeito some das abas do
  painel e das lojas do formulário.

### 0.6. Prioridade da requisição + histórico
- `api/database/requisicao_prioridade.sql`: `alter table tblrequisicao add column if not exists
  prioridade smallint not null default 2 check (prioridade between 1 and 4)`, onde 1 = Baixa,
  2 = Normal, 3 = Alta e 4 = Urgente; o número ordena sem `case`. Mais o índice
  `(codestoquelocalrequisitada, status, prioridade)` para o painel.
- Backend:
  - `Requisicao.php`: constantes `PRIORIDADE_*`/`PRIORIDADES` e o fillable.
  - `RequisicaoRequest`: `prioridade` **opcional**, `in:1,2,3,4`; sem ela, o banco põe Normal. Assim o
    PDV com o front antigo (até o F5) não leva 422.
  - `RequisicaoResource`: `prioridade` e `podePrioridade`, que vale para status aberto e para o lado de
    quem pede ou de quem atende.
  - `RequisicaoService`:
    - `criar()` grava a prioridade e `atualizar()` não mexe nela;
    - `painel()` ordena por `prioridade desc, criacao, codrequisicao`;
    - `pesquisar()` ganha o filtro `prioridade`;
    - o novo `prioridade(Requisicao $req, int $prioridade, string $motivo)` recusa sem motivo e grava
      `AuditoriaService::registrar('tblrequisicao', …, TIPO_PRIORIDADE_ALTERADA, ['prioridade' =>
      antes], ['prioridade' => depois], $motivo)`.
  - `AuditoriaService`: `TIPO_PRIORIDADE_ALTERADA = 24`, "Prioridade alterada".
  - Rota `PUT requisicao/{codrequisicao}/prioridade` no grupo `pdv`, com a transaction no Controller.
  - As vias: `RequisicaoImpressaoService` e `api/resources/views/requisicao/requisicao.blade.php`.
- Negocios:
  - `stores/requisicao.js`: `PRIORIDADES`, a prioridade no `formVazio`/`salvar` e a pré-seleção pelo
    destino.
  - `RequisicaoFormPage.vue`: radio na etapa de revisão, só na requisição nova.
  - `RequisicaoPainelPage.vue`: o chip.
  - `RequisicaoDetalhePage.vue`: o chip, o botão "Prioridade" (dialog com o radio e o Motivo em
    `MgInput`, obrigatório pelas `:rules`) e `<MgHistorico tabela="tblrequisicao" :codigo>`, como em
    `DetalheNegocio.vue:405`.
  - `components/drawers/RequisicaoLeftDrawer.vue`: o filtro.
- Conferir:
  - criar uma requisição com cada destino e ver a pré-seleção;
  - trocar pelo botão sem motivo (recusa) e com motivo, e ver a troca no Histórico;
  - ver a ordem e os chips no painel, e a prioridade nas vias.

### 1. Motor de busca + consulta do gerador (não grava nada): o core dos números
- **Motor único:** o texto de busca de cada barra e o filtro de palavras saem de
  `ProdutoBarraService::pesquisa` (`api/app/Mg/Produto/ProdutoBarraService.php:28`) para um método do
  `ProdutoBarraService` que devolve o SQL e os binds. O texto passa a incluir `pv.referencia` e
  `pb.referencia`. O F1 continua com a relevância e a paginação por cima; o gerador usa o método
  dentro de um `exists`. A pesquisa offline do PDV (Dexie, JS) fica fora.
- Backend: `api/app/Mg/Requisicao/RequisicaoGeradorService.php` (estático, SQL cru). Os endpoints
  ficam no `RequisicaoController`, no grupo `pdv` (`api/routes/api.php:538`):
  - `GET v1/requisicao/gerador/marcas?codestoquelocal=&abc=&situacao=` →
    `[{codmarca, marca, trabalha, abaixo, furo}]`.
  - `GET v1/requisicao/gerador/produtos?codestoquelocal=&codmarca=&busca=&abc=&situacao=&ordem=&page=` →
    `[{codproduto, produto, abc, sigla, trabalha, abaixo, faltam, furo, variacoes: [{codprodutovariacao,
    variacao, saldo, estoqueminimo, estoquemaximo}]}]`. Sem `codmarca` = Todas as marcas. Uma linha por
    produto, com as variações em `json_agg`, e os agregados calculados no SQL para a ordem valer na
    paginação.
  - `GET v1/requisicao/gerador/produto/{codproduto}?codestoquelocal=` → o produto (unidade, decimais,
    embalagem de transferência com quantidade/sigla) e as variações, com `codprodutobarraembalagem` e
    `codprodutobarraunidade`. Cada variação traz `lojas: [{codestoquelocal, saldo, estoqueminimo,
    estoquemaximo, pedido}]`. Não se reaproveita o `ProdutoService::saldoEstoque`: ele é N+1 e não
    traz as barras.
- Front (negocios), rotas em `negocios/src/router/routes.js`, navegação por `:to`:
  - `/requisicao/gerador`: escolhe a loja (`ListaOpcoes`, como a etapa "loja que pede" do
    `RequisicaoFormPage.vue`).
  - `/requisicao/gerador/:codestoquelocal/marcas`: a pesquisa, os selects Urgência e ABC, a ordem e
    "Todas as marcas" no topo.
  - `/requisicao/gerador/:codestoquelocal/marca/:codmarca?`: os produtos, com a pesquisa, os mesmos
    filtros, a ordem e a paginação (`q-infinite-scroll`).
  - `/requisicao/gerador/:codestoquelocal/produto/:codproduto`: as variações e o link para o estoque.
  - Botão "Gerador" no `RequisicaoPainelPage.vue`, ao lado do "Todas". A lista vazia usa o
    `MgEmptyState`.
  - Store `negocios/src/stores/requisicaoGerador.js` (`defineStore` de `#q-app/wrappers`, axios de
    `src/services/api`).
- Conferir:
  - o contador e o furo de uma marca contra o PDF de transferências da mesma loja;
  - a tela da variação contra a aba Estoque do produto no app estoque;
  - o F1 acha o mesmo de antes e passa a achar pela referência.

### 2. Quantidades + rascunho persistente
- `MgInputValor` por loja de onde pedir, na tela da variação. No blur, arredonda para cima ao múltiplo
  e mostra a embalagem. Com 0 ou vazio, o item sai do rascunho.
- Na store, `persist: { pick: ['codestoquelocal', 'itens', 'ordem', 'abc', 'situacao'] }`. O plugin já
  está em `negocios/src/stores/index.js:3`, no padrão de `estoque/src/stores/produtoStore.js:103`. A
  chave de `itens` é `codprodutovariacao-codestoquelocalrequisitada`.
- O cabeçalho das telas do gerador leva "Revisar (N)" e "Limpar rascunho". A lista de produtos marca o
  que já está no rascunho.
- Conferir:
  - 7 numa CX c/12 vira 12 (1 CX c/12);
  - fechar a aba e reabrir mantém as quantidades e os filtros;
  - trocar de loja é recusado com aviso.

### 3. Revisão + Gerar
- `/requisicao/gerador/:codestoquelocal/revisao`: os cards por loja, o tirar item e o radio de
  prioridade. A pré-seleção usa `GET v1/requisicao/gerador/situacao?codestoquelocal=&codprodutovariacao[]=`,
  que devolve `zerado`/`abaixo`/`ok` por variação.
- "Gerar requisições" faz um `POST v1/requisicao` por loja com `{codestoquelocalrequisitante,
  codestoquelocalrequisitada, destino: 'L', prioridade, itens: [{codprodutobarra, quantidade}]}`. Isso
  reaproveita o `RequisicaoService::criar`: validação, processamento e impressão. A loja que deu certo
  sai do rascunho; a que falhou fica, com o erro. No fim vai para `/requisicao/painel/:codestoquelocal`.
- Conferir: uma requisição de Reposição por loja no painel, com a prioridade pré-selecionada, os itens
  na barra da caixa e as vias impressas.

## Go-live
- **Depende do go-live da TASK-55**, que ainda está pendente (os SQLs `requisicao*.sql` não rodaram em
  prod).
- Antes do deploy, depois dos SQLs da TASK-55: `estoquelocal_defeito.sql` e `requisicao_prioridade.sql`.
  Os dois são idempotentes (`if not exists`).
- Depois do deploy, os PDVs precisam sincronizar para o Defeito sumir das listas deles. Até lá, o
  Defeito aparece como hoje.

## Verificação
Quem testa é o Fábio, pelo roteiro "Conferir" de cada etapa. Eu não rodo teste, script nem dev server
sem pedido. Eu rodo `php -l` nos PHP alterados e eslint + prettier nos `.vue`/`.js` alterados (negocios
e estoque).

## Alerta (sem abrir trabalho)
O gerador só acerta se o mínimo e o máximo estiverem certos. O cálculo automático está desligado
(`VendaMensalService::calcularMinimoMaximo` começa com `return false`), e o MGspa não tem tela para
editar o mín/máx (TASK-76, To Do); só o MGLara edita. Na simulação do dev, a Caderno Inteligente teve
95% das variações abaixo do mínimo no Centro.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado em 24-25/10/2026 (na árvore, aguardando validação do Fábio).

Go-live (depois dos SQLs da TASK-55, antes do deploy): api/database/estoquelocal_defeito.sql e api/database/requisicao_prioridade.sql (idempotentes). Depois do deploy, os PDVs precisam sincronizar para o Defeito sumir das listas deles.

Arquivos principais:
- api: RequisicaoGeradorService (consultas do gerador), ProdutoBarraService::sqlBusca (motor único F1 + gerador, agora com referência do produto, da variação e da barra), RequisicaoService::prioridade/podePrioridade (auditoria tipo 24), EstoqueLocal.defeito.
- negocios: stores/requisicaoGerador.js (rascunho persistente), pages/RequisicaoGerador*.vue, components/requisicao/Gerador*.vue; prioridade no formulário, painel, detalhe e listagem.
- estoque: flag Defeito no cadastro do local.

Roteiro de teste: ver o plano (seções Conferir de cada etapa).
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Gerador de requisição no painel (loja → marcas → produtos → variações → revisão), com furo em R$, filtros de urgência/ABC/ordem, rascunho persistente e uma requisição de Reposição por loja. Junto: saiu o Sugerir abaixo do mínimo da TASK-55; flag Defeito no local de estoque; prioridade da requisição (botão com motivo e auditoria, painel ordenado, vias, filtro); motor de busca único do F1 com referência. Testado no dev em 25/10/2026 (consultas, F1 antigo × novo, prioridade/auditoria e o fluxo inteiro no headless).
<!-- SECTION:FINAL_SUMMARY:END -->

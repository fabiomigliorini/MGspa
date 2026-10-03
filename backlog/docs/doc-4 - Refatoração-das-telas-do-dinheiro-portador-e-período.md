---
id: doc-4
title: Refatoração das telas do dinheiro - portador e período
type: specification
created_date: '2026-10-03 17:26'
---

# Refatoração das telas do dinheiro — portador e período

Desenhado com o Fábio em 03/10/2026. Complementa o
`backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md` (doc-3), que continua a fonte
do modelo de dados e das regras de negócio (glossário, decisões 1–29, M9.x, M10.x, M13.x). Este doc
manda nas **telas**. Task: **TASK-39**.

## Por quê

Os milestones M6.1 a M13 construíram telas operacionais e automações antes de existir onde ver o
core: o razão do portador (M10) lança sozinho e nenhuma tela mostra "o que entrou e saiu deste
portador neste período, e quanto sobrou". O mesmo dinheiro aparece em umas dez telas (Pagamentos,
Caixas com 4 abas, Fechamentos → Sessão, Saldos, Cadastros → Portadores, Caixa do PDV…), nenhuma
responde a pergunta básica, e por isso nada pôde ser validado.

**Ordem daqui em diante: do core para as beiradas, uma tela por vez.** Primeiro a tela do portador
e do período, onde o razão é visto e conferido; o core é validado nela; só depois cada tela das
beiradas (Caixa do PDV, Fechamentos, itens, wizard) é redesenhada, numa conversa própria, desenho
antes de código.

**KISS**: uma tela polivalente, não uma tela e um processo para cada caso.

## Escopo desta tarefa

Duas telas no **contas**, mais o que o backend precisa para elas. Nada além.

| Sai | Entra |
|---|---|
| Movimento → **Saldos** (`SaldosPage`, matriz filial × banco) | `/portador` — o painel |
| Cadastros → **Portadores** (`pages/portador/Index.vue`, o CRUD) | `/portador` (FAB e cabeçalho) |
| — | `/portador/{codportador}/{codportadorperiodo?}` — o portador e o período |

**Fica como está nesta tarefa** (cada um terá sua conversa): Caixa do negocios (`/caixa`),
Movimento → Caixas (abas Portadores, Transferências, Períodos, Itens), Fechamentos (sessão, lote,
cheque, vale, duplicata, venda), Pagamentos. Não apagar nada deles. Onde eles linkam para portador
ou período, podem passar a apontar para a tela nova se for trivial.

## Decisões (R.x, não reabrir)

1. **Painel `/portador`** — substitui Saldos e Cadastros → Portadores. Mostra **todos** os
   portadores ativos (inativos atrás do filtro "mostrar inativos").
2. **Agrupado por filial.** Filtro de filial no drawer: padrão a filial do usuário; para
   Financeiro/Admin, "Todas". Total por filial (só da espécie, ver R3).
3. **Saldo só na espécie nesta fase** (tipo `E`: gavetas, cofres, troco, Caixa Financeiro). Banco,
   adquirente, cartão e outros aparecem sem saldo (um traço), com as movimentações normais: o saldo
   deles não é confiável até a conciliação (banco pelo OFX; cartões no M14). Quando ficar, entra no
   mesmo painel, sem refazer a tela.
4. **Linha do painel**: nome, tipo, saldo (R3); na gaveta, a situação ("aberto desde 08:02 por
   Maria" / "fechado em 02/10 18:40"); aviso quando houver transferência a confirmar chegando ou
   saindo. A linha inteira é um link (`:to`) para `/portador/{codportador}`.
5. **FAB "+" no painel** cria portador (o form do CRUD de hoje, num dialog). **Editar, inativar e
   excluir** ficam no cabeçalho de `/portador/{codportador}`. Excluir só se o portador nunca teve
   movimento; com movimento, só inativar.
6. **Importação do OFX** sai da Saldos para o painel (botão no cabeçalho do painel, mesmo dialog e
   mesma rota `v1/portador/importar-ofx`). A **Extrato do banco** (`ExtratoPage`, o que o banco
   mandou) abre por um botão no cabeçalho do portador banco. A conciliação razão × extrato não é
   desta tarefa.
7. **`/portador/{codportador}/{codportadorperiodo}`**: cabeçalho do portador; abaixo, **três níveis
   de abas — Ano → Mês → Período** — só com o que existe (ano só se tem mês; mês só se tem
   período). Selecionar um período atualiza a URL para `/portador/{cod}/{codperiodo}`; sem período
   na URL abre o último. Cada aba de período mostra a data, o saldo e a situação.
8. **Cabeçalho do período**: situação (aberto, fechado por quem e quando, reaberto) e as ações de
   **estado**: na gaveta, Abrir caixa e Fechar com contagem (`@components/caixa/ContagemCaixa.vue`,
   regras do M13), Reabrir, Borderô; nos demais, Fechar com corte e Reabrir (regras do M12).
9. **Resumo do período** acima da lista: saldo inicial; entradas e saídas **por origem** (Vendas,
   Títulos, Transferências, Avulsos e ajustes, Itens do caixa); saldo final. Na gaveta, também
   contado × sistema na abertura e no fechamento. É o formulário "Movimento do Caixa" de papel.
   Clicar numa origem filtra a lista.
10. **Lista de lançamentos** = linha de extrato: data e hora do fato (`transacao`); origem em texto
    com link ("Venda 123456 · João", "Sangria → Cofre Centro", "Baixa de 3 títulos · José",
    "Ajuste de caixa", "Item: Chips de celular"); meio; valor (entrada verde, saída vermelha); saldo
    corrente. Transferência a confirmar em amarelo; cancelado (linha inativa) riscado na própria
    lista e fora do saldo. Clicar abre o detalhe do pagamento. **Ações do lançamento na linha**:
    confirmar/cancelar transferência, cancelar avulso.
11. **FAB do período** cria movimento: principal **Transferir**; mini **Avulso** e, na gaveta,
    **Item do caixa**. Reusar `@components/caixa/{TransferirCaixaDialog, AvulsoCaixaDialog,
    ItemCaixaDialog}` e as rotas que já existem (transferência `v1/pagamento/transferencia`; avulso
    da gaveta `v1/caixa/sessao/{id}/avulso`; avulso de não-gaveta `v1/portador-periodo/lancamento`;
    item `v1/caixa/sessao/{id}/item/{codcaixaitem}`).
12. **Período nasce sozinho** no primeiro movimento da data (já é assim:
    `PortadorPeriodoService::doMomento`/`corrente`). **Exceção só da gaveta**: sem período aberto
    na data do movimento → 422 "Gaveta não aberta" (já é o `CaixaService::vincular`; manter uma
    regra só, no service).
13. **Saldo gravado, não calculado na tela**:
    - o período mantém `saldofinal` **sempre atualizado**, aberto ou fechado (= `saldoinicial` +
      Σ linhas ativas do período); fechar só congela;
    - coluna nova **`tblportador.saldo`** = `saldofinal` do último período do portador (facilita o
      painel);
    - quem mantém os dois é o **`PortadorMovimentoService::sincronizar`** (escritor único do
      razão), propagando para os períodos seguintes do mesmo portador (`saldoinicial` do seguinte =
      `saldofinal` do anterior) quando o lançamento cai num período que não é o último. Fechar,
      reabrir e o ajuste da gaveta passam pelo mesmo cálculo.
14. **Todo endpoint que cria ou muda movimento devolve, no resource, o período atualizado** (com
    saldos e resumo); o front troca o período da tela pelo que veio, sem recalcular nada.
15. **Quem vê**: Gerente vê as filiais dele; Financeiro e Admin, todas (regra do
    `ConferenciaAutorizador`). Cadastro do portador (criar, editar, inativar, excluir, OFX):
    Financeiro e Admin, como hoje. As ações de movimento seguem as permissões que já existem
    (`TransferenciaAutorizador`, regras do caixa no M13, períodos do M12).
16. **Saldo da gaveta sempre visível** (M13.7 acabou com o "às cegas"). Sobras da regra antiga
    (`v1/portador/caixas`, `/saldo`, `PortadorPeriodoResource` escondendo saldo de gaveta não
    conferida) saem.

## Backend

- **DDL `api/database/portador_saldo.sql`** (padrão do `conferencia.sql`: `\set ON_ERROR_STOP on`,
  `BEGIN`, timeouts, idempotente, `COMMIT`): `tblportador.saldo numeric(14,2) NOT NULL DEFAULT 0`;
  carga: `saldofinal` dos períodos abertos calculado, depois `tblportador.saldo` do último período.
  Roda no go-live depois do `caixa_item.sql` e antes do `tipo_titulo_limpeza.sql` (atualizar a
  ordem no topo do doc-3).
- `PortadorMovimentoService::sincronizar` mantém `saldofinal` e `tblportador.saldo` (R13);
  `PortadorPeriodoService::{fechar, reabrir, saldo, movimento}` e o `CaixaService` passam a ler o
  gravado em vez de recalcular, onde fizer sentido.
- Endpoints da tela (reusar o que existe; criar só o que faltar):
  - painel: portadores com saldo, tipo, filial e, na gaveta, a situação da sessão e pendências;
  - árvore de períodos do portador (ano → mês → período, com saldo e situação);
  - período: cabeçalho, resumo por origem e lançamentos (linha do razão + pagamento + origem em
    texto e link; a origem já é calculada no `PagamentoListaService`).
- Resources de movimento devolvem o período (R14).

## Decisões da execução (03/10/2026, com o Fábio, antes de codar)

- **Store do domínio período**: `@components/stores/periodoStore.js`, genérico para qualquer
  portador. Os dialogs `@components/caixa/{Transferir, Avulso, Item}CaixaDialog` passaram a usá-lo
  (Avulso fora da gaveta com campo Data; Item com o select do item da sessão). O `MgCaixaSessao`
  entrega a gaveta e a sessão ao store (`usar`) e recarrega depois de cada movimento; a tela do
  caixa não mudou para quem usa.
- **R14**: as rotas de movimento devolvem, além do que já devolviam, `periodos` = todos os períodos
  afetados, completos (o de cada lado do razão e os seguintes do mesmo portador). Sem parâmetro.
- **`v1/portador/caixas` e `/saldo` saíram** (R16). A tela Caixas do contas fica como está e quebra
  (aba Portadores) até ser redesenhada. O Transferir pega as gavetas fechadas do painel
  (`v1/portador/painel`); usuário só Caixa não vê o painel, então não vê o bloqueio (o servidor
  recusa com 422).
- **Cancelar avulso fora da gaveta**: rota nova `POST v1/portador-periodo/lancamento/{id}/cancelar`
  (Financeiro, justificativa).
- **Abas**: período que atravessa meses fica no mês do **início**.
- **Menu**: Movimento → Portadores (no lugar de Saldos); Cadastros → Portadores saiu. Rota aberta
  também ao Gerente; cadastro e OFX escondidos para ele e recusados (403) no backend.
- **Mensagem única** da gaveta sem caixa aberto, no `CaixaService::naoAberta`: "Gaveta não aberta
  (nome): abra o caixa antes de movimentar." (vincular, recebimento no PDV, transferência, avulso,
  item, cancelar avulso).

## Como ficou no código (03/10/2026)

- **DDL** `api/database/portador_saldo.sql` rodado 2x em dev (a segunda: `UPDATE 0`, `UPDATE 0`).
- **Saldo gravado (R13)**: `PortadorPeriodoService::recalcular(periodo)` trava o portador, recalcula
  o `saldofinal` do período em diante (o seguinte começa com o final do anterior; fechado fica
  congelado) e grava `tblportador.saldo`. Chamado pelo `PortadorMovimentoService::sincronizar`
  (do período mais antigo mexido de cada portador), pelo `fechar`/`reabrir` (que não apaga mais o
  `saldofinal`) e pelo `CaixaService::abrir`; o corrente e a sessão nascem com `saldofinal` =
  inicial. `PortadorPeriodoService::saldo` (cálculo da decisão 19) saiu.
- **Rotas**: `GET v1/portador/painel` (`PortadorService::painel`), `GET
  v1/portador/{cod}/periodo/{codperiodo?}` (`PortadorPeriodoController::tela`: portador, `pode`,
  abas e o período com lançamentos), o cancelar acima. Saíram `v1/portador/caixas`,
  `v1/portador/{id}/saldo`, `v1/portador/lista-saldos` (e `SomatorioSaldoResource`).
- **`PortadorPeriodoResource`**: saldos gravados e sempre visíveis; com lançamentos traz `texto`
  (R10), `saldo` corrente, `resumo` por origem, flags das ações da linha e, na gaveta, `caixa`
  (contado × sistema) e `itens`.
- **contas**: `pages/portador/Index.vue` (painel), `pages/portador/Detalhe.vue` (portador + abas
  Ano → Mês → Período em `q-route-tab`), `components/portador/{PortadorDialog, OfxDialog,
  PeriodoCabecalho, PeriodoResumo, PeriodoLancamentos}.vue`, `stores/portadorStore.js` (painel +
  cadastro). Saíram `SaldosPage`, `saldoStore`, `SaldosFiltrosDrawer` e o CRUD antigo. Extrato do
  banco volta para o portador.
- **Conferido**: tinker com rollback (painel, transferência com os dois períodos afetados, avulso e
  cancelar, gaveta fechada = 422, tela, Gerente só a filial dele, 403 em outra filial e no
  cadastro) e no navegador (Chrome headless, usuário fabio): painel, gaveta, Troco Centro com
  transferência → Caixa Centro, avulso e cancelamento pela linha, cancelar transferência, abas
  trocando a URL, voltar e recarregar, Fechamentos → Sessão (MgCaixaSessao), celular (390 px). Os
  testes deixaram em dev, canceladas, uma transferência Troco Centro → Caixa Centro de 20 e um
  avulso de 5 no Troco Centro. Não testado no navegador: abrir/fechar a gaveta pela tela nova, item
  do caixa e fechar com corte (a gaveta estava em uso nos testes do Fábio).
- **Ajustes pedidos pelo Fábio na mesma conversa**: painel com um cabeçalho por tipo (Espécie,
  Banco, Adquirente, Cartão da empresa, Outros) dentro de cada filial; badge verde "Aberto" na
  gaveta (painel), no cabeçalho do portador e nas abas de período; primeiro movimento de portador
  sem período passa a mostrar o período criado e levar a URL a ele, sem F5; Lançamento avulso com
  Data já preenchida (agora; só leitura na gaveta), Tipo em radio com o foco inicial, Entrada/Saída
  em combo na linha do Valor, Cancelar fora do Tab (o mesmo nos demais dialogs da tela; o
  Transferir com "Enviar de / Receber em" em radio); lançamentos em `q-timeline` (entradas à
  esquerda, saídas à direita, uma coluna no celular) e, na transferência, "Ver em {outro
  portador}", que abre o extrato do outro lado no período onde o valor caiu (`contraparte` no
  resource).
- **Commitado em 03/10/2026 sem validação, a pedido do Fábio**: ele valida pelo roteiro abaixo.
- **Em aberto**: "Abrir caixa" com contagem de cédulas no cofre, troco e Caixa Financeiro (só a
  implantação, ou abrir e fechar todo dia como a gaveta, o que mexe na decisão 18 do doc-3). Hoje a
  implantação é um avulso de Ajuste (Entrada) ou uma transferência recebida.
- **Dev**: a sessão 38 da gaveta (de antes do M13) tem saldo final 250 com inicial 300 e −46,10 no
  razão; reaberta e fechada de novo pelo Fábio em 03/10 às 14:07, ficou consistente.

## Valida (o core, pela tela nova)

Em dev, PDV 508, gaveta "Gaveta Dev Fabio". Cada passo é conferido **em `/portador/{cod}`**:
lançamento na lista com a origem certa, resumo por origem e saldo do período e do painel batendo.

1. Painel: todos os portadores, agrupados por filial; saldo só na espécie; gaveta com a situação;
   FAB cria portador; editar/inativar/excluir pelo cabeçalho (excluir com movimento = recusa);
   importar OFX pelo painel; Extrato do banco pelo cabeçalho do banco.
2. Abrir o caixa da gaveta pela tela (contagem) → período novo na aba do dia; ajuste de abertura,
   se houver, como linha "Ajuste de caixa".
3. Venda de R$ 10 em dinheiro no PDV → linha "Venda nº · cliente", +10, saldo corrente e painel
   atualizados.
4. Sangria gaveta → cofre (FAB Transferir) → amarela "a confirmar" nos dois portadores; confirmar
   pelo cofre → normal; outra sangria cancelada → riscada nos dois, fora do saldo.
5. Avulso de saída na gaveta e num cofre → linha "Avulso/Ajuste" com o motivo; cancelar pela linha.
6. Receber um título por transferência no contas com data de ontem → linha no banco (sem saldo
   no painel, R3), no período da data.
7. Fechar a gaveta com contagem → ajuste de fechamento, saldo final = contado; reabrir.
8. Cofre: fechar com corte; lançamento depois do corte cai no período seguinte (aba nova);
   reabrir; saldo inicial do seguinte acompanha.
9. Movimento na gaveta sem caixa aberto → "Gaveta não aberta".
10. URL: navegar pelas abas troca a URL; recarregar a página volta ao mesmo período; voltar do
    navegador funciona.
11. Celular: painel e período usáveis no celular do gerente.

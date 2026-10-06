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

## Redefinição do domínio do dinheiro (03/10/2026, com o Fábio)

O modelo tinha sido construído com definições erradas: ajuste e transferência viravam
`tblpagamento` (igual a pagamento de verdade), ajuste/taxa/tarifa/rendimento misturados em
"Avulsos e ajustes", ajuste automático inventado na abertura e no fechamento. Esta seção **manda**
sobre o que vier antes neste doc e no doc-3 onde houver conflito (R3 origens, Valida 2/5/7, R6 da
TASK-39). Escopo: **o dinheiro em espécie**.

### Princípio: cada botão faz uma coisa só

Filosofia Unix (regra do projeto, `CLAUDE.md`): cada botão, rota e ação faz uma coisa só, e bem
feita. Abrir abre; contar conta; fechar fecha. Se falta um pré-requisito, a ação **recusa** dizendo
o que falta — não faz o pré-requisito sozinha, não abre outro formulário no caminho e não mexe em
outro período. Automação de evento (uma ação disparar outra) só quando o Fábio pedir.

### Movimento do portador

`tblportadormovimento` é o **registro principal** do saldo. Cada linha tem **tipo**, valor com
sinal, data e período; `codpagamento` só no tipo pagamento. A tela do portador lista os movimentos
agrupados por período.

| Tipo | O que é | Contraparte | Grava |
|---|---|---|---|
| **Pagamento** (venda em dinheiro, título, vale) | dinheiro entrou/saiu por causa de alguém | pessoa (negócio, título, acerto) | `tblpagamento`, que gera a linha (`PortadorMovimentoService::sincronizar`) |
| **Ajuste** | acerto do saldo, aceitar uma diferença sem explicação | nenhuma | só a linha, com o motivo na observação |
| **Transferência** (depósito, reforço, sangria) | saldo sai de um portador e entra em outro | o outro portador | duas linhas ligadas pelo par (−X origem, +X destino); estado e justificativa **nas duas**, o backend mantém as duas iguais |

- **Taxa, tarifa, rendimento**: fora do escopo do dinheiro; continuam pagamento sem pessoa
  (`tblpagamento.motivo` T/F/R), lançados só no banco.
- **Item do caixa**: redefinido na seção "Itens do caixa" (item que conta como cédula, que
  conta como cédula; tipo I no movimento).
- **Cancelar**: ajuste e transferência só se cancelam, com justificativa, e continuam visíveis em
  "Mostrar cancelados". Com o período não fechado (nos dois lados, na transferência).
- **Em qual período cai**: no contas, no período da tela, com a data dentro do início/fim dele; o
  outro lado da transferência, pela data. No PDV e nas outras telas, a data decide.
- As linhas do pagamento usam `inativo` para a troca interna; o cancelamento de
  ajuste/transferência é o estado próprio, separado.

### Papéis no portador

Cada portador (qualquer tipo) tem uma **lista de usuários com papel** (`tblportadorusuario`). Ela
**substitui** a regra por grupo e filial. Administrador é gestor em todos sem estar na lista.

| | Depositante | Operador | Gestor |
|---|---|---|---|
| Ver o portador, o saldo e os movimentos | | ✓ | ✓ |
| Aparecer como **destino** no select | ✓ | ✓ | ✓ |
| Aparecer como **origem** no select | | ✓ | ✓ |
| Lançar ajuste e transferência; abrir, contar e fechar período | | ✓ | ✓ |
| Confirmar transferência que chegou | | | ✓ |
| Reabrir, editar início/fim, dividir, unificar | | | ✓ |
| Cuidar da lista de usuários do portador | | | ✓ |

- **Depositante** só diz para onde a pessoa pode mandar dinheiro: não vê saldo nem movimento. Os
  selects de origem e destino mostram **só** os portadores em que o usuário tem papel (evita a
  sangria para o portador errado). Sem papel, o portador não aparece em lugar nenhum.
- Transferência registrada por **gestor do destino** nasce **feita**; senão fica **a confirmar** até
  um gestor do destino confirmar. Ex.: caixa (operador na gaveta, depositante no cofre) faz sangria
  → gerente (gestor no cofre) confirma; gerente faz sangria para o cofre dele → feita; gerente
  (depositante no Caixa Financeiro) manda ao financeiro → financeiro (gestor) aprova.
- A lista é editada pelo cadeado ao lado do lápis do portador (gestor ou Administrador).
- Na virada, a lista nasceu da regra de hoje: Gerente da filial = gestor na gaveta, cofre e troco;
  Caixa da filial = operador na gaveta e depositante no cofre e troco; Financeiro = gestor no Caixa
  Financeiro e nos demais tipos; Gerente = depositante no Caixa Financeiro e nos bancos.
- **O PDV não valida papel**: quem está naquela gaveta trabalha nela.

### Período (todo portador em espécie)

- Todo portador em espécie (gaveta, cofre, troco, Caixa Financeiro) **abre, conta e fecha**.
  "Sessão" deixou de existir: é tudo **período**.
- **Estados**: **aberto** (sem fim; só um por portador), **pendente** (com fim, sem fechamento: não
  recebe o movimento do dia a dia, só correção) e **fechado** (o razão trava).
- **Saldo inicial** = **contagem final do período anterior** (soma das cédulas e moedas; sem
  contagem, o saldo final dele). Nunca editado à mão. No primeiro período do portador, é a contagem
  inicial. Saldo final = saldo inicial + movimentos.
- **Contagem inicial**: nasce **preenchida com a contagem final do anterior**; quem abre confirma e
  altera se tiver diferença para registrar. Se não bater com o saldo inicial, a tela mostra — não
  ajusta nada.
- **Corrigir a contagem final** de um período: o saldo inicial do seguinte acompanha; a contagem
  inicial do seguinte também acompanha, **a menos que as duas já fossem diferentes** antes da
  correção (quem abriu contou outra coisa). Com o seguinte já fechado, recusa (reabra ele antes).
- **Diferença** = contagem final − saldo final, gravada no período (para levantar os dias com
  diferença). **Tolerância** por portador (`tblportador.tolerancia`, padrão R$ 2,00, no cadastro).
- **Abrir novo período**: só confirma (sim/não). O início quem decide é o servidor: o segundo
  seguinte ao fim do anterior; sem período, o começo de hoje.
- **Fechar**: só fecha o período pedido, com a contagem final **já informada** (contar é o botão do
  resumo).
  - Recusa, sem mexer em nada, se houver **período anterior por fechar** (fecha-se do mais antigo
    para o mais novo) ou **transferência a confirmar** no período.
  - Diferença dentro da tolerância: **fecha**; a diferença fica registrada.
  - Acima: dá o **erro** e o período fica **pendente** (com fim; o dia seguinte abre normal). A
    correção (vale ao colaborador quando falta, o movimento que faltou, ajuste) entra no pendente,
    e o mesmo Fechar tenta de novo.
- **Reabrir** (gestor): do mais novo para o mais antigo. O último volta a aberto; um anterior fica
  pendente. Deixa corrigir a contagem final.
- **Início e fim** (gestor): sem invadir os vizinhos nem deixar lançamento de fora.
- **Dividir** (gestor): o corte é escolhido numa régua do período com os lançamentos marcados
  (começa no meio do tempo; o campo de data anda junto com a régua). As **duas partes ficam
  pendentes**: a primeira termina no corte, sem contagem final; a segunda fica com o fim, a
  contagem final e o estado de antes (se o original estava aberto, ela continua aberta) e começa
  com o saldo da primeira.
- **Unificar** (gestor): junta o período ao anterior, os dois não fechados e o anterior sem
  diferença (para nenhuma sumir).
- A linha do item divide e unifica como qualquer linha (seção "Itens do caixa").

### A tela do portador

- **Abas** de ano, mês e período em **ordem decrescente** (o mais novo primeiro). A aba do período
  mostra **só a data final** (15/jul/2026); sem fim, "aberto". Sem período aberto, **"Novo
  período"** é a primeira aba.
- **Cabeçalho do período**: a situação e um botão por ação (fechar, início e fim, dividir,
  unificar, reabrir, borderô), conforme o papel. Pendente mostra o que falta.
- **Resumo**: saldo inicial (com a contagem inicial e se confere), entradas e saídas por origem
  (vendas, títulos e vales, itens, transferências, ajustes, taxas), saldo final, contagem final e
  diferença (verde dentro da tolerância). Os botões ao lado dos saldos são as contagens.
- **Lançamentos**: linha do tempo por dia; pagamento abre o pagamento; transferência leva ao outro
  portador; confirmar e cancelar na linha. Os botões de **ajuste (+)** e **reforço/sangria** ficam
  no cabeçalho dos lançamentos.
- **Cadastro do portador em espécie**: só portador, tipo, filial e tolerância (sem banco, conta,
  Pix, boleto).

### O que saiu

Transferência e ajuste de dentro do pagamento (`PagamentoService::transferir/confirmar/...`,
`TransferenciaAutorizador`, `TransferenciaResource`, origem X da listagem de pagamentos,
`motivo` A); a permissão por grupo e filial no caixa; `CaixasController/Service`; as abas
Portadores, Transferências e Períodos da página Caixas do contas (ficou só "Movimento dos Itens");
o ajuste automático de abertura/fechamento.

### Como ficou no código (commit 86138ae06, 03/10/2026; não validado)

- **DDL** `api/database/portador_movimento_tipo.sql` (rodado no dev, idempotente):
  `tblportadormovimento` com tipo P/A/T, estado, observação, par, confirmação, cancelamento e
  justificativa (`codpagamento` opcional; check amarra tipo × pagamento × estado);
  `tblportadorusuario` (papel D/O/G) preenchida pela regra de hoje; `tblportador.tolerancia`;
  `tblportadorperiodo.diferenca`. Os ajustes (`motivo` A) e as transferências viraram linhas do
  movimento e os pagamentos foram apagados; `motivo` aceita só T/F/R. Saldos iguais antes e depois.
- **Backend** (`api/app/Mg/Portador`): `PortadorAutorizador` (papel do usuário),
  `PortadorLancamentoService` (ajuste, transferência, confirmar, cancelar), `PortadorPeriodoService`
  (abrir, inicioDoNovo, contar, fecharCaixa, reabrirCaixa, editarDatas, dividir, unificar,
  recalcular; banco: fechar com corte, reabrir, taxa/tarifa/rendimento), `PortadorUsuarioController`,
  `PortadorLancamentoController`. O `CaixaService` ficou só com o PDV (gaveta, vincular, itens,
  borderô).
- **Rotas**: `v1/portador/{cod}/usuario`, `v1/portador/{cod}/periodo/abrir`,
  `v1/portador-periodo/{id}/{fechar,reabrir,contagem,datas,dividir,unificar,ajuste,bordero}`,
  `v1/portador-movimento/{transferencia,{id}/confirmar,{id}/cancelar}`. O PDV continua em
  `v1/caixa/*` (com o `codpdv` da gaveta não valida papel; abrir sem início usa agora).
- **PDV** (`MgCaixaSessao`): só adaptado às rotas novas; não testado no navegador.

## Itens do caixa (03–06/10/2026, com o Fábio)

Os itens do M13 (doc-3, decisão 27) eram fixos: 6 cadastros semeados, colunas fixas por período
da gaveta, modos C/M, pagamento em dinheiro na gaveta e título de repasse criado sozinho no
fechamento. Esta seção **manda** sobre aquilo. Vale para todo item que **conta como cédula**
(chips de celular, ingressos impressos): mercadoria com preço de face que fica no caixa, sem
parceiro, acerto nem título por enquanto. Começou-se pelos chips; os outros tipos de item (sem
estoque, com acerto) vêm um por um, adaptando a estrutura.

### Definições

1. **Cadastro de um nível**: cada linha é o que se controla ("Chips de celular", "Ingressos
   Brígida"). Mínimo: o nome (e inativo); sem filial. Pessoa, conta, modo voltam quando o item
   que precisar chegar.
2. **O item conta como cédula**: no início e no fim do dia o caixa informa cédulas + moedas +
   itens. O **saldo do portador inclui os itens** (a valor de face) e a **diferença é uma só**,
   com a tolerância do portador. O saldo inicial (contagem final do anterior) já vem com os
   itens.
3. **Vender não lança nada**: o item vira dinheiro, o saldo não muda.
4. **Entrada de item** (botão `style` no cabeçalho dos lançamentos), com sinal: + chegou; − saiu
   sem venda (devolveu, perdeu). É o **único lançamento** do item: **tipo I** no movimento do
   portador, sem `tblpagamento`. Cancela-se com justificativa, como o ajuste (operador, período
   não fechado). As linhas são sempre novas: **descrição** (typeahead com as já usadas no item),
   **preço** e **quantidade**; "+ Linha" acrescenta, o X exclui.
5. **Contagem**: um bloco por item que está no portador, **um campo de quantidade por preço**
   (como cédula), rotulado com o preço e a descrição. Preço novo só entra pela entrada.
6. **Qualquer portador em espécie** (gaveta, cofre, troco, Caixa Financeiro). Não há vínculo a
   cadastrar: a entrada oferece todos os itens ativos, e o item entra na contagem do portador na
   primeira entrada ali e **vai de um dia para o outro até zerar** (contou zero, some da contagem
   seguinte).
7. **Só na tela do período do contas** por enquanto. O PDV (`MgCaixaSessao`) perdeu o item antigo
   e ganha o item na refatoração dele. Até lá, a contagem feita pelo PDV não mexe nos itens já
   contados (mantém os de antes); gaveta com itens no saldo fechada pelo PDV sem contá-los mostra
   a diferença deles.
8. **Tela do item** (contas → Itens do Caixa → o item): os registros que apontam para ele, com o
   que cada portador tem e os períodos em que o item mexeu (abertura, entradas, fechamento,
   diferença e o total dos fechados); editar, inativar e excluir no cabeçalho (a lista só
   navega).

### Anotado para os próximos itens (não fazer agora)

- **Item sem estoque** (Bilhete Agora, Redeflex, Bradesco Expresso): o caixa lança por item o
  **total do dia** do borderô do parceiro, um valor com sinal (tipo I). **Foto do borderô
  opcional, com aviso** "sem borderô".
- **Acerto com o parceiro à parte do caixa**: financeiro ou gerente, a qualquer hora (inclusive
  com o caixa aberto); cada fechamento do movimento do parceiro vira um título a pagar. Falta
  definir: o que o fechamento abrange, se o título nasce do fechamento ou por botão, e como fica o
  estoque no corte.
- **Ingressos com variações** (masculino/feminino, preços diferentes), que nascem na entrada.

### Como ficou no código (04–06/10/2026; não validado)

- **DDL** `api/database/caixa_item_dinamico.sql` (idempotente; roda no go-live depois do
  `portador_movimento_tipo.sql`): tipo I no check do `tblportadormovimento` (exige
  `codcaixaitem` e `itens` jsonb, estado E/C); `tblportadorperiodo.contagemitensinicial/final`
  (jsonb `{codcaixaitem: [{preco, quantidade, descricao}]}`, ao lado das cédulas); apaga
  `tblcaixaitemlancamento` (recusa se tiver item movimentado) e
  `tblpagamento.codcaixaitemlancamento`; `tblcaixaitem` só com item e inativo (saem modo,
  codfilial, codpessoa, codcontacontabil, ordem) e, dos 6 itens iniciais, só o chip. No dev o
  Fábio já apagou à mão (05–06/10); o script completo foi conferido no dev com rollback, também
  na ordem do go-live (`caixa_item.sql` e depois este).
- **Backend**: `PortadorLancamentoService::lancarItem/cancelarItem`, rota
  `POST v1/portador-periodo/{id}/item`, cancelar em `v1/portador-movimento/{id}/cancelar`;
  `PortadorPeriodoService` (contado = cédulas + itens; gravarContagem, mesmaContagem, abrir,
  contar, fechar, dividir, unificar levam os itens); `CaixaItemService` (linhas, totais,
  descrições, períodos e total dos fechados);
  `PortadorPeriodoResource` (linha I, `contagem.*.itens`, `itens` ativos com os preços conhecidos
  e `contar` quando está no portador); `GET v1/caixa-item/{id}/descricao` (typeahead) e as rotas
  da tela do item; borderô com os itens na contagem. Saíram `CaixaItemLancamento(Service)`,
  `CaixaService::lancamentos/salvarItem/pagamentoNaGaveta/titulosRepasse/estornarRepasse/itens`,
  `exigirSemItens`, a origem I dos pagamentos e as rotas `v1/caixa/sessao/{id}/item` e
  `v1/caixa/item-lancamento`.
- **Front**: `@components/caixa/ItemCaixaDialog` (entrada/saída, linhas novas com typeahead) e
  `LinhasItemCaixa` (um campo por preço), `ContagemCaixa` com os itens, `periodoStore.lancarItem`;
  contas: botão nos lançamentos de todo portador em espécie, itens no diálogo da contagem,
  cadastro só com o nome, tela do item (`caixaItem/Detalhe` e `CaixaItemFechamentosDialog`),
  "Movimento dos Itens" removido.

### Valida (itens do caixa)

1. contas → Cadastros → Itens do Caixa: criar e editar pedem só o nome; clicar no item abre a
   tela dele.
2. contas → Portadores → um portador em espécie (gaveta, cofre, troco) → período aberto: botão
   `style` (Entrada de item) nos lançamentos → escolher o item → descrição (digitar "Cl" sugere
   "Claro"), preço 10,00, quantidade 10 → Lançar. Saldo final sobe R$ 100,00; linha "Entrada:
   Chips de celular" na linha do tempo; resumo "Itens do caixa".
3. Saída de 1 × R$ 10,00 (sem venda): saldo desce R$ 10,00.
4. Contagem final (botão ao lado do saldo final): cédulas + o bloco do item com o campo
   "R$ 10,00" já listado; contar o que sobrou. Total geral = cédulas + itens; diferença uma só.
5. Venda em dinheiro de um item: nada lançado; contar um a menos e R$ 10,00 a mais → mesma
   diferença.
6. Cancelar a entrada (na linha): saldo volta; aparece em "Mostrar cancelados".
7. Fechar e abrir o seguinte: contagem inicial já vem com os itens.
8. Contar zero, fechar e abrir o seguinte: o item some da contagem. Borderô do período mostra os
   itens na contagem.
9. PDV `/caixa`: sem o bloco de itens; abre e fecha como antes.
10. Tela do item: os períodos de cada portador e a linha "Total dos fechados" (abertura do mais
    antigo + entradas + diferença = fechamento do mais novo).

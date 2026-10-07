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
4. **Entrada ou saída de item** (botão `style` no cabeçalho dos lançamentos; Entrada / Saída é o
   primeiro campo do diálogo), com sinal: + chegou; − saiu sem venda (devolveu, perdeu, recolheram
   o bloco de ingressos). É o **único lançamento** do item: **tipo I** no movimento do
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
   que cada portador tem e os períodos em que o item mexeu (abertura, entradas e saídas numa
   coluna só, a saída negativa, fechamento, diferença e o total dos fechados); editar, inativar e
   excluir no cabeçalho (a lista só navega).

### Itens de parceiro (TASK-39, critérios #20 e #21; decidido com o Fábio em 06/10/2026)

O parceiro (Bilhete Agora, outras tiqueteiras, Redeflex, Rede Card) deixa a maquineta na loja.
Cartão e Pix vão direto para ele, sem passar pela gente; só o **dinheiro** fica na gaveta. No fim
do dia o caixa tira o borderô da maquineta e lança o total em dinheiro, senão sobra dinheiro na
contagem. O financeiro acompanha o que deve a cada maquineta e paga o parceiro de tempos em tempos.

1. **Cada maquineta é um item do caixa** ("Bilhete Agora — Centro 1"). O item tem um modo:
   **cédula** (chips: conta como cédula, como acima) ou **maquineta de parceiro** (sem estoque,
   nunca entra na contagem). O totalizador é por maquineta: o parceiro pode ter várias na mesma
   filial, e a mesma maquineta pode ser usada em dois caixas.
2. **Cadastro da maquineta = o mínimo para gerar o título**: nome, parceiro (pessoa), filial e
   conta contábil do título. O modo não muda depois que o item foi lançado em caixa.
3. **Borderô** (só na tela do período do contas; o PDV ganha na refatoração dele): a maquineta, a
   data, o **total em dinheiro** (com sinal: negativo quando a maquineta devolveu dinheiro), a
   observação e a **foto do borderô, opcional**. Sem foto a linha mostra "sem borderô"; a foto pode
   ser anexada depois na linha (mesmo com o período fechado: não muda valor) e a errada, excluída
   ali mesmo (quem pode anexar). Só as maquinetas da filial do caixa: o diálogo lista só elas e o
   servidor recusa a de outra filial. Cartão e Pix só na foto. Sobe o saldo do portador (explica o
   dinheiro a mais) e é crédito na conta da maquineta.
   Cancela-se com justificativa, como o ajuste.
4. **Conta corrente por maquineta** (não há "acerto que abrange um período"): crédito = os borderôs
   dos caixas; débito = os títulos gerados; ajuste com sinal e observação obrigatória (a comissão
   que o parceiro desconta, como a da Rede Card; o saldo que já devíamos no go-live; diferença com
   o relatório do parceiro). Saldo = o que devemos ao parceiro. A comissão só abate: não vira
   título a receber. Quem decide comissão é o parceiro; o sistema não tem regra. O extrato anda
   por **semana (domingo a sábado**, a Redeflex fecha no sábado; abre na semana atual), mês ou
   período personalizado; o servidor calcula o período, o saldo anterior e os saldos (07/10/2026).
5. **Gerar título** (botão na conta corrente da maquineta): data do fechamento do parceiro (a
   Redeflex fecha no sábado e o título sai na segunda; é a data do débito no extrato e a transação
   e o número do título; emissão é hoje), valor (sugere o saldo) e vencimento (sugere hoje) →
   título a pagar (Duplicata a Pagar) para o parceiro, com a filial e a conta da maquineta, em
   aberto e sem portador, pago pelo caminho normal do contas; o débito fica ligado ao título. Um
   título por maquineta (pagar vários juntos = liquidação de vários títulos). **O título é da
   conta corrente** (07/10/2026), como o do negócio é do negócio: a tela de títulos não o estorna
   nem muda número, valor e datas (mostra "Repasse da maquineta X" com o link); **cancelar o
   débito estorna o título junto**. Título já pago (total ou parte) recusa: desfaça o pagamento
   antes.
6. **Quem**: cadastro e conta corrente, Administrador e Financeiro; borderô, quem opera o portador
   (como ajuste e item).
7. **Bloquinho de ingresso com maquineta** (o bloquinho como cédula e a maquineta pelo borderô, no
   mesmo item): não existe hoje; saiu do escopo.
8. **Saldo na lista** (07/10/2026, TASK-39 #23, a fazer): a lista de Itens do Caixa mostra o saldo a
   pagar de cada maquineta, para o financeiro não abrir uma por uma.
9. **Até a refatoração do PDV** (07/10/2026): o borderô só se lança na tela do portador do
   contas, que não abre para o grupo Caixa (só Administrador, Financeiro e Gerente). Quem tem
   acesso lança o borderô antes de fechar a gaveta; fechada pelo PDV sem ele, o dinheiro da
   maquineta aparece como sobra. O botão na tela do caixa do PDV, para o próprio caixa lançar, é o
   M9.7 da TASK-188 (o `MaquinetaCaixaDialog` já é compartilhado).
10. **Virada** (07/10/2026): depois do `caixa_item_maquineta.sql`, as maquinetas reais (parceiro,
    filial, conta) e o saldo inicial de cada uma (ajuste "saldo inicial" na conta corrente) são
    cadastrados pela tela, pelo Fábio ou pelo financeiro; sem script.
11. **Leitura do valor pela foto** (TASK-200, opcional): uma LLM com visão sugere o valor em
    dinheiro a partir da foto do borderô; o caixa confere antes de lançar.

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
- **Front**: `@components/caixa/ItemCaixaDialog` (Entrada / Saída como primeiro campo, linhas
  novas com typeahead) e `LinhasItemCaixa` (um campo por preço), `ContagemCaixa` com os itens,
  `periodoStore.lancarItem`;
  contas: botão "Entrada ou saída de item" nos lançamentos de todo portador em espécie, itens no
  diálogo da contagem, cadastro só com o nome, tela do item (`caixaItem/Detalhe` e
  `CaixaItemFechamentosDialog`, com a coluna e o card "Entradas e saídas"; o campo continua
  `entradas` no JSON), "Movimento dos Itens" removido. A saída ficou visível em 07/10/2026: o
  botão dizia só "Entrada de item" e o rádio de Saída ficava escondido abaixo do item.

### Valida (itens do caixa)

1. contas → Cadastros → Itens do Caixa: criar e editar pedem só o nome; clicar no item abre a
   tela dele.
2. contas → Portadores → um portador em espécie (gaveta, cofre, troco) → período aberto: botão
   `style` (Entrada ou saída de item) nos lançamentos → Entrada → escolher o item → descrição
   (digitar "Cl" sugere "Claro"), preço 10,00, quantidade 10 → Lançar. Saldo final sobe
   R$ 100,00; linha "Entrada: Chips de celular" na linha do tempo; resumo "Itens do caixa".
3. Saída sem venda, no mesmo botão com Saída marcada (ex.: o bloco de 25 ingressos de R$ 40,00
   da Brígida recolhido): saldo desce R$ 1.000,00; linha "Saída: …" em vermelho.
4. Contagem final (botão ao lado do saldo final): cédulas + o bloco do item com o campo
   "R$ 10,00" já listado; contar o que sobrou. Total geral = cédulas + itens; diferença uma só.
5. Venda em dinheiro de um item: nada lançado; contar um a menos e R$ 10,00 a mais → mesma
   diferença.
6. Cancelar a entrada (na linha): saldo volta; aparece em "Mostrar cancelados".
7. Fechar e abrir o seguinte: contagem inicial já vem com os itens.
8. Contar zero, fechar e abrir o seguinte: o item some da contagem. Borderô do período mostra os
   itens na contagem.
9. PDV `/caixa`: sem o bloco de itens; abre e fecha como antes.
10. Tela do item: "Saldo nos caixas" com cada portador que tem o item (contagem do último
    fechamento + entradas e saídas do período aberto) e o total; clicar no caixa abre o popup
    dos períodos em que o item mexeu, com os cards Saldo, Entradas e saídas e Diferença (entradas
    e saídas + diferença = saldo; a saída entra na mesma coluna, negativa); "Descrições" troca o
    texto de um tipo (descrição + preço) em tudo.

### Como ficou no código: maquinetas de parceiro (06–07/10/2026; validado pelo Fábio em 07/10)

- **DDL** `api/database/caixa_item_maquineta.sql` (idempotente; rodado no dev; roda no go-live
  depois do `caixa_item_dinamico.sql`): `tblcaixaitem.modo` (C/M) com `codpessoa`, `codfilial`,
  `codcontacontabil` (check: C sem os três, M com os três); tipo **M** no check do
  `tblportadormovimento` (com `codcaixaitem`, sem `itens`, estado E/C); tabela nova
  `tblcaixaitemacerto` (tipo T título com `codtitulo` e valor negativo, A ajuste com observação;
  cancelamento com justificativa).
- **Backend**: `CaixaItem` (`MODO_CEDULA`/`MODO_MAQUINETA`, `ehMaquineta`), `CaixaItemAcerto`;
  `CaixaItemService` (modo no salvar, recusa trocar o modo de item lançado, `ativos($modo)`,
  `exigirCedula` em saldos/períodos/tipos/descrições, contagem recusa maquineta);
  `CaixaItemContaService` (`saldo`, `extrato`, `gerarTitulo` via `TituloService::criar`,
  `ajustar`, `cancelar`); rotas `GET v1/caixa-item/{id}/conta`, `POST .../conta/titulo`,
  `POST .../conta/ajuste`, `POST v1/caixa-item-acerto/{id}/cancelar`.
  `PortadorLancamentoService::lancarMaquineta` (recusa maquineta de outra filial),
  `anexarFoto`/`excluirFoto`/`fotos`/`mostrarFoto` (disco
  `negocio-anexo`, pasta `portador-movimento/{cod}`, sem coluna; a foto do borderô, do lote e da
  maquineta de parceiro, grava e lê por `NegocioAnexoService::gravarFoto/fotos/mostrarFoto`);
  `lancarItem` recusa maquineta; cancelar aceita o M. As rotas da conta corrente devolvem o
  extrato de/até já com o que mudou. Rotas `POST v1/portador-periodo/{id}/maquineta`,
  `POST v1/portador-movimento/{id}/foto`, `GET/DELETE .../foto/{arquivo}`.
  `PortadorPeriodoResource`: origem M "Maquinetas de parceiros" no resumo (e no borderô impresso),
  linha "Borderô: …"/"Devolução: …" com `fotos`, `semBordero`, `podeAnexar`; `itens` só os de
  cédula; `maquinetas` ativas da filial do portador para o diálogo.
  `CaixaService::dinheiro` com o documento M.
- **Front**: `@components/caixa/MaquinetaCaixaDialog` (borderô, com `MgSlim` para a foto) e
  `BorderoFotosDialog` (ver, anexar e excluir; o quadro de fotografar do tamanho das fotos, nos
  dois diálogos); `periodoStore.lancarMaquineta/anexarFotoBordero/excluirFotoBordero` e os
  helpers `limitePeriodo`/`dentroDoPeriodo` (a data dentro do período, usada pelos diálogos de
  ajuste, item, maquineta e transferência); contas:
  botão `point_of_sale` nos lançamentos do período em espécie, badge "sem borderô" e câmera na
  linha; cadastro com o modo e, na maquineta, parceiro, filial e conta; tela do item da maquineta
  com o saldo a pagar no cabeçalho e a conta corrente no mesmo desenho do extrato do período
  (`CaixaItemContaCorrente`, `CaixaItemTituloDialog`, `CaixaItemAjusteDialog`); lista dos itens
  mostra o parceiro da maquineta. O modo do item não muda depois de qualquer lançamento (caixa
  ou conta corrente).
- **07/10/2026, depois do teste**: o título leva a data do fechamento do parceiro (débito no
  extrato, transação e número do título); o título de maquineta só se estorna cancelando o débito
  na conta corrente (`Titulo::geradoAutomaticamente`, `CaixaItemAcerto` ligado; a tela de títulos
  recusa e trava número, valor e datas); o extrato anda por semana/mês/personalizado com o período
  calculado no servidor (`CaixaItemContaService::periodo`, `modo`/`data`/`passo` ou `de`/`ate`).

### Valida (maquinetas de parceiro)

1. contas → Itens do Caixa → Novo: modo "Maquineta de parceiro" ("Rede Card Centro"), com parceiro,
   filial e conta; sem parceiro não salva. O chip continua cédula.
2. Portador em espécie → período aberto → botão `point_of_sale` (Borderô de maquineta): R$ 350,00
   com foto. Linha "Borderô: Rede Card Centro", saldo +350, resumo "Maquinetas de parceiros".
3. Outro borderô, Devolução R$ 20,00, sem foto: badge "sem borderô"; câmera na linha → anexar → o
   badge some. Lixeira numa foto → confirmar → some; sem nenhuma, o badge volta. O diálogo só
   lista as maquinetas da filial do caixa.
4. O diálogo do item e a contagem não mostram a maquineta; contar os 330 a mais nas cédulas →
   diferença 0.
5. Cancelar a devolução com justificativa: saldo volta; aparece em Mostrar cancelados.
6. Itens do Caixa → Rede Card Centro: conta corrente com o crédito de 350 (clicar leva ao caixa).
   Ajuste "Diminui" 15,00 "comissão" → saldo 335; ajuste "Aumenta" 100,00 → 435; cancelar este →
   335 e aparece em Mostrar cancelados.
7. Gerar título: sugere 335 e hoje → título a pagar com parceiro, filial e conta da maquineta, sem
   portador; débito −335, saldo 0; clicar no débito abre o título.
8. Cancelar o débito do título: recusa ("Estorne o título … antes"). Estornar o título, voltar e
   cancelar → saldo 335.
9. Período fechado recusa borderô novo; usuário fora de Administrador e Financeiro não abre a
   conta corrente.

### Saldo dos itens na lista (TASK-39 #23; decidido com o Fábio em 07/10/2026)

1. A lista de Itens do Caixa tem um card para a cédula (chips, ingressos) e **um card por parceiro**
   (as maquinetas agrupadas pela pessoa, o nome no cabeçalho), três por linha (`col-12 col-sm-4`).
2. À direita de cada linha, o saldo: cédula com **quantidade e valor** nos caixas (somando os
   caixas, a mesma base da tela do item); maquineta com o **saldo a pagar** (R$ 0,00 também,
   vermelho se negativo). **Total** no fim de cada card, só do que aparece.
3. **Mostrar inativos**: um toggle em cada card.
4. O saldo é **gravado** na `tblcaixaitem` e recalculado **só nas ações de item**: venda no PDV não
   recalcula (o `recalcular` do portador roda em toda venda em dinheiro).
5. Botão **Recalcular** na lista (todos) e na tela do item (o item): go-live e correção.

### Como ficou no código: saldo dos itens (07/10/2026; validado pelo Fábio em 07/10)

- **DDL** `api/database/caixa_item_saldo.sql` (idempotente; rodado no dev; roda no go-live depois do
  `caixa_item_maquineta.sql`, e depois dele o Recalcular da lista): `tblcaixaitem.saldo`
  (`numeric(14,2)`, default 0) e `saldoquantidade` (só cédula).
- **Backend**: `CaixaItemService::recalcularSaldo` (trava o item; cédula = soma de `saldos()`,
  maquineta = `CaixaItemContaService::saldo`; grava sem o MgModel, não é alteração do cadastro) e
  `recalcularSaldos(?codportador)` (os itens que já mexeram naquele caixa, ou todos; em ordem de
  código). Chamado no `lancarItem`, `cancelarItem` e `lancarMaquineta` do
  `PortadorLancamentoService`; no `contar`, `fecharCaixa`, `reabrirCaixa`, `dividir`, `unificar` e
  `editarDatas` do `PortadorPeriodoService`; no `gerarTitulo`, `ajustar` e `cancelar` do
  `CaixaItemContaService`. Rotas `POST v1/caixa-item/saldo/recalcular` (devolve a lista) e
  `POST v1/caixa-item/{id}/saldo/recalcular`. `CaixaItemResource` com `saldo` e `saldoquantidade`.
- **Front** (contas): `caixaItem/Index.vue` com o card da cédula, um por parceiro e o `refresh` no topo;
  `Detalhe.vue` com o `refresh` no cabeçalho; `caixaItemStore.recalcularSaldos/recalcularSaldo`.

### Valida (saldo dos itens)

1. contas → Itens do Caixa (F5) → Recalcular: chips e ingressos com quantidade e valor; "Bilhete
   Agora Centro" R$ 632,00; total de cada card.
2. Os números de cada chip batem com o total "Saldo nos caixas" da tela do item.
3. Toggle "Inativos" em cada card: o inativo aparece riscado e entra no total.
4. Período aberto: entrada de chip → a lista soma; cancelar → volta.
5. Maquineta: ajuste "Diminui" 10,00 → a lista mostra 622,00; cancelar o ajuste → 632,00.

## Caixa do PDV (06/10/2026, com o Fábio; TASK-188 M9.1, M9.5, M9.7)

A tela do caixa do negocios (`/caixa`) era o `MgCaixaSessao` do M13: cards de avulso e de
transferência, tabela de dinheiro própria, abrir e fechar com a contagem embutida, sem itens nem
borderô de maquineta. Passa a ser **a mesma tela do período do contas**, só com o **período aberto**
da gaveta do PDV. Esta seção manda sobre o "até a refatoração do PDV" das seções acima (itens,
item 7; parceiros, item 9).

### Decisões

1. **O PDV não fecha nada.** Quem fecha é sempre o gerente, no contas. A regra do fechamento não
   muda (recusa com transferência a confirmar, chegando ou saindo): o gerente também confirma a
   sangria.
2. **Abrir**: o caixa no PDV (só confirma) ou o gerente no contas (aba "Novo período").
3. **Contagens**: inicial e final pelos botões do resumo, com os itens, como no contas.
4. **Sem período aberto**: só "Abrir caixa". Os fechados e o histórico ficam no contas.
5. **Lançamentos no PDV**: só **Reforço / Sangria** (a confirmar pelo gestor do destino) e
   **Borderô de maquineta** (com foto; câmera na linha). Ajuste e entrada/saída de item, só no contas.
6. **Cancelar no PDV**: na linha, com justificativa, a sangria/reforço ainda a confirmar e o borderô
   de maquineta, com o período aberto. Confirmada, só o gerente no contas.
7. **Borderô**: botão "Imprimir borderô" no período aberto; térmica do PDV, sem impressora o PDF.
8. **Link da linha, igual nos dois apps**: com negócio, o negócio; sem negócio, o pagamento. Dentro
   do app `:to`, no outro `:href` (`NEGOCIOS_URL` / `CONTAS_URL`).
9. **Fechamentos → "Caixas abertos" saiu**: o gerente vê as gavetas em Movimento → Portadores.

### Como ficou no código (06/10/2026; não validado)

- **Front**: `PeriodoCabecalho`, `PeriodoResumo` e `PeriodoLancamentos` foram para
  `@components/portador/`, e o corpo do período (cabeçalho e resumo, lançamentos e os diálogos de
  movimento) virou `@components/portador/Periodo.vue`, usado pelo `Detalhe.vue` do contas e pela
  `CaixaPage.vue` do negocios. `periodoStore.carregar(cod, codperiodo,
  { codpdv, impressora })`; com o `codpdv` (getter `pdv`) o cabeçalho troca o Fechar por Imprimir
  borderô e a lista esconde ajuste e item; `imprimirBordero()`; `CEDULAS`, `MOEDAS` e `MOTIVOS`
  vieram do `caixaSessaoStore`. `negocios/src/pages/CaixaPage.vue` monta a tela. Saíram
  `MgCaixaSessao`, `caixaSessaoStore`, `usar`/`aoMudar` do store e `contas/pages/fechamento/Sessao.vue`.
- **Backend**: `PortadorAutorizador::livre()` (a gaveta do `codpdv` do request: o PDV não valida o
  papel nela) usado pela tela, abrir, contar, borderô (`GET` e `POST
  v1/portador-periodo/{id}/bordero/{impressora}`), transferir, maquineta, foto e cancelar. Com o
  `codpdv`: a tela devolve papel operador sem ações de gestor; cancelar só aceita
  `PortadorLancamentoService::cancelaNoPdv` (transferência a confirmar e borderô); ajuste e item
  não aceitam mais o `codpdv`. `PortadorPeriodoResource`: `codnegocio` na linha e, no PDV, sem
  `podeConfirmar`. Saíram `SessaoResource`, os métodos do `CaixaController` (ficam `status` e o PDF
  assinado da impressora), as rotas `v1/caixa/gaveta|sessao|avulso`, `CaixaService::envelope/
  podeOperar/autorizarOperar` e a pendência `sessao` do `ConferenciaService`. De passagem: a
  transferência em período fechado não mostra mais o cancelar (o servidor já recusava).
- **Borderô da Bilhete Agora, Redeflex etc.**: é o botão `point_of_sale` dos lançamentos, que só
  aparece com maquineta de parceiro cadastrada (contas → Itens do Caixa → Novo, modo "Maquineta de
  parceiro", uma por maquineta). No dev, em 06/10/2026, não havia nenhuma.
- **Conferido em 06/10/2026** (Chrome headless, PDV 508 ligado a uma gaveta e um cofre temporários,
  usuário Caixa sem papel na gaveta e o fabio no contas; tudo apagado e o PDV devolvido à gaveta
  202075 no fim): abrir no PDV; contagem inicial e final; duas sangrias a confirmar, cancelar uma;
  diálogo do borderô com as maquinetas (simuladas no navegador); imprimir (PDF); "Ver no cofre" e
  pagamento abrindo no contas em outra aba; no contas venda → negócio (outra aba) e título →
  pagamento; confirmar a sangria pelo cofre; fechar; reabrir; ajuste e item no contas; PDV sem
  gaveta; celular nos dois apps. Servidor (tinker com rollback): borderô, cancelar e anexar pelo
  PDV, ajuste e item recusados com o `codpdv`, sangria já confirmada não se cancela no PDV.
- **Em aberto (decidir)**: o caixa não consegue registrar **reforço** no PDV, porque tirar do cofre
  exige operador do cofre e o caixa é só depositante (a lista de origem vem "Nenhum portador"). Hoje
  o reforço é lançado pelo gerente.

### Valida (caixa do PDV)

1. negocios `/caixa` (PDV 508) com um usuário do grupo Caixa: sem período aberto, só "Abrir caixa";
   abrir; contagem inicial já preenchida, com os itens.
2. Venda em dinheiro no wizard → linha "Venda nº", que abre o negócio.
3. Borderô de maquineta com foto; outro sem foto → anexar pela câmera da linha.
4. Sangria para o cofre → amarela, a confirmar; cancelar uma pelo PDV.
5. Contagem final; Imprimir borderô (PDF sem impressora).
6. Não aparecem Fechar, Ajuste nem Entrada/saída de item.
7. contas `/portador/{gaveta}/{período}` como gerente: as mesmas linhas; venda abre o negócio
   (outra aba do negocios), título abre o pagamento; confirmar a sangria pelo cofre; fechar a
   gaveta → o PDV volta a "Abrir caixa".
8. Fechamentos sem "Caixas abertos".

## Maquineta e seus períodos (07/10/2026, com o Fábio; TASK-188 M9.8)

**Por quê**: todo dia o gerente da unidade confere o que passou em cada maquineta de cartão
(venda, recebimento de título, adiantamento) com o borderô do dia impresso por ela, e anexa a
foto. É assim que se pega o caixa que lançou errado ou fake. Não é o que cai no banco (isso é a
TASK-195, M14). O lote da maquineta do M9 já fazia a conta, mas na tela Fechamentos ("lança e
some; errou, não tem como desfazer"). A tela passa a seguir o padrão do portador e seus períodos:
o passado e o momento, lançar e alterar no mesmo lugar.

### Decisões

1. **Cada maquineta tem uma tela com os períodos em abas** Ano → Mês → Período (o lote do M9 é o
   período; no banco continua `tblmaquinetalote`). A lista de Maquinetas mostra a situação (aberto
   desde, pendentes, sem borderô) e o nome leva à tela. O grupo Maquinetas sai de Fechamentos.
2. **Aberto → pendente → conferido.** O cartão cai no período aberto. Conferir com o borderô
   (crédito e débito digitados): o aberto termina na hora e abre o seguinte; bateu no centavo com o
   sistema, conferido; não bateu, pendente (sem tolerância). O mesmo botão confere de novo.
3. **Visão aberta**: lançamentos e total do sistema visíveis sempre (sem conferência às cegas).
   Quem está sendo conferido é o caixa; a foto é a prova.
4. **Foto opcional, com aviso**: conferir sem foto deixa o período "sem borderô" (aba, cabeçalho e
   lista de Maquinetas) até anexar; anexa a qualquer hora, inclusive no conferido.
5. **Acertar o período, igual ao caixa**: dividir (régua com os lançamentos), unificar com o
   anterior (os dois não conferidos; vale com diferença), início e fim, e mover um lançamento de
   período (na correção da linha). **O período manda, não a hora**: o lançamento movido fica no
   período mesmo fora do início e fim, e editar as datas não recusa por isso (resolve também a venda
   offline do PDV, gravada na hora da sincronização). Caso típico: o caixa fechou, alguém vendeu
   depois na mesma maquineta, a venda caiu no período de hoje → divide hoje isolando a venda e
   unifica o pedaço com ontem (ou move a venda).
6. **Reabrir** qualquer período conferido, em qualquer ordem (cartão não tem saldo encadeado): volta
   a pendente.
7. **Quem**: Gerente da filial, Financeiro e Administrador; maquineta compartilhada (as virtuais
   Le Card Site, MultVale Site, Brasil Card Site), qualquer gerente. As virtuais têm período como
   as outras; às vezes são conferidas por semana (o período fica aberto até alguém conferir).
8. **Correções na linha** (as do M9): corrigir crédito/débito, maquineta, período, bandeira,
   autorização, parcelas e valor; registro indevido. Só no período não conferido.
9. **Fica para depois**: a dashboard de pendências do gerente (unidade) e do financeiro (geral), que
   leva cada item à tela onde ele está (TASK-203). O resto de Fechamentos (cheque, vale, duplicata,
   venda com diferença, PIX) continua como está.

### Como ficou no código (07/10/2026; não validado)

- **DDL** `api/database/maquineta_periodo.sql` (idempotente; rodado 2x no dev; go-live depois do
  `caixa_item_saldo.sql`): `tblmaquinetalote.fim`; conferido antigo ganha `fim = fechamento`; o
  índice do aberto passa a ser `fim is null`. `abertura` é o início.
- **Backend**: `MaquinetaLote` (`fim`, `situacao()` aberto/pendente/conferido; `aberto()` = sem
  fim). `MaquinetaLoteService`: `corrente` (o sem fim; nasce no fim+1s do último), `conferir`,
  `reabrir`, `editarDatas`, `dividir` (cartão por `transacao`, cancelamento por `cancelamento`
  depois do corte; o borderô digitado e as fotos vão para a segunda parte), `unificar` (fica o
  anterior, com o fim e o borderô do posterior; sem ele, o do anterior; as fotos vão junto),
  `exigirNaoConferido` (no lugar do `exigirAberto`, usado nas correções), `totais` (as abas numa
  consulta), `resumo` (a lista de Maquinetas), `comFoto`. `MaquinetaLoteResource` mudou para
  `Mg/Maquineta` (sem `comLancamentos`: as abas com `total` e `semBordero`; com: sistema por PDV,
  fotos, anterior e lançamentos). `MaquinetaPeriodoController`: `GET
  v1/maquineta/{cod}/periodo/{codlote?}` (a tela), `GET v1/maquineta/{cod}/lote` (não conferidos,
  destino do mover), `POST v1/maquineta-lote/{id}/conferir|reabrir|datas|dividir|unificar|foto`,
  `GET v1/maquineta-lote/{id}/foto/{arquivo}`; toda ação devolve a tela. Saíram as rotas
  `v1/conferencia/lote*` e o bloco dos lotes de `ConferenciaService::pendencias`.
  `MaquinetaController::index` traz `periodos` (aberto desde, pendentes, sem borderô nos últimos 60
  dias). `ConferenciaPagamentoResource` ganhou `cancelamento`.
- **Front (contas)**: `pages/maquineta/Detalhe.vue` (rota `maquineta-detalhe`,
  `maquineta/:codmaquineta/:codmaquinetalote?`), `components/maquineta/PeriodoCabecalho.vue`
  (situação, ações, borderô × sistema, por PDV, foto) e `PeriodoLancamentos.vue` (extrato por dia,
  com acumulado; corrigir e indevido na linha), `components/maquineta/linhas.js` (as linhas com o
  sinal do sistema), `stores/maquinetaPeriodoStore.js`. Lista de Maquinetas com a coluna Período e
  o nome como link. `CorrecaoPagamentoDialog`: "Período" no lugar de "Lote". Saíram
  `pages/fechamento/Lote.vue`, `pages/maquineta/Lotes.vue`, as rotas `fechamento-lote` e
  `maquineta-lotes` e as funções de lote do `conferenciaStore`.
- Conferido: `php -l`; cenário no tinker com rollback (16 verificações: dividir, conferir batendo e
  não batendo, abrir o seguinte, cartão novo no aberto, mover para pendente, correção no conferido
  recusada, unificar com conferido recusado, reabrir, unificar, conferir de novo, datas invadindo e
  no futuro recusadas, a tela e a lista); eslint e prettier; `quasar build` do contas.
- **Foto do borderô fora de Negócio** (07/10/2026, com o Fábio): as fotos do borderô estavam no
  disco `negocio-anexo`, pelo `NegocioAnexoService` (pastas `maquineta-lote/…` e
  `portador-movimento/…` no meio dos anexos de negócio). Agora:
  - `Mg\Anexo\FotoService`, genérico: `fotos`, `gravar`, `mostrar`, `excluir` e `jpeg`; recebe o
    disco e a pasta, não sabe de quem é a foto.
  - Um disco por dono, como o `pessoa-anexo`: `maquineta-anexo` (`MAQUINETA_ANEXO_PATH`, pasta =
    `codmaquinetalote`) e `portador-anexo` (`PORTADOR_ANEXO_PATH`, pasta = `codportadormovimento`).
    Caminhos: produção `/opt/www/Arquivos/Maquinetas` e `/opt/www/Arquivos/Portadores`; dev com
    `/Anexos` no fim, como o `Pessoas/Anexos` de lá.
  - `NegocioAnexoService` ficou só com o que é de negócio (o `jpeg` repassa para o `FotoService`).
  - Excluir a foto: `DELETE v1/maquineta-lote/{id}/foto/{arquivo}`, vale também no conferido; na
    tela, a lixeira na miniatura, com confirmação. Sem nenhuma foto, volta o "sem borderô".
  - **Go-live**: variáveis e pastas já criadas em produção (07/10/2026); depois do deploy, `php
    artisan optimize` (as rotas estão em cache). As fotos do dev já foram movidas.

### Valida (maquineta e seus períodos)

1. contas → Maquinetas: a coluna Período ("Aberto desde …"); clicar no nome abre a maquineta.
2. Venda no cartão com essa maquineta no PDV → aparece no período aberto (aba "aberto" com o total).
3. Conferir com o borderô (`fact_check`) digitando um valor diferente → Pendente, com a diferença
   em vermelho, e nasce um período aberto novo.
4. Nova venda na maquineta → cai no período novo.
5. No pendente: corrigir um lançamento (ou mover para outro período) e Conferir de novo com o valor
   certo → Conferido.
6. Conferido sem foto: "sem borderô" no cabeçalho, na aba e na lista. Fotografar → o selo some.
   Lixeira na miniatura → confirma → a foto some; excluindo todas, o "sem borderô" volta.
7. Reabrir → Pendente. Dividir o período aberto numa hora → a primeira parte pendente; unificar
   essa parte com o anterior → um período só, com o borderô do anterior.
8. Início e fim de um período pendente; abas de meses e anos pela URL.
9. Fechamentos não mostra mais as maquinetas; o resto continua.

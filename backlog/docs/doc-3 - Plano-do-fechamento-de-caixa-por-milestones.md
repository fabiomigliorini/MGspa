---
id: doc-3
title: Plano do fechamento de caixa por milestones
type: other
created_date: '2026-09-28 20:00'
---

# Fechamento de caixa — plano por milestones (TASK-39 + duas tasks irmãs)

## Como usar este plano (para conversas futuras)

Este arquivo é a fonte de verdade do desenho. Cada milestone será executado numa **conversa
separada**, referenciando este arquivo e o milestone pelo nome (ex.: "executa o M0.1 do plano em
`backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md`"). Ao iniciar uma conversa de milestone:

1. Ler este arquivo inteiro (Decisões, Convenções, Glossário e o milestone pedido), depois o backlog:
   `./backlog.sh task view TASK-39` e as duas tasks irmãs (criadas na conversa do M0 — ver seção
   Backlog). Este arquivo está versionado no repositório (`./backlog.sh doc view doc-3`); não copiar para
   outro lugar.
2. Conferir no banco e no código o que o milestone assume (as consultas usadas no levantamento
   estão nas seções "Levantamento" e "Convenções"); o que mudou desde o plano é relatado antes de
   codar, não corrigido em silêncio.
3. Marcar a task In Progress, executar só o milestone pedido (DDL em dev, backend, frontend), deixar
   na árvore de trabalho sem commit, e entregar o roteiro "Valida" do milestone para o Fábio testar.
4. Commit só depois do OK explícito, um por milestone, com os `.md` do backlog no mesmo commit.

**Pendências que o Fábio decide no go-live** (não são gaps do plano): quais dos dois portadores da
filial 101 (100 Caixa Financeiro, 101001 Caixa Atacado) são cofre; pessoa e conta contábil de cada
item do caixa; conferir os nomes das 17 gavetas geradas dos PDVs; rodar os scripts DDL em produção.

## Glossário

- **Portador**: conta onde dinheiro fica (banco, gaveta, cofre, troco, cartão, wallet, Carteira).
- **`caixa`** (flag do portador): dinheiro em espécie. **Gaveta**: portador `caixa` com PDV apontando
  (`tblpdv.codportador`). **Cofre** e **troco**: portadores `caixa` da filial sem PDV (só o nome
  diferencia; hoje "Caixa Centro" vira o cofre do Centro).
- **Razão** (`tblportadormovimento`): toda entrada/saída de dinheiro de um portador, com vínculo à
  origem (nfp da venda, movimento de título, transferência, item, ajuste manual).
- **Período** (`tblportadorperiodo`): faixa `[inicio, fim]` de um portador que agrupa movimentos e
  guarda saldo inicial/final. **Corrente** = `fim` nulo (o de agora). **Aberto** = `fechamento` nulo.
  **Sessão** = período de gaveta (abre e fecha com contagem). **Corte** = a data que fecha um período
  corrente de cofre/banco (normalmente o fim do mês).
- **Envelope**: o que sobra na gaveta ao fechar; é o `saldofinal` da sessão e o `saldoinicial` da
  próxima.
- **Transferência**: sangria (sai da gaveta) e suprimento (entra na gaveta) são a mesma coisa vista
  de lados diferentes; sempre em dois passos (registra / confirma), com cores por estado.
- **Item do caixa**: mercadoria de parceiro fora do fiscal (chips, ingressos, maquinetas de terceiros)
  que passa pela gaveta e vira título "Repasse Parceiro" no fechamento.
- **Título com `movimentaportador`**: tipo cuja implantação já tira/põe dinheiro no portador (vale
  colaborador, adiantamento).

## Contexto

Hoje o fechamento de caixa é feito à mão, no formulário "MOVIMENTO DO CAIXA" (um por caixa por dia):
contagem inicial e final de moedas/cédulas/chips/ingressos, total de vendas à vista, recebimentos de
títulos, cartões por adquirente, PIX, sangrias (com rubrica de quem levou) e a diferença. O único apoio
do sistema é o protótipo "Totais de Caixa" do MG Lara, que só lista totais por usuário/período.

O banco já foi desenhado para "portador como razão de todo o dinheiro" e nunca foi ligado:
`tblportadormovimento`, `tblportadortransferencia` e `tblextratobancarioportadormovimento` existem
com FKs certas e **0 linhas**; `tblpdv.codportador` existe e está NULL nos 312 PDVs; venda em dinheiro
não toca portador; `tblliquidacaotitulo` tem `codpdv/codpix/codpagarmepedido/tipo/bandeira/...` vazios
em 100% das linhas. O modelo de títulos carrega colunas duplicadas, 4 triggers e catálogos com tipos
sem uso desde 2016–2024.

**Andamento**: M0.1 e M0.2 **concluídos e validados em 29/09/2026** (TASK-186, commits `7c6551a33`
e `0991fc2b3`). **Próximo: M1.1.** Os demais milestones esperam a validação do anterior.

**Formato do trabalho**: milestones. Cada um termina numa tela que o Fábio abre, testa e valida; só
então começa o próximo. Tudo que o milestone precisa (DDL, backend, frontend) entra nele; o que não é
dependente fica para o seguinte. Um commit por milestone, depois da validação e do OK explícito.

| Milestone | Task | Tela que valida |
|---|---|---|
| M0.1 Títulos: `valor`/`saldo` com sinal, sem triggers — **feito** | TASK-186 | contas → Títulos/Liquidações/Agrupamentos/Boletos; RH acerto; PDV prazo/vale/devolução |
| M0.2 Títulos: catálogos enxutos, "Vale Colaborador", "Repasse Parceiro" — **feito** | TASK-186 | contas → Cadastros → Tipos de Título / Tipos de Movimento; Novo título |
| M1.1 Receber título no PDV: dinheiro, PIX, cheque (+ portador em espécie e PDV → portador) | "Receber título no balcão" (nova) | negocios → Receber título; contas → Portadores; Config → PDV |
| M1.2 Receber título no PDV: cartão pela maquineta | idem | negocios → Receber título com PagarMe/Saurus |
| M2 Abrir/fechar caixa e venda em dinheiro (períodos + razão) | TASK-39 | negocios → Caixa; Receber → Dinheiro |
| M3 Títulos no razão (liquidação, vale/adiantamento) | TASK-39 | contas → Liquidações/Títulos; negocios → Caixa |
| M4 Transferências (sangria/suprimento) | TASK-39 | negocios → Caixa → Transferir; contas → Caixas |
| M5 Períodos no contas (financeiro fecha o mês) | TASK-39 | contas → Caixas → Períodos |
| M6 Itens do caixa e repasse ao parceiro | TASK-39 | contas → Itens do Caixa; negocios → Caixa; contas a pagar |

---

## Decisões fechadas (não reabrir)

1. **Portador = razão único.** Todo dinheiro é linha em `tblportadormovimento` (`valor > 0` entrou,
   `< 0` saiu). Mover entre portadores = `tblportadortransferencia` com um movimento por lado.
2. **`tblportador.caixa` boolean** ("dinheiro em espécie") no lugar de qualquer enum. Banco =
   `codbanco IS NOT NULL`. **Gaveta de PDV** = `caixa` **e** algum PDV autorizado/ativo aponta para
   ele (`tblpdv.codportador`) — é o PDV apontar que faz do portador uma gaveta. Cofre e troco = `caixa`
   sem PDV (só o nome diferencia). Outros (Carteira, Folha, Barter, wallets) = nem caixa nem banco →
   **o razão ignora**. Os `Caixa <Loja>` atuais viram o cofre da filial; nasce um "Troco <Loja>" por
   loja e uma gaveta por PDV `alocacao = 'C'` (17), já vinculada.
3. **PDV aponta para o portador** (`tblpdv.codportador`, N:1): o estável recebe o ponteiro do
   volátil; vários dispositivos podem compartilhar a gaveta; no futuro cabe `codportadorpix`.
4. **`tblportadorperiodo`** = faixa `[inicio, fim]` do portador; `fim` nulo = período **corrente**;
   `fechamento` nulo = **aberto** (faixa e estado são coisas separadas). Todo movimento tem
   `codportadorperiodo NOT NULL` e cai no período cuja faixa contém a data do lançamento; se está
   fechado → 422. Gaveta: o caixa abre (contagem) e fecha (contagem, corte = agora) explicitamente;
   "caixa aberto" = existe corrente aberto. Cofre/troco/banco: o corrente nasce sozinho no primeiro
   lançamento após o último corte; o financeiro fecha escolhendo a data de corte (o que ficou depois
   vai para o período novo). `saldoinicial` = snapshot na criação; `saldofinal` no fechamento.
5. **Saldo do portador** = `saldoinicial` do aberto mais antigo + Σ movimentos ativos de todos os
   abertos; sem aberto = `saldofinal` do último fechado (0 se nunca houve). Espelha `tblestoquemes`.
6. **Período fechado é imutável.** Fecha-se do mais antigo para o mais novo; reabre-se do mais novo
   para o mais antigo (para reabrir anteontem, ontem e hoje têm que estar abertos). Qualquer estorno
   ou cancelamento cujo movimento esteja em período fechado → 422 "reabra o período". Reabrir sessão
   de gaveta = Gerente/Admin; demais = Financeiro/Admin.
7. **Envelope = `saldofinal` da sessão** (vira `saldoinicial` da próxima). Diferença na abertura e no
   fechamento viram ajuste manual dentro da sessão; depois do ajuste, sistema = contado.
8. **Transferência** nasce com os dois movimentos e um estado: pendente (amarela) / confirmada
   (verde) / cancelada (vermelha). Registra quem é dono de um dos lados; confirma o dono do lado que
   não registrou; **se o dono do destino registrou, já nasce confirmada** (quem recebe atesta); mesma
   pessoa dona dos dois lados idem. Cancelar (pendente ou confirmada): qualquer dos dois donos, com
   justificativa obrigatória, inativa os dois movimentos — exige os dois períodos abertos (regra 6).
   Sem `valorconfirmado`: divergência = cancela + registra outra.
9. **Sangria/suprimento** = transferência entre quaisquer portadores que o razão acompanha, inclusive
   gaveta → gaveta. Lista de destino agrupada: "desta filial" (os `caixa` da filial, menos eu) e
   "mais opções" (Caixa Financeiro, bancos, `caixa` de outras filiais). **Gaveta de origem ou destino
   sem sessão aberta → 422**, no registro e na confirmação; na lista aparece desabilitada com motivo.
   Fechar gaveta com pendente **chegando** → 422; pendente **saindo** não trava.
10. **Quem opera**: gaveta → Caixa da filial/Gerente/Admin; cofre/troco → Gerente da filial/Admin
    (Caixa Financeiro 100 → Financeiro/Admin); banco → Financeiro/Admin. Receber dinheiro no PDV exige
    o grupo Caixa da filial do negócio (absorve TASK-48) e gaveta com sessão aberta (absorve TASK-34).
11. **Itens do caixa** (fora do fiscal: parceria, sem comissão, nunca nas nossas maquininhas): modo
    `C` contagem (chips, ingressos impressos; vender não gera lançamento) e modo `M` maquineta/
    terceiro (Bilhete Agora, BlackTicket, Redeflex, Bradesco Expresso: vendido informativo + dinheiro
    que entrou/saiu). Cada item tem parceiro (`codpessoa`) e conta contábil; **no fechamento da
    sessão** o líquido do item vira **título "Repasse Parceiro"** (a pagar; a receber se negativo),
    número `AAAA-MM-DD-P{id do período}` (+ sufixo), um por sessão por item; o financeiro agrupa e
    paga. Reabrir sessão estorna esses títulos (422 se já movimentados).
12. **Vale colaborador / adiantamento** saem do portador quando o título nasce:
    `tbltipotitulo.movimentaportador` (M0.2) substitui a gambiarra "implanta com movimento 600";
    "Vale Funcionario" → "Vale Colaborador".
13. **Receber título no balcão** (M1) vem **antes** do caixa: o fechamento só fica certo quando toda
    notinha recebida no PDV está registrada com a forma certa. Portador = forma (dinheiro → gaveta,
    PIX → banco, cheque → Carteira até o repasse, cartão → adquirente quando houver portador).
14. **Escopo do razão nas tasks**: dinheiro (venda, liquidação, vale/adiantamento, transferência,
    ajuste, item) e liquidações em banco. PIX, cartão e boleto no razão do banco/adquirente = task
    futura de conciliação; no caixa são informativos.

## Convenções confirmadas no código

- **Modelo de títulos depois do M0 (é o que vale)**: `tbltitulo`, `tblmovimentotitulo`,
  `tbltituloagrupamento` e `tblliquidacaotitulo` têm um `valor` só, com sinal (positivo = a receber,
  negativo = a pagar); `tbltitulo.saldo` na mesma convenção. Não existem mais `debito`/`credito`
  (exceto em `tblliquidacaotitulo`, mantidos só para o "Totais de Caixa" do MGLara até o M2), nem
  `debitosaldo/creditosaldo/debitototal/creditototal`, nem a coluna `sistema`, nem as 4 triggers e
  as 4 views. Todo movimento nasce por `MovimentoTituloService::lancar`, que recalcula título,
  liquidação e agrupamento; implantação é sempre tipo 100 (`TituloService::implantar`); estorno
  guarda o tipo do original e aponta para ele (`codmovimentotituloestorno`); natureza do título por
  `Titulo::ehReceber()`. `tbltipotitulo` tem `natureza` (R/P) e `movimentaportador` (true em 2 Vale
  Colaborador, 120 Adto Fornecedor, 220 Adto Cliente, 230 Crédito Cliente); tipo 953 = Repasse
  Parceiro.
- Dinheiro no portador = `−valor` do movimento de liquidação (título a receber liquida com movimento
  negativo → entrou dinheiro). Razão: uma linha por movimento de liquidação (600), por estorno dela,
  e por implantação (100)/estorno de tipo com `movimentaportador`.
- `Autorizador::pode([...], $codfilial)` sempre inclui Administrador, mas com filial exige linha do
  Admin naquela filial → "Admin ou X da filial" = `pode([]) || pode(['X'], $codfilial)`.
- `tblportadormovimento.observacoes varchar(300)` = histórico; não criar coluna.
- `throw new Exception` genérico vira 500 em prod → regras novas usam `abort(422|403, msg)`.
- nfp são gravados no PUT de sync, que **apaga** nfp ausentes; FK do razão é ON DELETE CASCADE → o
  razão da venda só nasce no `fechar` (`PdvNegocioService::fechar`, transação 328→476).
- Scripts DDL: `api/database/<nome>.sql`, padrão `vale.sql` (`\set ON_ERROR_STOP on`, `BEGIN`,
  lock/statement timeout, idempotente, FKs guardadas por `pg_constraint`, `COMMIT`). Dev:
  `docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < api/database/x.sql`; prod: Fábio, no go-live.
- Padrão de domínio: `api/app/Mg/<Dominio>/` com Model (`MgModel`), Controller (transação, `abort`),
  Service estático, Store/UpdateRequest, Resource; rotas em `api/routes/api.php` dentro de
  `auth:api` + `v1` (grupo `pdv` nas linhas 855-923; Portador 1264-1278). PDF = mPDF como
  `Mg/Vale/ValeEmitidoRelatorioService.php`. Frontend: store Pinia por domínio, `MgInput*`,
  `MgSelect*`, `MgEmptyState`, `MgInfoCriacao`, `abrirPdf`, botões de dialog `flat`.

---

## M0 — Títulos simplificados (TASK-186) — CONCLUÍDO em 29/09/2026

Mantido como registro. Os detalhes do que foi entregue e os achados estão nas notas da TASK-186.

**Levantamento (feito):** `tbltitulo` tem `debito/credito`, `debitototal/creditototal` (ninguém lê),
`debitosaldo/creditosaldo` (6 PHP + `negocios/.../FormaVale.vue`) e `saldo` (já é `debito − credito`).
Triggers: `fntbltituloai` (cria implantação com `tbltipotitulo.codtipomovimentotitulo` e o portador),
`fntbltituloau` (ajuste 200), `fntblmovimentotituloaiauad` (saldo/estornado/transacaoliquidacao +
cabeçalho da liquidação), `fntbltituloaiauad` (agrupamento). Views: `vwboleto`, `vwliquidacaotitulo`,
`vwtitulo`, `vwtituloagrupamento_baixados`. 51 tipos de título, 24 sem uso desde 2025; 21 tipos de
movimento, 5 sem uso (610/910/920/992/993); dos flags dos catálogos só `estorno` é lido;
`MovimentoTituloService::estornar` grava sempre 930; a decisão CR/DB está copiada em 5 lugares;
7 arquivos escrevem movimento, 8 criam título; ~41 PHP, 15 blades, 18 Vue leem as colunas duplicadas;
filtros `debito_de/credito_de/saldo_de/credito=1|2` são contrato com o contas. Bugs de passagem:
`BoletoRetornoService` repete `case '106','115','117'`; `_recibo-pagamento.blade.php:13-37` erra em CR;
`NfeTerceiroDuplicataResource:18` e `cheque/Detalhe.vue:296` pedem `valor` inexistente.

### M0.1 — `valor`/`saldo` com sinal, sem triggers
- **DDL `api/database/titulo_valor.sql`**: `tbltitulo.valor` e `tblmovimentotitulo.valor`
  numeric(14,2) com backfill `debito − credito`; `tblmovimentotitulo.codmovimentotituloestorno`
  (auto-FK); `tbltituloagrupamento.valor` e `tblliquidacaotitulo.valor` (total do agrupamento e
  total líquido da liquidação, mesmo backfill; a liquidação não guarda recebido e pago separados —
  qual recibo oferecer sai dos movimentos). Colunas antigas ficam nesta fase.
- **Backend**: um escritor só, `MovimentoTituloService::lancar(Titulo, int $tipo, float $valor,
  array $vinculos)` — grava `valor` **e** debito/credito (dual-write), recalcula `saldo`, `estornado`,
  `transacaoliquidacao` do título e o total da liquidação/agrupamento; `estornar()` grava o mesmo
  tipo do original + FK. Trocar os 7 escritores (`MovimentoTituloHelper`, `MovimentoTituloService`,
  `TituloService::estornar`, `Rh/AcertoService`, `Boleto/BoletoRetornoService`,
  `BoletoBb/BoletoBbService`, `Pdv/PdvNegocioPrazoService`) e os 8 criadores de título
  (`TituloService::criar` cria a implantação explicitamente; `atualizar` cria o ajuste 200).
  `Titulo::ehReceber()` substitui os 5 clones de `operacao()`. Derrubar as 4 triggers **depois** do
  dual-write. Conferência: `sum(valor) = sum(debito − credito)` por título, pessoa e portador.
- **Leitores**: ~41 PHP, 15 blades, 18 Vue migram para `valor`/`saldo`/`ehReceber`; filtros da API
  `valor_de`/`natureza`; `TituloResource` deixa de despejar colunas cruas; `FormaVale.vue` lê `saldo`.
- **Limpeza**: derrubar colunas antigas e views (após confirmar que nada vivo lê). Feita em
  título, movimento e agrupamento. **Ficam `debito`/`credito` da liquidação**: o "Totais de Caixa"
  do MGLara soma as duas colunas; saem quando essa tela sair (M2). No MGsis só a NFe de Terceiros
  está viva: os models `Titulo` e `MovimentoTitulo` de lá foram ajustados e sobem junto.
- **Coluna `sistema`**: sai de título, movimento e liquidação (era a data de gravação do sistema
  antigo; `criacao`/`alteracao` já guardam isso). `criacao` em branco recebe o valor de `sistema`
  antes de a coluna cair.
- **Valida**: contas → Títulos (novo/editar/estornar, filtros), Liquidações (nova/estornar/recibos),
  Agrupamentos, Boletos (retorno BB e Bradesco), Cheques; pessoas → RH acerto; negocios → venda a
  prazo, PIX a receber, entrega, vale (emitir e usar), devolução, cancelamento; PDFs.

### M0.2 — Catálogos enxutos
- **DDL** (mesmo script, seção 2): `tbltipotitulo.natureza` char(1) R/P (derivado de
  `pagar/receber/debito/credito`), `movimentaportador boolean` (true nos 4 tipos que implantam com 600
  + Vale), rename `2` → "Vale Colaborador", tipo novo **"Repasse Parceiro"** (P, `movimentaportador
  false`, para o M6), `inativo` nos 24 tipos sem uso; tipos de movimento 610/910/920/992/993
  inativados e os 7 flags removidos; `codtipomovimentotitulo` do tipo de título deixa de existir
  (implantação é sempre 100).
- **Como ficou**: `natureza` é o sinal (o que os flags `debito`/`credito` diziam, que saíram);
  `pagar`/`receber` ficam, porque são a carteira (cliente/fornecedor) e alimentam o filtro
  "Pagar / Receber". Inativados 23 tipos, não 24: o 946 Remessa Armazenagem fica ativo porque a
  natureza 72 aponta para ele com financeiro ligado. Os 364 movimentos sem tipo (Rubrica RH) não
  foram alterados.
- **Backend/front**: `TituloService::criar` usa `natureza` para o sinal; CRUDs `contas/pages/
  tipoTitulo`, `tipoMovimentoTitulo`, `MgSelectTipoTitulo/TipoMovimentoTitulo`, requests e resources
  refletem as colunas novas; corrigir os bugs de passagem.
- **Valida**: contas → Cadastros → Tipos de Título / Tipos de Movimento; Novo título de cada
  natureza; "Vale Colaborador" aparece com o nome novo em títulos antigos.

---

## M1 — Receber título no PDV (task "Receber título no balcão")

Cliente vem pagar título aberto; entregador volta com o pagamento da entrega ("Entrega Receber" 310).
O caixa recebe pelo wizard do PDV, e a liquidação guarda a forma. `tblliquidacaotitulo` já tem
`codpdv, codpix, codpagarmepedido, codcheque, tipo, bandeira, parcelas, autorizacao, integracao,
codpessoacartao`; falta `codsauruspedido` (M1.2).

### M1.1 — Dinheiro, PIX e cheque (+ portador em espécie, PDV → portador)
- **DDL `api/database/receber_titulo.sql`**:
  - `tblportador.caixa boolean NOT NULL DEFAULT false`; seed `true` em 100, 101001, 202002, 201001,
    202001, 202023, 202017 (Caixa Arquitetura: setar `codfilial` 501); `INSERT` "Troco <Loja>" para
    101–105; `INSERT` uma gaveta por `tblpdv` com `alocacao = 'C'`, autorizado, ativo e sem portador
    (nome = apelido, filial do PDV) + `UPDATE tblpdv.codportador`; (Fábio confirma os dois `caixa`
    da filial 101 no go-live).
  - `tblliquidacaotitulo.codsauruspedido` fica para M1.2. Verificar `tblpixcob.codnegocio`: se NOT
    NULL, tornar nullable (cobrança de liquidação não tem negócio).
- **Backend**:
  - `Portador`: `caixa` no fillable/request/resource; `ehGaveta()` (caixa && PDV autorizado ativo
    aponta); `ehBanco()`; `PortadorService::listar` filtro `caixa`; `SelectPortadorController`
    devolve `caixa`, `codbanco`, `codfilial`. `PdvService::update` (linha 499): `codportador` só
    portador `caixa` da mesma filial (422); `PdvResource` com `portador`.
  - `Mg/Pdv/PdvLiquidacaoService`: `titulosAbertos(codpessoa | numero)` (SQL cru, títulos a receber
    com saldo, da filial ou de todas); `receber(Pdv, array $dados)` → `LiquidacaoTituloService::criar`
    com `codpdv`, `tipo` (1 dinheiro, 17 PIX, 2 cheque), `codportador` (dinheiro → `$pdv->codportador`
    ou 422 "PDV sem portador"; PIX → portador da cobrança; cheque → Carteira 999), `codpix`
    (validar não usado e valor igual), `codcheque` (`PdvNegocioChequeService` reaproveitado para
    criar o cheque), `valortroco` quando dinheiro > total (troco não entra na liquidação);
    `estornar` = regra existente (Caixa: próprias, 120 min; Gerente: filial).
  - PIX: `PixService::criarPixCobPdv` sem negócio (`codnegocio` nulo) + `importarPix` não cria nfp
    quando a cob não tem negócio; o PDV faz `POST v1/pdv/liquidacao` com `codpix` quando o status é pago.
  - Rotas (`v1/pdv`): `GET titulo/abertos`, `POST liquidacao`, `POST liquidacao/{id}/estornar`,
    `GET liquidacao/{id}/recibo` (reusa `recibo-recebimento`). `LiquidacaoTituloResource`/lista do
    contas expõem `tipo`, `pdv`, `pix`, `cheque`.
- **Frontend negocios**: `components/offline/ReceberTituloDialog.vue` (teclado, mesmo esqueleto do
  `ReceberDialog`: passo 1 pessoa/número → passo 2 títulos abertos com seleção e total → passo 3 forma:
  dinheiro com troco, PIX (`FormaPix` com prop `contexto: 'liquidacao'`), cheque (`FormaCheque`));
  atalho F11 "Receber título" no `IndexPage`; store `liquidacao.js` ganha `titulosAbertos`,
  `receber`, `estornar`, `recibo`; `LiquidacaoListagemPage` mostra forma/PDV (e corrige os filtros
  quebrados de `PdvLiquidacaoService`: `codusuario`, `codliquidacao`, `integracao`). negocios →
  Config → PDV → Editar: `MgSelectPortador` filtrando `caixa` + filial.
- **Frontend contas**: Portadores: checkbox "Dinheiro em espécie", filtro, badge; trocar os
  `q-input` restantes por `MgInput`. Liquidações: coluna forma/PDV.
- **Valida**: PDV recebe notinha em dinheiro (troco), PIX (cob paga) e cheque; entrega paga na
  volta; recibo; estorno pelo caixa; contas → Liquidações mostra forma e PDV; contas → Portadores
  com `caixa`; Config → PDV vinculado.

### M1.2 — Cartão pela maquineta
- **DDL**: `tblliquidacaotitulo.codsauruspedido`; `tblpagarmepedido`/`tblsauruspedido` aceitam
  pedido sem negócio (`codnegocio` nullable + `codliquidacaotitulo`), a confirmar ao iniciar.
- **Backend**: `PagarMeService`/`SaurusService` criam/consultam pedido para "documento" negócio ou
  liquidação; ao pagar, `receber()` grava `codpagarmepedido`/`codsauruspedido`, `bandeira`,
  `parcelas`, `autorizacao`, `codpessoacartao` (adquirente), `integracao = true`; cartão manual
  (`tipo` 3/4, `autorizacao`, `serialmaquineta`).
- **Frontend**: `FormaCartao` com `contexto: 'liquidacao'` (maquinetas recentes, aguardando
  pagamento, TASK-118 continua valendo).
- **Valida**: notinha paga na Stone e na Safra; falha na maquineta; estorno; listagens. Detalhar as
  integrações ao iniciar este milestone.

---

## M2 — Abrir/fechar caixa e venda em dinheiro (TASK-39)

### DDL `api/database/caixa.sql` (seção 1)
```sql
CREATE TABLE IF NOT EXISTS tblportadorperiodo (
  codportadorperiodo             bigserial PRIMARY KEY,
  codportador                    bigint NOT NULL,
  inicio                         timestamp(0) NOT NULL,   -- gaveta: abertura; demais: corte anterior + 1s
  fim                            timestamp(0),            -- data de corte; nulo = corrente
  fechamento                     timestamp(0),            -- nulo = aberto
  codusuarioabertura             bigint,                  -- gaveta: quem abriu; demais: nulo
  codusuariofechamento           bigint,
  saldoinicial                   numeric(14,2) NOT NULL DEFAULT 0,
  saldofinal                     numeric(14,2),
  moedasabertura numeric(14,2), cedulasabertura numeric(14,2),      -- contagem (só gaveta)
  moedasfechamento numeric(14,2), cedulasfechamento numeric(14,2),
  codportadormovimentoabertura   bigint,                  -- ajuste da diferença (nulo = bateu)
  codportadormovimentofechamento bigint,
  observacoes                    varchar(300),
  criacao timestamp(0) NOT NULL DEFAULT now(), codusuariocriacao bigint,
  alteracao timestamp(0) NOT NULL DEFAULT now(), codusuarioalteracao bigint
);
CREATE UNIQUE INDEX IF NOT EXISTS unq_tblportadorperiodo_corrente ON tblportadorperiodo (codportador) WHERE fim IS NULL;
CREATE INDEX IF NOT EXISTS idx_tblportadorperiodo_portador_inicio ON tblportadorperiodo (codportador, inicio DESC);
ALTER TABLE tblportadormovimento ADD COLUMN IF NOT EXISTS codportadorperiodo bigint NOT NULL;  -- tabela vazia
CREATE INDEX IF NOT EXISTS idx_tblportadormovimento_periodo ON tblportadormovimento (codportadorperiodo) WHERE inativo IS NULL;
-- FKs guardadas: periodo -> portador, usuario x2, movimento x2; movimento -> periodo
```

### Backend
- **`Mg/Portador/PortadorPeriodo.php`** (model; accessors `corrente`, `aberto`) e
  **`PortadorPeriodoService`**:
  ```php
  corrente(int $codportador): ?PortadorPeriodo                 // fim is null
  abertos(int $codportador): Collection                       // fechamento is null, order inicio
  periodoPara(Portador $p, Carbon $lancamento, string $acao): PortadorPeriodo
    // período com inicio <= lancamento <= coalesce(fim, infinito): fechado -> 422 "Período fechado";
    // nenhum: gaveta -> 422 "Caixa fechado. Abra o caixa para {$acao}!";
    //         demais -> cria corrente (inicio = último.fim + 1s ou lancamento; saldoinicial = último.saldofinal ?? 0)
  saldo(int $codportador): float                              // abertos[0].saldoinicial + Σ ativos dos abertos | último fechado.saldofinal | 0
  saldos(array $codportadores): array
  fechar(PortadorPeriodo $per, ?Carbon $corte): PortadorPeriodo
    // 422 se fechado ou se há aberto mais antigo; se corrente: fim = corte (gaveta: now), movimentos após o corte
    // vão para o corrente novo (inicio = corte + 1s, saldoinicial = saldofinal); saldofinal = saldoinicial + Σ; empurra saldoinicial do seguinte
  reabrir(PortadorPeriodo $per): PortadorPeriodo             // 422 se há fechado mais novo; fechamento = null
  listar(array $f)                                            // codfilial, codportador, de, ate, situacao
  ```
- **`Mg/Portador/PortadorMovimentoService`**: `lancar(Portador, float $valor, Carbon $lancamento,
  string $obs, array $vinculos)` (usa `periodoPara`; ignora portador que não é caixa nem banco →
  null), `inativar(mov)` (422 se período fechado), `lancarMovimentoTitulo(MovimentoTitulo)` e
  `inativarDoTitulo(Titulo)` (usados no M3).
- **`Mg/Caixa/CaixaAutorizador`**: `podeOperar(Portador)` conforme decisão 10; `autoriza()`.
- **`Mg/Caixa/CaixaService`** (sessão = período corrente de gaveta):
  ```php
  portadorDoPdv(Pdv): Portador                 // 422 se nulo ou !ehGaveta
  abrir(Portador, array $d): PortadorPeriodo   // autoriza; lockForUpdate; 422 se corrente existe; cria (inicio now, saldoinicial = saldo());
                                               // contado = moedas + cedulas; dif -> lancar('Diferença na abertura do caixa', manual) -> codportadormovimentoabertura
  fechar(PortadorPeriodo, array $d)            // autoriza; 422 se não é corrente/aberto ou há aberto mais antigo; dif -> 'Diferença no fechamento'; PortadorPeriodoService::fechar(now)
  reabrir(PortadorPeriodo)                     // Gerente/Admin; PortadorPeriodoService::reabrir; inativa MovimentoFechamento; limpa contagem de fechamento
  resumo(PortadorPeriodo): array               // SQL abaixo
  lancamentos(PortadorPeriodo): array
  lancamentoAvulso(PortadorPeriodo, 'E'|'S', float, string $historico)
  excluirLancamentoAvulso(PortadorPeriodo, PortadorMovimento)   // só manual sem vínculo, não ajuste, período aberto
  validarDinheiroNegocio(Negocio, Pdv): void   // nfp tipo 1? -> portadorDoPdv; 403 se !(admin || pode(['Caixa','Gerente'], $n->codfilial)); corrente aberto ou 422
  lancarDinheiroNegocio(Negocio, Pdv): void    // por nfp tipo 1: (valorpagamento − coalesce(valortroco,0)) × (codoperacao == 2 ? 1 : −1), "Venda #cod"
  estornarDinheiroNegocio(Negocio): void       // inativar os movimentos das nfp (422 se período fechado)
  ```
- **Encaixes**: `PdvNegocioService::fechar` — `validarDinheiroNegocio` após limite de crédito (424),
  antes de marcar fechado (442); `lancarDinheiroNegocio` após `emitirCreditos` (468), antes do commit.
  `::cancelar` — `estornarDinheiroNegocio` após `PdvNegocioChequeService::cancelar` (550).
- **`Mg/Caixa/CaixaController`** (`v1/pdv`, `PdvRequest`): `GET caixa` (status), `POST caixa/abrir`,
  `POST caixa/fechar`, `POST caixa/reabrir`, `POST caixa/lancamento`, `DELETE caixa/lancamento/{id}`,
  `GET caixa/{id}/pdf`. Requests: `moedas*/cedulas* required|numeric|min:0`, `observacoes max:300`;
  lançamento `tipo in:E,S`, `valor min:0.01`, `historico required|max:300`.
  Status: `{ pdv, aberto, sessao, saldo, resumo, lancamentos, ultimaFechada, podeReabrir, podeOperar }`.
- **`Mg/Caixa/CaixaRelatorioService` + `api/resources/views/caixa/movimento.blade.php`** (mPDF):
  formulário do papel — Entrada (contagem inicial, vendas dinheiro, entradas avulsas) × Saída
  (contagem final, cancelamentos, saídas avulsas), Total, Diferença; informativo (cartão, PIX, vale,
  prazo, cheque da janela); lançamentos; assinaturas. Cresce nos milestones seguintes.
- **Resumo (SQL)**: (1) razão do período classificado por vínculo e sinal (`ajuste | vendasdinheiro |
  cancelamentos | recebimentos | pagamentos | suprimentos | sangrias | itensentrada | itenssaida |
  entradasavulsas | saidasavulsas`); (2) informativo: negócios fechados com natureza financeira,
  `n.codpdv in (select codpdv from tblpdv where codportador = :p)`, `lancamento` na janela, por
  `nfp.tipo`; (3) total de vendas. Identidade "Total": contado inicial + entradas − saídas = saldo.

### Frontend negocios
- `stores/caixa.js`: `status`; getters `temPortador, aberto, sessao, saldo, resumo, lancamentos,
  podeOperar, podeReabrir, motivoDinheiro`; actions `carregar(silencioso)`, `abrir`, `fechar`,
  `reabrir`, `lancamento`, `excluirLancamento`, `pdf`. Sempre `pdv: sSinc.pdv.uuid`.
- Rota `/caixa` → `layouts/CaixaLayout.vue` + `pages/CaixaPage.vue`: sem portador → `MgEmptyState`;
  fechado → abertura inline (Moedas autofocus, Cédulas, Observações) + "Última sessão" (diferença,
  PDF, Reabrir); aberto → cabeçalho (saldo), `CaixaResumo.vue`, informativos, lançamentos, botões
  Lançamento (`DialogLancamento.vue`) e Fechar (`DialogFecharCaixa.vue` com diferença ao vivo → PDF).
- `ReceberDialog.vue`: `dinheiro` desabilitado com `motivo` (padrão `PRECISA_CLIENTE`); `entrar()`
  dispara `carregar(true)`; `IndexPage` carrega no `onMounted`. Menu: "Caixa" (`savings`).

### Valida
`caixa.sql` 2×; abrir com 50 + 200 → ajuste +250; venda R$10 em dinheiro com R$20 → +10 na sessão;
fechar → Dinheiro desabilitado; estado velho → 422; cancelar venda com sessão aberta → movimento
inativo; com sessão fechada → 422; fechar com contagem → diferença → PDF; reabrir (Gerente) → ajuste
inativo → fechar de novo; sessão de ontem reaberta exige fechar ontem antes de hoje.

---

## M3 — Títulos no razão (TASK-39)

- **Backend**: `LiquidacaoTituloService::criar` → após gravar os movimentos, `lancarMovimentoTitulo`
  para cada 600 (gaveta sem sessão → 422; banco/cofre abre corrente sozinho); `estornar` →
  `inativarDoTitulo` das linhas dos 600 (período fechado → 422); `atualizar` mudando portador/data
  com linha no razão → 422 "estorne e lance de novo". `TituloService::criar` com
  `movimentaportador` → `lancarMovimentoTitulo(implantação)` (gaveta: autoriza + sessão);
  `estornar` → `inativarDoTitulo`; `atualizar` idem. `PdvLiquidacaoService::receber` (M1) passa a
  lançar no razão pelo mesmo caminho (dinheiro → gaveta com sessão; PIX → banco; cheque → Carteira,
  ignorado).
- **Frontend**: contas → Nova liquidação: `MgSelectPortador` agrupado ("desta filial" primeiro) e
  mensagem do 422; negocios → Caixa: linhas "Recebimentos de títulos" e "Títulos pagos (vales,
  adiantamentos)" no resumo, no PDF e na lista de lançamentos (tipo e pessoa).
- **Valida**: liquidação em gaveta com sessão → linha; sem sessão → 422; em banco → período do
  banco nasce; estorno em período aberto/fechado; "Vale Colaborador" em gaveta → −50 na sessão;
  "Adto Fornecedor" em banco; "Crédito Cliente" em Carteira → nada; PDV recebe notinha em dinheiro →
  aparece no fechamento.

---

## M4 — Transferências (TASK-39)

- **DDL** (`caixa.sql` seção 2): `tblportadortransferencia` + `confirmacao`, `codusuarioconfirmacao`,
  `cancelamento`, `codusuariocancelamento`, `justificativa varchar(300)`, `observacoes varchar(300)`;
  índice parcial de pendentes por destino.
- **Backend `PortadorTransferenciaService`**:
  ```php
  registrar(Portador $o, Portador $d, float $valor, ?string $obs): PortadorTransferencia
    // registrante é dono de um dos lados; o != d; gavetas dos dois lados com corrente aberto (422);
    // cria + lancar(o, −valor) + lancar(d, +valor); confirmada de nascença se podeOperar(d)
  confirmar(t)      // podeOperar(destino); 422 se não pendente; gaveta destino aberta
  cancelar(t, string $justificativa)   // dono de qualquer lado; pendente ou confirmada; inativa os 2 movimentos (422 se período fechado)
  pendentes(int $codportador)          // destino = portador, sem confirmação, sem cancelamento
  listar(array $f)                     // codportador (origem ou destino), codfilial, situacao, de, ate
  ```
  Resource: origem/destino, valor, `situacao` (pendente|confirmada|cancelada), quem/quando de cada
  etapa, justificativa. Regras de fechamento: `CaixaService::fechar` → 422 se há pendente chegando.
- **Rotas**: `v1/pdv/caixa/transferencia` (POST), `.../{id}/confirmar`, `.../{id}/cancelar`;
  `v1/portador-transferencia` (GET, POST, `{id}/confirmar`, `{id}/cancelar`); `GET v1/portador/caixas?codfilial=`
  (portadores `caixa` da filial com saldo, corrente aberto, pendentes, `ehGaveta`); `GET v1/portador/{id}/saldo`.
- **Frontend negocios**: `DialogTransferir.vue` (`MgSelectPortador` com prop `agrupar` = "desta
  filial / mais opções", gavetas fechadas desabilitadas com motivo, valor, observações); lista
  "Transferências da sessão" com cores por estado, Confirmar/Cancelar (justificativa) ; resumo e PDF
  com sangrias/suprimentos.
- **Frontend contas**: página **Caixas** (`pages/caixa/Index.vue`, `stores/caixaStore.js`, drawer
  filial/de/até): aba **Portadores** (cards `caixa` da filial com saldo, estado, pendentes) e aba
  **Transferências** (pendentes e histórico, Confirmar/Cancelar, FAB "Nova transferência" de → para com
  o select agrupado). Menu Movimento → "Caixas". `MgSelectPortador` compartilhado ganha `agrupar` e
  filtros `caixa`/`banco`.
- **Valida**: gaveta → cofre 200 (pendente, −200/+200), gerente confirma (verde); gerente registra
  "recebi 250 do caixa 3" (nasce confirmada); cancelar com justificativa (vermelha, movimentos
  inativos); gaveta → gaveta com destino fechado → 422; fechar gaveta com pendente chegando → 422;
  gaveta → Caixa Financeiro em "mais opções" (Financeiro confirma no contas); cancelar transferência
  cuja sessão já fechou → 422.

---

## M5 — Períodos no contas (TASK-39)

- **Backend `PortadorPeriodoController`** (`v1/portador-periodo`): `index`, `show`,
  `POST {id}/fechar` (`corte` obrigatório para corrente de não-gaveta; Financeiro/Admin),
  `POST {id}/reabrir` (Financeiro/Admin; gaveta: Gerente/Admin), `POST {id}/lancamento` (avulso em
  período aberto de não-gaveta, Financeiro — saldo de implantação e correções).
  `CaixaController` lado contas: `POST caixa/{id}/fechar` com contagem (Gerente, para gaveta cujo PDV
  sumiu), `GET caixa/{id}` (resumo), `GET caixa/{id}/pdf`.
- **Frontend contas**: Caixas → aba **Períodos** (portador, início, fim, estado, saldo inicial/final,
  diferenças e PDF quando gaveta; Fechar com `MgInputData` de corte (default: último dia do mês
  anterior); Reabrir; Lançamento avulso).
- **Valida**: cofre com lançamentos em set e out → fechar com corte 30/09 → `saldofinal` sem outubro,
  corrente novo com outubro e `saldoinicial = saldofinal`; liquidar com data de setembro → 422;
  reabrir em cadeia (out, depois set) e fechar na ordem; fechar out com set aberto → 422; lançamento
  de implantação no banco; gaveta: fechar sessão pelo contas com contagem.

---

## M6 — Itens do caixa e repasse ao parceiro (TASK-39; depende de M0.2)

- **DDL** (`caixa.sql` seção 3): `tblcaixaitem` (`item varchar(50)`, `modo` C|M, `codfilial` nulo =
  todas, `codpessoa` parceiro, `codcontacontabil`, `ordem`, `inativo`, audit) com seeds (Chips de
  celular C, Ingressos impressos C, Bilhete Agora M, BlackTicket M, Redeflex recarga M, Bradesco
  Expresso M — pessoa/conta o Fábio preenche); `tblcaixaitemlancamento` (`codportadorperiodo`,
  `codcaixaitem`, `valorabertura`, `valorfechamento`, `valorvendido`, `valorentrada`, `valorsaida`,
  `observacoes`, `codportadormovimento`, `codtitulo`, audit; único por período+item).
- **Backend**: `CaixaItem` CRUD (`v1/caixa-item`, Administrador/Financeiro); `CaixaService::abrir`
  cria os lançamentos dos itens ativos (C com `valorabertura` do payload) e soma os C no contado;
  `salvarItemLancamento` (upsert do movimento `entrada − saida`, manual, "{item}: {obs}");
  `fechar` pede `valorfechamento` dos C, soma no contado e, por item com líquido ≠ 0 (C: `abertura +
  entrada − saida − fechamento`; M: `entrada − saida`), cria título "Repasse Parceiro" via
  `TituloService::criar` (pessoa do item, filial da gaveta, conta do item, número
  `AAAA-MM-DD-P{id}` + `aplicarSufixoNumero`, vencimento = corte) e grava `codtitulo`; `reabrir`
  estorna esses títulos (422 se movimentados). `GET v1/caixa/item-lancamento?codcaixaitem&codfilial&de&ate`
  (lista para o acerto, com `vendido`). Rotas `v1/pdv/caixa/item/{id}` (PUT).
- **Frontend**: contas → Cadastros → "Itens do Caixa" (`pages/caixaItem/Index.vue`, `MgSelectPessoa`,
  `MgSelectContaContabil`, `MgSelectFilial`); negocios → Caixa: abertura/fechamento com um
  `MgInputValor` por item C, `CaixaItens.vue` na sessão aberta (M: vendido/entrada/saída/obs; C:
  entrada/saída/obs), PDF completo (bloco de itens com vendido); contas → Caixas → aba **Itens**
  (listagem por item/filial/período com totais).
- **Valida**: abrir com chips 100; vender 1 chip (nada lança); bloco de ingressos entrada 500;
  Bilhete Agora vendido 120 / entrada 120 → +120; fechar contando chips 85 e ingressos 380 → títulos
  "Repasse Parceiro" de 15, 120 e 120 em contas a pagar com número `2026-…-P…`; agrupar e liquidar;
  reabrir sessão com título já agrupado → 422.

---

## Riscos e bordas
- PDV offline: abrir/fechar caixa, receber título e fechar negócio são online; estado velho no
  Receber → 422 claro no `fechar`.
- Vários dispositivos na mesma gaveta: sessão é por portador (índice do corrente + `lockForUpdate`).
- Admin desvincula o último PDV de uma gaveta com sessão aberta → fecha pelo contas (M5).
- Hábito atual: liquidações caem em "Caixa <Loja>" (vira cofre, sem sessão); para entrar na gaveta,
  o caixa recebe pelo PDV (M1) ou escolhe a gaveta no contas. Treinar.
- Go-live: `titulo_valor.sql`, `receber_titulo.sql`, `caixa.sql`; conferir as 17 gavetas e o
  vínculo dos PDVs, os dois `caixa` da filial 101, grupo Caixa por filial, pessoas/contas dos itens.
- Achado não incluído (só reportado): `ConferenciaPage.vue` lê `valorstone`, backend devolve
  `valorpagarme` — coluna Stone sempre vazia.

## Backlog (`./backlog.sh`; a aprovação deste plano é o OK explícito para criar as duas tasks novas)
- `task create "Títulos têm tipos sem uso, colunas duplicadas e triggers do sistema antigo" --type chore -l contas,api --priority high -d "<M0>"` com ACs M0.1.x / M0.2.x.
- `task create "Receber título no balcão informando a forma (dinheiro, PIX, cheque, cartão)" --type feature -l negocios,contas,api --priority high -d "<M1>"` com ACs M1.1.x / M1.2.x; `--dep` na task prévia.
- `task edit 39 -s "In Progress" -a @fabio -d "<decisões>" --dep <task M1>` e ACs agrupados M2.x … M6.x
  (uma chamada `--ac` por critério: sessão/razão/venda dinheiro/cancelamento/PDF; títulos no razão;
  transferências em dois passos e regras de gaveta; períodos com corte, cadeia e imutabilidade; itens
  e Repasse Parceiro; `caixa` no portador e `codportador` no PDV; permissões Caixa/Gerente/Admin).
- `task edit 84|48|33|34 --notes "Consolidada na TASK-39 (…)"` + `task archive <id>`.
- Cada milestone: marcar os ACs, commit `[UPD] TASK-nn Mx …` só depois da validação e do OK, com os
  `.md` do backlog no mesmo commit.

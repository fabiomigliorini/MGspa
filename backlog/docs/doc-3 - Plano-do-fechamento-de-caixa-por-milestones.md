---
id: doc-3
title: Plano do fechamento de caixa por milestones
type: other
created_date: '2026-09-28 20:00'
---

# Fechamento de caixa — plano por milestones (fundação do dinheiro + receber no balcão + TASK-39)

## Como usar este plano (para conversas futuras)

Este arquivo é a fonte de verdade do desenho. Cada milestone é executado numa **conversa separada**,
referenciando este arquivo e o milestone pelo nome (ex.: "executa o M1 do plano em
`backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md`"). Ao iniciar uma conversa:

1. Ler este arquivo inteiro (Como usar, Glossário, Decisões, Modelo de dados, Convenções e o
   milestone pedido), depois as tasks: `./backlog.sh task view TASK-39`, `TASK-186` e as que a seção
   Backlog manda criar. Este arquivo está versionado (`./backlog.sh doc view doc-3`); não copiar.
2. Conferir no banco e no código o que o milestone assume; o que mudou desde o plano é relatado
   antes de codar, não corrigido em silêncio. Vale o que está no código e no banco.
3. Marcar a task In Progress, executar **só o milestone pedido** (DDL em dev, backend, frontend),
   deixar na árvore de trabalho sem commit, e entregar o roteiro "Valida" para o Fábio testar.
4. Commit só depois do OK explícito, um por milestone, com os `.md` do backlog no mesmo commit.
   Atualizar neste doc o que tiver mudado em relação ao plano daquele milestone.

**Andamento:** M0.1 e M0.2 (títulos com `valor`/`saldo`, catálogos enxutos) **concluídos e validados
em 29/09/2026** (TASK-186, commits `7c6551a33` e `0991fc2b3`). **M1 (movimento de título numa
linha) concluído e validado em 30/09/2026** (TASK-188). **M2 (tipo de portador e PDV →
portador) concluído e validado em 30/09/2026** (TASK-188). **Próximo: M3.**

**Go-live: tudo junto, no final.** Os milestones são commitados no master um a um, depois de
validados em dev, mas **nenhum vai para produção sozinho**: scripts DDL e código de todos os
milestones sobem de uma vez, numa única janela com a API parada, quando o plano terminar. Até lá
produção fica como está.

**Pendências do Fábio no go-live** (não são gaps do plano): quais dos dois portadores em espécie da
filial 101 (100 Caixa Financeiro, 101001 Caixa Atacado) são cofre; pessoa e conta contábil de cada
item do caixa; cadastrar as gavetas (portador em espécie) e vinculá-las em cada PDV de caixa (M2);
rodar os scripts DDL em produção, na ordem dos milestones (`movimento_titulo_colunas.sql`,
`portador_tipo.sql`, …), com o MGsis e o MG Lara olhando as views temporárias.

## Glossário

- **Portador**: conta onde dinheiro fica. Tipos: **E** espécie (gaveta, cofre, troco, Caixa
  Financeiro), **B** banco, **A** adquirente ou conta de pagamento (Stone, Safra, Mercado Pago, Asaas),
  **C** cartão de crédito da empresa (tem fatura), **O** outros (Carteira, Cobrador Externo e os que
  ficam só para histórico). **Gaveta** = portador `E` com PDV apontando (`tblpdv.codportador`).
- **Pagamento** (`tblpagamento`): o ato de dinheiro se mover. Origem vazia = recebimento (veio de
  fora); destino vazio = pagamento (foi para fora); os dois preenchidos = transferência. Serve para
  venda, título, vale, adiantamento, sangria, depósito, repasse de adquirente, fatura de cartão,
  taxa, ajuste de caixa e, no futuro, ordem ao banco.
- **Meio**: como o dinheiro andou (dinheiro, cheque, crédito, débito, PIX, boleto, transferência,
  vale, compensação, folha, permuta, perda). Lista fixa no código, com os códigos da NF-e.
- **Estado do pagamento**: pendente (amarelo), efetivado (verde), cancelado (vermelho).
- **Parcela do negócio** (`tblnegocioparcela`): o que a venda deixou para depois. Vira título ao
  fechar. Condições: fechamento, parcelado, boleto, entrega, PIX/depósito a receber.
- **Movimento de título** (`tblmovimentotitulo`): o que mudou no título. Uma linha por título em cada
  pagamento, com principal, juros, multa, desconto e total — as mesmas colunas de valor do pagamento.
- **Razão** (`tblportadormovimento`): o que cai em cada portador e quando. Toda linha nasce de um
  pagamento. Data = dia em que aparece no extrato daquela conta.
- **Período** (`tblportadorperiodo`): faixa `[inicio, fim]` de um portador com saldo inicial e final.
  **Corrente** = `fim` nulo. **Aberto** = `fechamento` nulo. **Sessão** = período de gaveta (abre e
  fecha com contagem). **Corte** = a data que fecha um período corrente de cofre/banco. **Fatura** =
  período de cartão da empresa, com vencimento.
- **Envelope**: o que sobra na gaveta ao fechar = `saldofinal` da sessão = `saldoinicial` da próxima.
- **Item do caixa**: mercadoria de parceiro fora do fiscal (chips, ingressos, maquinetas de
  terceiros) que passa pela gaveta e vira título "Repasse Parceiro" no fechamento.
- **Maquineta** (`tblmaquineta`): cadastro único dos terminais de cartão, integrados ou não.
- **Baixa de título**: o nome novo para o que era "liquidação". A palavra liquidação só aparece
  neste doc para a tabela antiga (`tblliquidacaotitulo`) e as telas que existem até o M6.

## Contexto

Hoje o fechamento de caixa é feito à mão, no formulário "MOVIMENTO DO CAIXA" (um por caixa por dia):
contagem inicial e final de moedas/cédulas/chips/ingressos, total de vendas à vista, recebimentos de
títulos, cartões por adquirente, PIX, sangrias (com rubrica de quem levou) e a diferença. O único apoio
do sistema é o protótipo "Totais de Caixa" do MG Lara, que só lista totais por usuário/período.

O banco tinha `tblportadormovimento` e `tblportadortransferencia` desenhadas e vazias;
`tblpdv.codportador` vazio nos 312 PDVs; venda em dinheiro não tocava portador; a liquidação tinha
colunas de forma de pagamento nunca preenchidas; e "como o dinheiro se moveu" estava guardado de
três jeitos (forma de pagamento da venda, liquidação, transferência), com juros, multa e desconto em
linhas separadas do movimento de título. O M0 (TASK-186) já deixou título e movimento com um `valor`
com sinal, sem triggers e com catálogos enxutos.

**Formato do trabalho**: milestones. Cada um termina numa tela que o Fábio abre, testa e valida; só
então começa o próximo. Tudo que o milestone precisa (DDL, backend, frontend) entra nele; o que não é
dependente fica para o seguinte. Um commit por milestone, depois da validação e do OK explícito.

| # | Entrega | Task | Tela que valida | Risco |
|---|---|---|---|---|
| M0 | Títulos com `valor`/`saldo`, catálogos enxutos — **feito** | TASK-186 | — | — |
| M1 | Movimento de título numa linha (principal, juros, multa, desconto, total), histórico convertido | Fundação (nova) | contas → Títulos, Liquidações, recibos, relatórios | Médio |
| M2 | Tipo de portador, gavetas, trocos, adquirentes, PDV → portador | Fundação | contas → Portadores; negocios → Config → PDV | Baixo |
| M3 | Cadastro único de maquinetas | Fundação | contas → Maquinetas; PDV → Cartão | Baixo |
| M4 | Pagamento e parcelas no lugar da forma de pagamento da venda; histórico copiado; view do legado (PDV não muda) | Fundação | venda em todas as formas, NF-e, DIMP, romaneio | **Alto** |
| M5 | Wizard de cobrança desacoplado; prazo com vencimento ajustável | Fundação | PDV → Receber (F6–F9), Prazo | **Alto** |
| M6 | Pagamento no lugar da liquidação; telas de recebimentos e pagamentos | Fundação | contas → Recebimentos e Pagamentos; RH acerto; retorno de boleto | **Alto** |
| M7 | Receber notinha e pagar vale/crédito do cliente no PDV | Receber no balcão (nova) | PDV → Receber título / Pagar vale | Médio |
| M8 | Vale colaborador e adiantamentos no PDV | Receber no balcão | PDV → Vale / Adiantamento | Baixo |
| M9 | Conferência de maquinetas | Receber no balcão | contas → Maquinetas → Conferência | Baixo |
| M10 | Caixa: períodos, razão, abrir/fechar, dinheiro | TASK-39 | negocios → Caixa | Médio |
| M11 | Transferências (sangria, suprimento, depósito, envio ao financeiro) | TASK-39 | negocios → Caixa; contas → Caixas | Médio |
| M12 | Períodos no contas (financeiro fecha o mês) | TASK-39 | contas → Caixas → Períodos | Baixo |
| M13 | Itens do caixa e repasse ao parceiro | TASK-39 | negocios → Caixa; contas a pagar | Médio |
| M14 | Cartões no razão: prazos, taxas, fatura do cartão da empresa, conciliação com extrato | futura | a definir | — |
| M15 | Pagamento por PIX e boleto pela API do banco; cancelamento e devolução pela integração | futura | a definir | — |

---

## Decisões fechadas (não reabrir)

### Estrutura

1. **Duas peças genéricas: o pagamento e o razão.** O pagamento (`tblpagamento`) é o ato: de onde
   saiu, para onde foi, quanto, meio, estado. O razão (`tblportadormovimento`) é a consequência: o que
   cai em cada portador e quando. Um pagamento gera zero, um ou vários lançamentos (crédito em 3x =
   três lançamentos).
2. **Pagamento com origem e destino absorve a transferência.** Recebimento, pagamento e transferência
   são a mesma tabela. `tblportadortransferencia` some.
3. **O pagamento aponta para o documento** (`codnegocio`, item do caixa, período, acerto de RH), nunca
   o contrário: é o lado "muitos", é o padrão da casa (movimento aponta para liquidação, título para
   forma de pagamento) e o fechamento classifica olhando a linha. Com título a ligação é pelo
   movimento de título, que aponta para o pagamento.
4. **O pagamento substitui a forma de pagamento da venda.** Tabela nova, **histórico copiado**
   mantendo os códigos (pagamento 123 = antiga forma 123). A tabela antiga cai e no lugar fica uma
   **view temporária com o nome antigo** para o Totais de Caixa do MG Lara e a NFe de Terceiros do
   MGsis, até saírem.
5. **Prazo é estrutura à parte: parcelas do negócio.** O que não foi pago na hora (fechamento,
   crediário, boleto, entrega, PIX/depósito a receber) fica em `tblnegocioparcela`, com vencimento,
   valor e condição, sugerido pela condição e ajustável no PDV até fechar. Ao fechar, cada parcela
   vira um título. Depois disso, mexer em vencimento é no título, pelo contas.
6. **A liquidação desaparece.** O movimento de título aponta para o pagamento. Duas formas de
   pagamento viram dois pagamentos, cada um baixando a sua parte dos títulos. Encontro de contas sem
   dinheiro = pagamento de total zero, meio compensação, sem portador. Histórico de liquidações copiado
   para pagamento (código novo; o antigo guardado em coluna) e view temporária para o MG Lara.
7. **Movimento de título: uma linha por título em cada pagamento**, com `principal` (era `valor`;
   efeito no saldo, com sinal), `juros`, `multa`, `desconto` (positivos) e `total` (dinheiro que
   andou, mesmo sinal do principal). Histórico convertido: as linhas separadas de juros, multa e
   desconto são incorporadas à linha de baixa (tipo 600) do mesmo título; os tipos de movimento juros,
   multa e desconto deixam de ser usados. Juros de parcelamento e desconto por meio, na venda,
   ficam no pagamento (decisão 17). Taxa de adquirente e tarifa de banco são pagamento sem documento.
8. **Tudo em lista fixa.** Meios, condições de prazo e motivos de pagamento sem documento são
   constantes no código. O cadastro Formas de Pagamento deixa de ter tela (tabela congelada, só para
   a view do legado). A operadora é dado da maquineta, não da forma.
9. **Cadastro único de maquinetas** (`tblmaquineta`): PagarMe, Saurus e manuais no mesmo cadastro;
   as tabelas das operadoras continuam só como configuração da integração; o wizard escolhe da lista.
10. **Tipo de portador** char(1): E espécie, B banco, A adquirente/conta de pagamento, C cartão da
    empresa, O outros. Gaveta = E com PDV apontando. Os pseudoportadores usados como forma de baixa
    (Acerto Folha, Barter, Perda por Prazo, Programação de Pagamentos, Cred Pis/Cofins) viram meio do
    pagamento e ficam só para histórico. Stone e Safra viram portadores A já no M2.
11. **PDV aponta para o portador** (`tblpdv.codportador`, N:1).
12. **Agrupamento de títulos fica como está.**

### Estados, datas e sinais

13. **Três estados**: pendente, efetivado, cancelado. Pendente = falta alguém confirmar (dono do
    destino na transferência, banco/maquineta numa cobrança, ou a venda ainda não fechou). O
    lançamento no razão nasce quando o dinheiro se move de fato: na transferência já no registro
    (marcado "a confirmar"); na venda só ao fechar; na cobrança integrada quando o banco/maquineta
    confirma. Cancelar exige justificativa e período aberto nos dois lados.
14. **Cancelamento parcial e devolução são um pagamento novo no sentido contrário**, apontando para o
    original (`codpagamentoorigem`). O original nunca é alterado.
15. **No pagamento o valor é sempre positivo**; o sentido vem de origem/destino. No razão e no
    movimento de título o valor tem sinal (convenção do M0: positivo aumenta o que nos devem).
16. **Uma data no razão**: o dia em que cai naquele portador. A data do ato fica no pagamento. Saldo
    de hoje soma até hoje; data futura aparece como "a cair". Débito cai pela adquirente em D+1;
    crédito em D+30 (parcelado 30/60/90); PIX, TED e depósito no mesmo dia; transferência bancária
    no mesmo dia.
17. **Pagamento tem as mesmas colunas de valor do movimento de título**: `principal`, `juros`,
    `multa`, `desconto` e `total` (não existe coluna `valor`). `total` = o que andou de dinheiro por
    aquele meio = principal + juros + multa − desconto; `valortroco` à parte (a NF-e precisa do
    troco). Na venda: juros do parcelamento (12x no cartão) e desconto por pagar à vista (PIX,
    dinheiro) ficam no pagamento e são rateados nos itens como hoje se faz com o juros
    (`valorjuros` do item → vOutro; `valordesconto` do item → vDesc); conferência: Σ principal dos
    pagamentos = Σ itens; Σ total − troco = total da venda. Na baixa de título: o pagamento
    consolida as linhas de movimento (Σ de cada coluna dos movimentos = a coluna do pagamento). Juros
    de **título** (notinha atrasada) nasce no movimento; juros de **venda** nasce no pagamento.

### Períodos e imutabilidade

18. **`tblportadorperiodo`** = faixa `[inicio, fim]` do portador; `fim` nulo = corrente; `fechamento`
    nulo = aberto. Todo lançamento tem `codportadorperiodo NOT NULL` e cai no período cuja faixa
    contém a sua data; fechado → 422. Gaveta: abre e fecha explicitamente com contagem (corte =
    agora); "caixa aberto" = existe corrente aberto. Demais portadores: o corrente nasce sozinho no
    primeiro lançamento após o último corte; o financeiro fecha escolhendo a data de corte (o que
    ficou depois vai para o período novo). Cartão da empresa: período = fatura, com vencimento.
19. **Saldo do portador** = `saldoinicial` do aberto mais antigo + Σ lançamentos ativos dos abertos com
    data ≤ hoje; sem aberto = `saldofinal` do último fechado (0 se nunca houve).
20. **Período fechado é imutável.** Fecha-se do mais antigo para o mais novo; reabre-se do mais novo
    para o mais antigo (em cadeia). Estorno ou cancelamento com lançamento em período fechado → 422
    "reabra o período". Reabrir sessão de gaveta = Gerente/Admin; demais = Financeiro/Admin.
21. **Envelope = `saldofinal` da sessão.** Diferença na abertura e no fechamento = pagamento de ajuste
    (motivo ajuste de caixa) dentro da sessão; depois dele, sistema = contado.

### Caixa, transferências e balcão

22. **Transferência**: nasce com os dois lançamentos e um estado. Registra quem é dono de um dos
    lados; confirma o dono do lado que não registrou; **se o dono do destino registrou, nasce
    efetivada**; mesma pessoa dona dos dois lados, idem. Cancelar: qualquer dos dois donos, com
    justificativa, inativa os dois lançamentos (exige períodos abertos). Divergência de valor =
    cancela + registra outra. Lista de destino agrupada: "desta filial" (os `E` da filial, menos eu)
    e "mais opções" (Caixa Financeiro, bancos, `E` de outras filiais). Gaveta de origem ou destino
    sem sessão aberta → 422 (aparece desabilitada com motivo). Fechar gaveta com pendente **chegando**
    → 422; pendente saindo não trava.
23. **Quem opera**: gaveta → Caixa da filial/Gerente/Admin; cofre/troco → Gerente da filial/Admin
    (Caixa Financeiro 100 → Financeiro/Admin); banco/adquirente → Financeiro/Admin. Receber dinheiro
    no PDV exige grupo Caixa da filial do negócio e gaveta com sessão aberta (absorve TASK-48/34).
    Vale de colaborador e adiantamento a fornecedor no PDV: **o caixa lança sozinho** (fica
    registrado quem lançou). Pagar vale/crédito de cliente (dinheiro ou registro de cancelamento):
    **só Gerente/Admin**.
24. **A loja só baixa o que se confirma na hora**: dinheiro, PIX QR (o banco confirma pela API),
    cheque, cartão integrado e cartão manual (o comprovante da maquineta é a prova). PIX por chave,
    transferência e depósito são do financeiro, no contas. **No contas não se baixa título em gaveta.**
25. **Devolução sempre gera o vale** (título de crédito do cliente), como hoje. Devolver dinheiro é
    **pagar esse vale**: em dinheiro (gaveta), registrando o cancelamento no cartão (origem =
    adquirente, aponta para o pagamento original), registrando a devolução de PIX (origem = banco,
    aponta para o PIX original), ou por PIX/transferência comum pelo financeiro. Cancelamento e
    devolução são **só registrados** nesta fase; executar pela integração fica no M15.
26. **Duas conferências de cartão, separadas do caixa**: do dia (gerente: sistema × relatório da
    maquineta, M9) e do repasse (financeiro: adquirente × banco, taxa e prazo, M14). O fechamento da
    gaveta cuida só de dinheiro e itens; cartão e PIX aparecem nele como informação.
27. **Itens do caixa** (fora do fiscal): modo contagem (chips, ingressos impressos) e modo maquineta/
    terceiro (Bilhete Agora, BlackTicket, Redeflex, Bradesco Expresso). Cada item tem parceiro e
    conta contábil; no fechamento da sessão o líquido vira título "Repasse Parceiro" (tipo 953),
    número `AAAA-MM-DD-P{id do período}`, um por sessão por item; o financeiro agrupa e paga. Reabrir
    sessão estorna esses títulos (422 se já movimentados).
28. **Vale colaborador / adiantamento**: título com `movimentaportador` cuja implantação já vem com o
    pagamento (dinheiro na gaveta pelo PDV; transferência ou cartão da empresa pelo contas). Um título
    por forma.
29. **Escopo agora**: cartões e previsões (M14) e API de banco (M15) o mais tarde possível, sem
    travar nada; estrutura nasce pronta para eles (origem/destino, estado, `codpagamentoorigem`,
    data no razão, maquineta e adquirente como portador), mas nada de lote/ordem bancária agora.

---

## Modelo de dados

### `tblpagamento` (M4; ganha colunas em M6, M10 e M13)

| Coluna | Tipo | Observação |
|---|---|---|
| codpagamento | bigserial PK | históricos da venda mantêm o código da forma de pagamento |
| uuid | uuid | sync offline do PDV |
| codportadororigem | bigint FK | nulo = veio de fora |
| codportadordestino | bigint FK | nulo = foi para fora |
| meio | smallint NN | código NF-e: 1 dinheiro, 2 cheque, 3 crédito, 4 débito, 12 vale, 15 boleto, 16 depósito, 17 PIX, 18 transferência; internos: 91 compensação, 92 folha, 93 permuta, 94 perda |
| estado | char(1) NN | P pendente, E efetivado, C cancelado |
| principal | numeric(14,2) NN | > 0; o que foi quitado (itens da venda, saldo de títulos) |
| juros, multa, desconto | numeric(14,2) NN default 0 | positivos; venda: juros do parcelamento e desconto por meio; título: consolidação dos movimentos |
| total | numeric(14,2) NN | o que andou de dinheiro por este meio = principal + juros + multa − desconto |
| valortroco | numeric(14,2) | dinheiro |
| parcelas | smallint | cartão de crédito |
| lancamento | timestamp NN | data do ato |
| efetivacao, codusuarioefetivacao | timestamp, bigint | quando/quem confirmou |
| cancelamento, codusuariocancelamento, justificativa | timestamp, bigint, varchar(300) | |
| codpessoa | bigint FK | contraparte quando não vem do documento (colaborador, fornecedor, cliente) |
| codpdv, codfilial | bigint FK | onde aconteceu |
| motivo | char(1) | só sem documento: T taxa, F tarifa, R rendimento, A ajuste de caixa |
| codnegocio | bigint FK | documento: venda |
| codpagamentoorigem | bigint FK | pagamento contrário (cancelamento parcial, devolução, estorno) |
| codmaquineta, bandeira, autorizacao, nsu | FK, smallint, varchar(20), varchar(20) | cartão |
| codpixcob, codpix, codpagarmepedido, codsauruspedido, codliopedido | FK | integrações |
| codcheque, cmc7, chequevencimento, chequecnpj, chequeemitente | | cheque (dados antes de o cheque existir) |
| codliquidacaotituloantigo | bigint | só histórico (M6) |
| codportadorperiodo | bigint FK | ajuste de contagem (M10) |
| codcaixaitemlancamento | bigint FK | item do caixa (M13) |
| codperiodocolaboradoracerto | bigint FK | acerto de RH (M6) |
| observacoes | varchar(300) | |
| criacao, codusuariocriacao, alteracao, codusuarioalteracao | | audit |

Índices: `codnegocio`, `(estado, lancamento)`, `codportadororigem`, `codportadordestino`,
`(codmaquineta, lancamento)`, `codpagamentoorigem`, `uuid` único.

### `tblnegocioparcela` (M4)

| Coluna | Observação |
|---|---|
| codnegocioparcela PK, uuid, codnegocio NN | |
| condicao char(1) NN | F fechamento (último dia útil do mês seguinte), P parcelado (30/60/90), B boleto, E entrega, X PIX/depósito a receber |
| numero smallint, vencimento date NN, valor numeric NN | |
| codtitulo bigint FK | preenchido ao fechar |
| audit | |

Histórico: uma parcela por título que apontava para forma de pagamento a prazo (vencimento e valor
do título; condição pela forma antiga: 3010/3020/5601 → F, 5100 → P, 4100 → B, 1099 → E, 5606 → X).
`tbltitulo.codnegocioformapagamento` vira `codnegocioparcela`.

### `tblmovimentotitulo` (M1)

`valor` → **`principal`** (efeito no saldo, com sinal); novas `juros`, `multa`, `desconto`
(numeric NN default 0, CHECK ≥ 0) e `total` (numeric NN default 0, valor efetivo da baixa —
dinheiro que andou, ou o que foi levado ao agrupamento —, sinal do principal). Na baixa: |total| =
|principal| + juros + multa − desconto. Implantação e ajuste: total 0. Script
`api/database/movimento_titulo_colunas.sql` (M1). Em M6 ganha `codpagamento` (FK) e perde
`codliquidacaotitulo`.

### `tblportador` (M2)

`tipo char(1) NN default 'O'` (E, B, A, C, O). Config de prazos/taxas de cartão fica para o M14.

### `tblmaquineta` (M3)

`codmaquineta PK, apelido, serial, codfilial, codpessoa (adquirente), integracao char(1) (nulo manual |
P PagarMe | S Saurus), codpagarmepos, codsauruspinpad, inativo, audit`. Seed das operadoras e dos
seriais em uso. `tblmaquinetaconferencia` (M9): `codmaquineta, dia, credito, debito, observacoes,
codusuario, audit`, única por maquineta e dia.

### `tblportadorperiodo` (M10)

`codportadorperiodo PK, codportador NN, inicio NN, fim (nulo = corrente), fechamento (nulo = aberto),
codusuarioabertura, codusuariofechamento, saldoinicial NN default 0, saldofinal, vencimento (fatura,
M14), moedasabertura, cedulasabertura, moedasfechamento, cedulasfechamento, codpagamentoabertura,
codpagamentofechamento (ajustes), observacoes, audit`. Único corrente por portador (`fim IS NULL`).

### `tblportadormovimento` (M10; recriada, a atual está vazia)

`codportadormovimento PK, codportador NN, codportadorperiodo NN, codpagamento NN, valor NN (com
sinal), data date NN (quando cai), parcela smallint, conciliado bool NN default false, inativo,
audit`. Toda linha nasce de um pagamento. `tblextratobancarioportadormovimento` (existe) continua
sendo a amarração com o extrato para o M14.

### Itens do caixa (M13)

`tblcaixaitem` (`item, modo C|M, codfilial nulo = todas, codpessoa, codcontacontabil, ordem, inativo`)
e `tblcaixaitemlancamento` (`codportadorperiodo, codcaixaitem, valorabertura, valorfechamento,
valorvendido, valorentrada, valorsaida, observacoes, codpagamento, codtitulo`, único por período+item).

### O que some

`tblnegocioformapagamento` (vira view temporária), `tblliquidacaotitulo` (idem), `tblportadortransferencia`,
`vwnegocioformapagamento`, `vwnegocioformapagamentototais`. `tblformapagamento` fica congelada, sem
tela, só para a view; cai com ela. Tipos de movimento 400, 401, 500 e os estornos deles ficam só
para leitura de histórico não convertido.

---

## Convenções confirmadas no código

- **Títulos depois do M0**: `valor`/`saldo` com sinal (positivo = a receber); movimento com
  `principal`, `juros`, `multa`, `desconto`, `total` (M1; `lancar(titulo, tipo, principal, [juros,
  multa, desconto, total], vinculos, unicoPor)`, sem total a baixa calcula), estorno com o tipo do
  original + `codmovimentotituloestorno`; implantação sempre
  100 (`TituloService::implantar`); escritor único `MovimentoTituloService::lancar`; `Titulo::ehReceber()`;
  `tbltipotitulo.natureza` (R/P) e `movimentaportador` (2 Vale Colaborador, 120, 220, 230); tipo 953
  Repasse Parceiro. `debito`/`credito` de `tblliquidacaotitulo` ainda existem só para o Totais de
  Caixa do MG Lara (somem com a view do M6).
- Dinheiro no portador = `−total` do movimento de título ligado ao pagamento (título a receber baixa
  com total negativo → entrou dinheiro).
- `Autorizador::pode([...], $codfilial)` sempre inclui Administrador, mas com filial exige linha do
  Admin naquela filial → "Admin ou X da filial" = `pode([]) || pode(['X'], $codfilial)`.
- `throw new Exception` genérico vira 500 em prod → regras novas usam `abort(422|403, msg)`.
- PDV offline: o negócio vive em Dexie (`negocios/src/boot/db.js`, `negocio` com `itens`, `vales`,
  `pagamentos`, `titulos`); o PUT de sync (`PdvNegocioService::negocioAberto`) faz upsert por `uuid`
  e **apaga** o que não veio; fechar é sempre online (`PdvNegocioService::fechar`, transação
  328→476). Cobranças integradas (PIX, PagarMe, Saurus) são criadas pelo servidor ao confirmar.
- Scripts DDL: `api/database/<nome>.sql`, padrão `vale.sql` (`\set ON_ERROR_STOP on`, `BEGIN`,
  lock/statement timeout, idempotente, FKs guardadas por `pg_constraint`, `COMMIT`). Dev:
  `docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < api/database/x.sql`; prod: Fábio, no go-live.
- Padrão de domínio: `api/app/Mg/<Dominio>/` com Model (`MgModel`), Controller (transação, `abort`),
  Service estático, Store/UpdateRequest, Resource; rotas em `api/routes/api.php` dentro de
  `auth:api` + `v1` (grupo `pdv` ~855-923; Portador ~1264-1278). PDF = mPDF como
  `Mg/Vale/ValeEmitidoRelatorioService.php`. Frontend: store Pinia por domínio, `MgInput*`,
  `MgSelect*`, `MgEmptyState`, `MgInfoCriacao`, `abrirPdf`, botões de dialog `flat`.
- Levantamento de 29/09/2026: `tblnegocioformapagamento` tem 5,3 milhões de linhas (225 mil em
  2026), 4 FKs apontando (título, movimento de título, cheque, razão), 2 views, 52 PHP + 2 blades +
  2 telas no MGspa, o `CaixaController` do MG Lara e o `NfeTerceiro` do MGsis. Liquidações: 175 mil.
  Movimentos de juros 41 mil, multa 25 mil, desconto 8,6 mil. Wizard e integrações do PDV: ~2.300
  linhas amarradas ao negócio (`ReceberDialog` 482, `FormaCartao` 440, `FormaPix` 209, `FormaCheque`
  173, `FormaVale` 197, `FormaPrazo` 152, stores `pix`/`pagar-me`/`saurus`). `tblsauruspedido.codnegocio`
  é NOT NULL; `tblpixcob.codnegocio` e `tblpagarmepedido.codnegocio` aceitam nulo. Maquinetas:
  `tblpagarmepos` (89, 4 ativas), `tblsauruspdv` (24) + `tblsauruspinpad` (31), serial digitado no
  cartão manual. PDVs de caixa: 17 com `alocacao = 'C'`.

---

## M1 — Movimento de título numa linha só (Fundação)

- **DDL `api/database/movimento_titulo_colunas.sql`**: `ALTER TABLE tblmovimentotitulo RENAME valor TO
  principal`; `ADD juros, multa, desconto, total numeric(14,2) NOT NULL DEFAULT 0`; conversão do
  histórico: para cada grupo (codtitulo + codliquidacaotitulo, ou + codboletoretorno) com linha 600,
  incorporar as linhas 400/401/500 do grupo (`juros`/`multa`/`desconto` = soma delas; `total` =
  `principal` antigo da 600; `principal` = total + juros + multa − desconto no sentido do título) e
  apagar as incorporadas (reapontar `codmovimentotituloestorno` de quem apontava para elas); mesma
  regra para os grupos de estorno (930 e os 9xx de juros/multa/desconto); linhas 400/401/500 sem
  baixa no grupo ficam como estão (tipo próprio, `principal` = valor antigo, colunas zeradas).
  Conferência antes/depois: saldo de cada título igual; Σ juros/multa/desconto por mês igual
  (coluna + linhas remanescentes).
- **Backend**: `MovimentoTituloService::lancar(Titulo, tipo, principal, [juros, multa, desconto,
  total], vinculos)` grava uma linha; `MovimentoTituloHelper::liquidar` deixa de chamar
  `adicionarMultaJurosDesconto` e passa tudo numa linha; `estornar` estorna a linha inteira;
  `LiquidacaoTituloService`, `BoletoRetornoService`, `BoletoBbService`, `Rh/AcertoService`,
  `TituloAgrupamentoService` e `PdvNegocioPrazoService` ajustados; Resources e listagens leem
  `principal`/`total` e as colunas; relatório de juros soma coluna + tipos 400/401 remanescentes.
- **Frontend**: contas → Títulos (detalhe mostra a linha com as cinco colunas, "Mostrar estornos"),
  Liquidações (nova/detalhe/recibos), Agrupamentos; negocios `ListagemTitulos`/`FormaVale`;
  pessoas RH acerto.
- **Valida**: baixar título com juros, multa e desconto → uma linha; estornar → uma linha de
  estorno; abatimento sem pagamento; baixa de boleto BB pela API; acerto de RH; agrupamento;
  venda a prazo e vale no PDV; recibos e relatórios; conferência do histórico.
- **O que mudou em relação ao plano** (conferência de 29/09/2026 no banco e no código):
  - **Quatro chaves de grupo, não duas.** Além de liquidação e retorno Bradesco, juros e multa
    ficam agrupados no **agrupamento** (a baixa é a 901; o estorno, a 991) e no **boleto BB pela
    API** (`codtituloboleto`, sem liquidação nem retorno: 5,6 mil linhas). As quatro chaves nunca
    aparecem juntas na mesma linha. Estorno feito depois do M0 herda o grupo do original pelo
    ponteiro (o `estornar` não copia o agrupamento).
  - **Boleto Bradesco está abandonado**: boleto é só pela API do BB (último retorno Bradesco é de
    2021). O `BoletoRetornoService` foi só adaptado à coluna nova, para não quebrar; o histórico
    dele foi convertido como os demais. Sai da validação do M1 e do M6.
  - **Sentido** das colunas: o sinal da própria baixa; baixa de valor zero (título quitado só com
    desconto, ~1,8 mil) usa a natureza do título. Grupos que dariam juros, multa ou desconto
    negativo (títulos de 2011 a 2024 com sinal trocado) **ficam como estão**, e a coluna ganhou
    CHECK `>= 0`.
  - Resultado em dev: 52.608 grupos juntados, 79.645 linhas incorporadas, **22 linhas** de
    juros/multa/desconto ficaram (4 de uma liquidação de 2013 estornada três vezes + os grupos de
    sinal trocado). Conferência dentro do script (aborta se não fechar) e refeita fora dele contra a
    cópia de antes: saldo igual nos 777.624 títulos; juros, multa e desconto iguais nos 185 meses;
    total de cada liquidação igual.
  - **`total`** = valor efetivo da baixa: dinheiro, ou o que foi levado ao agrupamento (901/991).
    Preenchido em toda baixa (300/933 vale, 600/930, 601, 610/910, 901/991); implantação, ajuste,
    transferência e as linhas antigas que ficaram: 0. Vale colaborador e adiantamentos também
    implantam com 0 até o M8.
  - O total da liquidação (`recalcularLiquidacao`, e o `debito`/`credito` do Totais de Caixa)
    passou a somar `total` das baixas: é o dinheiro, como antes.
  - "Outro" do boleto BB (`valoroutro`) continua num ajuste 200 próprio; não é juros.
  - Tipos 400, 401, 500, 940, 941 e 950 **inativados** (só para o histórico que sobrou).
  - **MGsis**: a NFe de Terceiros grava implantação/ajuste e recalculava o saldo pela coluna
    `valor`. `MovimentoTitulo.php` e `Titulo.php` de lá passaram a `principal` e **sobem junto**.
  - negocios (`ListagemTitulos`, `FormaVale`) e pessoas (acerto de RH) não precisaram mudar: leem
    valor/saldo do título e a chave `valor` do resource do acerto, que continua (agora de
    `principal`).

## M2 — Tipo de portador e PDV → portador (Fundação)

- **DDL `api/database/portador_tipo.sql`**: `tblportador.tipo char(1) NN default 'O'` + CHECK;
  seed: `B` onde `codbanco` não nulo (exceto os "Cartao NNNN", que viram `C`); `E` em 100, 101001,
  202002, 201001, 202001, 202023, 202017 (Caixa Arquitetura ganha filial 501); `A` em 202019 Cielo
  Super Link, 202027 Mercadopago, 202046 Mercos Pay, 202049 Asaas e nos novos **Stone** e **SafraPay**
  (criados, filial nula, `codpessoa` 9993 e 20119); `INSERT` "Troco <Loja>" (`E`) para 101–105;
  `INSERT` uma gaveta (`E`) por `tblpdv` com `alocacao = 'C'`, autorizado, ativo e sem portador (nome
  = apelido, filial do PDV) + `UPDATE tblpdv.codportador`. Fábio confirma os dois `E` da filial 101.
- **Backend**: `Portador` com `tipo`, `ehGaveta()`, constantes; requests/resources/filtro/select
  (`SelectPortadorController` devolve `tipo`, `codfilial`); `PdvService::update`: `codportador` só
  tipo `E` da mesma filial (422); `PdvResource` com `portador`.
- **Frontend**: contas → Portadores: `q-select` de tipo, filtro, badge; trocar `q-input` restantes
  por `MgInput`. negocios → Config → PDV → Editar: `MgSelectPortador` (prop `tipos`) filtrando `E` da
  filial; `PdvPage` mostra o portador. `@components/MgSelectPortador.vue` ganha `tipos` e `agrupar`
  ("desta filial / mais opções", usado nos milestones seguintes).
- **Valida**: tipos certos nos 69 portadores; Stone/SafraPay/trocos/17 gavetas criados; Config →
  PDV mostra e troca o portador; portador de outra filial ou que não é espécie → recusa.
- **O que mudou em relação ao plano** (conferência de 29/09/2026 no banco):
  - **Gavetas não são criadas pelo script.** `alocacao = 'C'` não identifica PDV de caixa: são 275
    PDVs com `C`, 120 ativos e autorizados (celulares, escritório, depósito…); os 17 do plano só
    aparecem filtrando também o apelido com "Caixa". Decisão do Fábio: cada gaveta é cadastrada no
    contas → Portadores (tipo Espécie, filial da loja) e vinculada ao PDV em negocios → Config → PDV.
  - **Cartões de débito da empresa são `B`** (202021, 202022, 202040, 202041, 202043, 202045: o
    débito sai direto da conta, não tem fatura). `C` ficou só para os de crédito, incluindo 202028
    "Cartao 4439" e 202031 "Cartao 4956", que não têm `codbanco`.
  - O seed do tipo só roda quando a coluna nasce; depois quem manda é a tela (rodar o script de novo
    não desfaz o que foi mudado no cadastro). Resultado em dev: A 6, B 39, C 10, E 12, O 9.
  - Trocos criados como "Troco " + nome da filial: Troco Deposito, Botanico, Centro, Imperial,
    Andre Maggi. Stone e SafraPay sem filial, com `codpessoa` 9993 e 20119.
  - `MgSelectPortador`: `tipos` (array) filtra no front (a lista já vem inteira do
    `v1/select/portador`, que agora devolve `tipo`); `agrupar` usa a prop nova `codfilial` como
    referência de "Desta filial" e põe o resto em "Mais opções".
  - O formulário de portador do contas já não tinha `q-input` cru; nada a trocar.
  - Recurso do portador devolve `gaveta` (espécie com PDV apontando), mostrado como badge na lista.

## M3 — Cadastro único de maquinetas (Fundação)

- **DDL `api/database/maquineta.sql`**: `tblmaquineta` (modelo acima); seed de `tblpagarmepos` e
  `tblsauruspinpad` ativos (`integracao` P/S, adquirente pela operadora) e dos seriais digitados em
  uso; `codmaquineta` em `tblnegocioformapagamento` (backfill por `serialmaquineta`/`codpagarmepos`/
  `codsauruspinpad` via pedidos/pagamentos das operadoras).
- **Backend**: domínio `Mg/Maquineta` (CRUD, inativar/ativar, `v1/select/maquineta`); PagarMe e
  Saurus gravam `codmaquineta` ao criar o pagamento; cartão manual exige `codmaquineta`.
- **Frontend**: contas → Cadastros → Maquinetas; `@components/MgSelectMaquineta.vue`; PDV →
  `FormaCartao` lista as maquinetas da filial (recentes primeiro) em vez de digitar serial.
- **Valida**: cadastro com as das duas operadoras e as manuais; venda em cartão manual e integrado
  escolhendo da lista; pagamento gravado com a maquineta certa.

## M4 — Pagamento e parcelas no lugar da forma de pagamento da venda (Fundação; PDV não muda)

- **DDL `api/database/pagamento.sql`**: cria `tblpagamento` e `tblnegocioparcela`; copia o histórico
  de `tblnegocioformapagamento` (formas de dinheiro → pagamento com mesmo código, `estado` E se
  negócio fechado / C se cancelado / P se aberto, `meio` pelo `tipo`/forma antiga, origem/destino
  nulos no histórico; formas a prazo → parcela por título); reaponta `tbltitulo` (→
  `codnegocioparcela`), `tblmovimentotitulo` (vale usado → `codpagamento`), `tblcheque` (→
  `codpagamento`); derruba as 2 views e a tabela antiga; cria a **view `tblnegocioformapagamento`**
  (pagamentos ∪ parcelas, colunas antigas) para MG Lara e MGsis; `tblformapagamento` fica.
- **Backend**: domínio `Mg/Pagamento` (`Pagamento`, `PagamentoService::{criar, efetivar, cancelar,
  contrario}` com as regras de estado, `PagamentoResource`, constantes de meio/estado/motivo/
  condição); `Mg/Negocio/NegocioParcela` + `NegocioParcelaService` (regras de vencimento por
  condição, último dia útil); `PdvNegocioService::negocioAberto` faz upsert de `pagamentos` e
  `parcelas` por uuid (o payload do PDV continua o de hoje: o backend traduz forma antiga → meio/
  condição enquanto o M5 não chega); `fechar` efetiva os pagamentos pendentes e gera títulos das
  parcelas (`PdvNegocioPrazoService::gerarTitulos` lê parcelas); `cancelar` cancela pagamentos e
  estorna títulos; `confereTotais` checa Σ principal dos pagamentos = Σ itens e Σ juros/desconto
  dos pagamentos = rateado nos itens (mesmo mecanismo do juros de hoje; desconto de item que não vem
  de pagamento continua existindo); `NegocioResource` devolve `pagamentos` e `parcelas` **e** o formato antigo
  `pagamentos` que o PDV consome hoje; NF-e (`NotaFiscalNegocioService`: grupo `pag` com tPag, vPag,
  troco, CNPJ/bandeira/autorização pela maquineta), DIMP (`DimpConciliacaoService`), romaneio, vale,
  `PdvService::conferencia`, `NegocioFormaPagamentoResource` → leem pagamento; `NfeTerceiroImportarService`
  grava parcelas; `.env` do negocios deixa de precisar de `CODFORMAPAGAMENTO_*` (M5 remove).
- **Frontend**: nenhum (o PDV continua enviando e lendo o formato antigo). contas: telas que listam
  forma de pagamento do negócio leem o novo.
- **Valida**: venda em dinheiro (troco), PIX QR, PIX chave, cartão integrado (duas operadoras), cartão
  manual, cheque, vale compras, prazo (fechamento, crediário, boleto), entrega, pagamento dividido;
  cancelamento; NF-e/NFC-e emitida com o grupo de pagamento certo; DIMP do mês; romaneio; Totais de
  Caixa do MG Lara e NFe de Terceiros do MGsis funcionando na view; conferência de totais do
  histórico (Σ total por negócio antes = depois).

## M5 — Wizard de cobrança desacoplado e prazo com vencimento ajustável (Fundação)

- **Frontend negocios**: `ReceberDialog` e `Forma*.vue` deixam de importar `negocioStore`: recebem
  `{valor, sentido, pessoa, formasPermitidas, documento}` e emitem `pagamento` (meio, valor, troco,
  portador, maquineta, dados de PIX/cheque) ou `parcelas` (condição, vencimentos, valores); store
  `cobranca.js` guarda o estado do wizard; `negocioStore` consome (`adicionarPagamento`,
  `adicionarParcelas`, troco, fechar automático). `FormaPrazo` mostra as parcelas com vencimento e
  valor editáveis (default pela condição). O wizard sugere juros (parcelado no cartão) e desconto
  (PIX/dinheiro) pela forma, editáveis, e o `negocioStore` rateia `juros` e `desconto` do
  pagamento nos itens (o rateio do juros já existe em `negocio.js` ~554-571; o do desconto entra
  igual). Dexie `version(8)`: `negocio.pagamentos` no formato novo
  (`meio` no lugar de `codformapagamento`/`tipo`) e `negocio.parcelas`; migração dos negócios
  abertos locais. Stores `pix`/`pagar-me`/`saurus` acompanham o pedido pelo documento. Remove
  `CODFORMAPAGAMENTO_*` do `.env`.
- **Backend**: `tblsauruspedido.codnegocio` nullable; `PixService`, `PagarMeService`, `SaurusService`
  criam e consultam cobrança/pedido para um documento que pode não ser negócio; sync aceita o formato
  novo (e o antigo até todos os PDVs atualizarem); `NegocioResource` deixa de devolver o formato antigo
  quando não houver mais cliente antigo.
- **Valida**: venda exatamente como antes em todas as formas, F6 a F9, pagamento dividido, cancelar
  cobrança, fechamento automático; prazo com vencimentos editados virando títulos com as datas
  certas; fechamento mensal caindo no último dia útil do mês seguinte.

## M6 — Pagamento no lugar da liquidação (Fundação)

- **DDL** (seção 2 de `pagamento.sql`): copia `tblliquidacaotitulo` para `tblpagamento` (código
  novo, `codliquidacaotituloantigo`, `meio` derivado do portador antigo: espécie → dinheiro; banco →
  transferência; pseudoportadores → compensação/folha/permuta/perda; estado E ou C se estornada;
  origem/destino pelo sentido); `tblmovimentotitulo.codpagamento` (backfill pelo antigo
  `codliquidacaotitulo`), derruba `codliquidacaotitulo`; derruba a tabela e cria a **view
  `tblliquidacaotitulo`** (pagamentos que movimentam título, com `debito`/`credito` calculados) para o
  Totais de Caixa; pseudoportadores inativados.
- **Backend**: `LiquidacaoTituloService` → `PagamentoTituloService::{receber, pagar, estornar}`:
  cria o pagamento (uma linha por forma), grava uma linha de movimento por título (principal, juros,
  multa, desconto, total; Σ de cada coluna = a coluna do pagamento), compensação de total zero; autorização por
  portador (Financeiro/Cobrança/Gerente/Caixa como hoje, e **gaveta recusada no contas**);
  `Rh/AcertoService` cria pagamento por evento (B banco, D dinheiro, F folha = compensação);
  `BoletoBbService` cria pagamento (destino = banco do boleto, meio boleto)
  por baixa; `PdvLiquidacaoService` lê pagamentos com título; recibos e relatório (`Mg/Pagamento/
  PagamentoRelatorioService`, blades renomeadas); rotas `v1/pagamento` (index, show, store,
  estornar, recibo, relatório) no lugar de `v1/liquidacao-titulo`.
- **Frontend contas**: "Liquidações" vira **"Recebimentos e Pagamentos"** (listagem com filtros de
  portador, meio, pessoa, período; detalhe com os títulos e as colunas; nova: seleciona títulos,
  portador — sem gaveta —, meio derivado do tipo do portador, juros/multa/desconto por título;
  estorno; recibos). negocios `LiquidacaoListagemPage` → lista de pagamentos com título (filtros
  corrigidos). pessoas RH acerto.
- **Valida**: receber em banco no contas (uma forma); pagar fornecedor; encontro de contas; estornar;
  acerto de RH (B/D/F); baixa de boleto BB pela API; recibos e relatório; Totais de Caixa do MG
  Lara na view; histórico: Σ por portador e mês antes = depois.

## M7 — Receber notinha e pagar vale do cliente no PDV (Receber no balcão)

- **Backend** `Mg/Pdv/PdvPagamentoService`: `titulosAbertos(codpessoa | numero)` (a receber e créditos
  do cliente); `receber(Pdv, dados)`: pagamentos de entrada (dinheiro → destino = gaveta do PDV ou 422
  "PDV sem portador"; PIX QR → cobrança sem negócio, destino = banco da cobrança; cheque → destino
  Carteira; cartão integrado/manual → destino = adquirente da maquineta) com uma linha de movimento
  por título, distribuindo por vencimento; `pagarCredito(Pdv, dados)` (Gerente/Admin): pagamento de
  saída baixando o vale/crédito — dinheiro (origem = gaveta), registro de cancelamento no cartão
  (origem = adquirente, `codpagamentoorigem` = pagamento original escolhido, valor ≤ original),
  registro de devolução de PIX (origem = banco, aponta para o PIX original); `estornar` (Caixa:
  próprios, 120 min; Gerente: filial); recibo térmico. Rotas `v1/pdv/pagamento/*`. PIX chave,
  transferência e depósito não aparecem no PDV.
- **Frontend negocios**: `ReceberTituloDialog.vue` (teclado): pessoa ou número → títulos abertos e
  créditos com seleção e total → wizard de cobrança (sentido conforme o líquido); atalho F11; para
  pagar crédito, escolha do meio (dinheiro / cancelamento no cartão escolhendo o pagamento original /
  devolução de PIX); store `pagamento.js`; listagem de pagamentos do PDV mostra meio, maquineta e PDV.
- **Valida**: notinha em dinheiro (troco), PIX QR, cheque, cartão nas duas operadoras, cartão manual;
  entrega paga na volta; dividido (dois pagamentos, um recibo); crédito de devolução pago em dinheiro
  (só Gerente), cancelamento parcial no cartão registrado; estorno; contas mostra tudo.

## M8 — Vale colaborador e adiantamentos no PDV (Receber no balcão)

- **Backend** `PdvTituloService::lancar(Pdv, dados)`: cria título com `movimentaportador` (2 Vale
  Colaborador e 120 Adto Fornecedor: saída em dinheiro, origem = gaveta; 220 Adto Cliente: entrada
  pelas formas do M7), um título por forma, implantação com `total` e pagamento ligado; `estornar`
  via `TituloService::estornar` (cancela o pagamento). Permissão: Caixa da filial/Gerente/Admin.
- **Frontend**: `LancarTituloDialog.vue` (tipo, pessoa, valor, observação → wizard no sentido certo);
  comprovante térmico com assinatura.
- **Valida**: vale em dinheiro; adiantamento de cliente em dinheiro, PIX QR e cartão; adiantamento a
  fornecedor; títulos no contas com portador certo; estorno.

## M9 — Conferência de maquinetas (Receber no balcão)

- **DDL**: `tblmaquinetaconferencia`. **Backend** `MaquinetaConferenciaService`: total do sistema por
  maquineta e dia (pagamentos efetivados com meio crédito/débito, por documento), lançamentos do dia,
  gravar o informado, diferença; Gerente da filial/Financeiro/Admin. **Frontend contas**: Maquinetas
  → Conferência (filial e dia; sistema × informado × diferença, crédito e débito; detalhe; PDF).
- **Valida**: dia com vendas e recebimentos em três maquinetas; relatório digitado; diferença zero e
  provocada.

## M10 — Caixa: períodos, razão, abrir/fechar, dinheiro (TASK-39)

- **DDL `api/database/caixa.sql`** (seção 1): `tblportadorperiodo`; recria `tblportadormovimento`;
  `tblpagamento.codportadorperiodo`.
- **Backend** `Mg/Portador/PortadorPeriodoService` (`corrente`, `abertos`, `periodoPara(portador,
  data, acao)` — gaveta sem sessão → 422 "Caixa fechado…", período fechado → 422, demais cria o
  corrente —, `saldo`, `fechar(periodo, corte)` com reaponte do que ficou depois do corte, `reabrir`
  em cadeia, `listar`); `PortadorMovimentoService::lancar(Pagamento)` gera as linhas do razão pelo
  meio e pelos portadores do pagamento (dinheiro/PIX/transferência: uma linha por lado, data = hoje;
  cartão: uma por parcela com D+1/D+30…, só quando o M14 ligar; outros: nada) e `inativar`;
  `PagamentoService::efetivar` passa a chamar `lancar`; `Mg/Caixa/CaixaAutorizador` (decisão 23) e
  `CaixaService` (`portadorDoPdv`, `abrir` com contagem e ajuste, `fechar` com contagem, ajuste e
  422 se há pendente chegando, `reabrir`, `resumo`, `lancamentos`, `lancamentoAvulso` = pagamento com
  motivo, hooks `validarDinheiroNegocio`/efetivação no `fechar` do negócio e cancelamento em período
  fechado → 422); `CaixaController` (`v1/pdv/caixa`: status, abrir, fechar, reabrir, lancamento,
  pdf); `CaixaRelatorioService` + blade "Movimento do Caixa" (Entrada × Saída, Total, Diferença,
  informativo de cartão/PIX/vale/prazo, lançamentos, assinaturas). Resumo: razão do período
  classificado pelo pagamento (venda, cancelamento, título recebido/pago, transferência, item, ajuste,
  avulso) + informativo por meio dos negócios dos PDVs da gaveta na janela.
- **Frontend negocios**: `stores/caixa.js`; `/caixa` (`CaixaLayout`, `CaixaPage`: sem portador,
  fechado → abertura inline, aberto → cabeçalho, `CaixaResumo`, informativos, lançamentos, botões
  Lançamento e Fechar com diferença ao vivo → PDF; última sessão com Reabrir); `ReceberDialog`
  desabilita Dinheiro com motivo; menu "Caixa".
- **Valida**: abrir com 50 + 200 → ajuste +250; venda R$10 em dinheiro → +10 na sessão; fechar →
  Dinheiro desabilitado; cancelar venda com sessão aberta → estorno; com sessão fechada → 422;
  notinha e vale do M7/M8 aparecendo; fechar com contagem → PDF; reabrir (Gerente); sessão de ontem
  reaberta exige fechar antes de hoje.

## M11 — Transferências (TASK-39)

- **Backend** `PagamentoService::transferir(origem, destino, total, obs)` (regras da decisão 22:
  quem registra, nasce efetivada se o dono do destino registrou, gavetas com sessão aberta, dois
  lançamentos no registro marcados "a confirmar"), `confirmar`, `cancelar(justificativa)`;
  `pendentes(portador)`; rotas `v1/pdv/caixa/transferencia` (POST, confirmar, cancelar) e
  `v1/pagamento/transferencia` (contas); `GET v1/portador/caixas?codfilial=` (portadores `E` da
  filial com saldo, sessão, pendentes, `ehGaveta`); `GET v1/portador/{id}/saldo`.
- **Frontend**: negocios `DialogTransferir.vue` (`MgSelectPortador agrupar`, gavetas fechadas
  desabilitadas com motivo), lista com cores por estado, Confirmar/Cancelar; contas → página
  **Caixas** (`pages/caixa/Index.vue`, `caixaStore`, drawer filial/de/até): abas Portadores e
  Transferências (pendentes, histórico, FAB "Nova transferência" de → para). Menu Movimento → Caixas.
- **Valida**: gaveta → cofre 200 pendente, gerente confirma; gerente registra "recebi 250" (nasce
  efetivada); cancelar com justificativa; gaveta → gaveta com destino fechado → 422; fechar gaveta
  com pendente chegando → 422; gaveta → Caixa Financeiro (Financeiro confirma no contas); cancelar
  com sessão já fechada → 422.

## M12 — Períodos no contas (TASK-39)

- **Backend** `PortadorPeriodoController` (`v1/portador-periodo`: index, show, fechar com `corte`,
  reabrir, lançamento avulso em período de não-gaveta — Financeiro); `CaixaController` lado contas
  (fechar sessão com contagem para Gerente, resumo, PDF).
- **Frontend contas**: Caixas → aba **Períodos** (portador, início, fim, estado, saldos, diferenças
  e PDF quando gaveta; Fechar com `MgInputData` de corte; Reabrir; Lançamento avulso).
- **Valida**: cofre com lançamentos em set e out → fechar com corte 30/09 → `saldofinal` sem outubro,
  corrente novo com outubro; lançar com data de setembro → 422; reabrir em cadeia e fechar na ordem;
  saldo de implantação de banco/cofre; fechar sessão de gaveta pelo contas.

## M13 — Itens do caixa e repasse ao parceiro (TASK-39)

- **DDL** (seção 2 de `caixa.sql`): `tblcaixaitem` com seeds (Chips de celular C, Ingressos impressos
  C, Bilhete Agora M, BlackTicket M, Redeflex recarga M, Bradesco Expresso M), `tblcaixaitemlancamento`,
  `tblpagamento.codcaixaitemlancamento`.
- **Backend**: `CaixaItem` CRUD (`v1/caixa-item`, Administrador/Financeiro); `CaixaService::abrir`
  cria os lançamentos dos itens ativos (C com `valorabertura`) e soma no contado;
  `salvarItemLancamento` (pagamento `entrada − saida`, destino/origem = gaveta, doc = item); `fechar`
  pede `valorfechamento` dos C, soma no contado e, por item com líquido ≠ 0 (C: `abertura + entrada −
  saida − fechamento`; M: `entrada − saida`), cria título "Repasse Parceiro" (pessoa e conta do item,
  filial da gaveta, número `AAAA-MM-DD-P{id}` + sufixo, vencimento = corte) e grava `codtitulo`;
  `reabrir` estorna (422 se movimentados); `GET v1/caixa/item-lancamento` para o acerto.
- **Frontend**: contas → Cadastros → Itens do Caixa; negocios → Caixa: contagem por item C na
  abertura/fechamento, `CaixaItens.vue` na sessão aberta, PDF completo; contas → Caixas → aba Itens.
- **Valida**: abrir com chips 100; vender 1 chip (nada lança); bloco de ingressos entrada 500;
  Bilhete Agora vendido 120 / entrada 120 → +120; fechar contando chips 85 e ingressos 380 → títulos
  Repasse Parceiro de 15, 120 e 120 em contas a pagar; agrupar e pagar; reabrir com título já
  agrupado → 422.

## M14 — Cartões no razão e conciliação (futura; a definir quando chegar)

Prazos e taxas por adquirente/maquineta; lançamentos do cartão na adquirente por parcela (D+1 débito,
D+30… crédito); repasse adquirente → banco como transferência; taxas e débitos da adquirente como
pagamento sem documento; fatura do cartão da empresa como período com vencimento e compras
parceladas em faturas futuras; conciliação razão ↔ extrato (`tblextratobancarioportadormovimento`),
importação de extrato de adquirente e de fatura. Ligar `PortadorMovimentoService::lancar` para meios
crédito/débito.

## M15 — Pagamento por API de banco e integrações de cancelamento (futura)

Ordem de pagamento (PIX por chave/dados/QR, boleto, TED) como pagamento de saída em estado pendente
até o banco confirmar; lote + item, chave própria sequencial, "aguardando liberação", devolução como
pagamento contrário; cancelamento de cartão pela operadora e devolução de PIX pela API. Levantamento
de 29/09/2026 (portais oficiais bloqueados; parte de memória): BB Pagamentos em Lote (lote + item,
liberação, webhook, mTLS A1), Itaú SISPAG (pré-aprovado × pós-autorizado), Bradesco (boleto, tributo,
TED, PIX), Sicredi sem API pública de pagamento. Conferir campos e estados ao implementar.

---

## Riscos e bordas

- M4, M5 e M6 mexem no caminho crítico do PDV e do financeiro: um de cada vez, com o roteiro de
  validação completo em dev, e conferência de totais do histórico antes e depois. O deploy é um só,
  no go-live final (ver topo), com todos os scripts e o código juntos e a API parada.
- PDVs com versão antiga durante o M5: o sync aceita os dois formatos até todos atualizarem.
- PDV offline: abrir/fechar caixa, receber título e fechar negócio são online; estado velho no
  Receber → 422 claro no `fechar`.
- Vários dispositivos na mesma gaveta: sessão é por portador (índice do corrente + `lockForUpdate`).
- Admin desvincula o último PDV de uma gaveta com sessão aberta → fecha pelo contas (M12).
- Hábito atual: liquidações caem em "Caixa <Loja>" (vira cofre, sem sessão); no modelo novo o
  balcão recebe pelo PDV e o contas não baixa título em gaveta. Treinar.
- Achado não incluído (só reportado): `ConferenciaPage.vue` lê `valorstone`, backend devolve
  `valorpagarme` — coluna Stone sempre vazia.

## Backlog (`./backlog.sh`; a aprovação deste plano é o OK explícito para criar as duas tasks novas)

- **Fundação** (nova, chore, labels `contas,negocios,api`, prioridade high):
  `task create "O pagamento é guardado de três jeitos (venda, liquidação, transferência) e juros, multa e desconto ficam em linhas separadas" --type chore -l contas,negocios,api --priority high -d "<Decisões 1-17 resumidas; milestones M1 a M6>"`;
  `--dep TASK-186`; ACs `M1.x` a `M6.x` (um `--ac` por critério, na língua de quem usa).
- **Receber no balcão** (nova, feature, labels `negocios,contas,api`, high):
  `task create "Notinha, vale e adiantamento não têm como ser recebidos ou pagos no PDV com a forma certa"` com ACs `M7.x` a `M9.x`; `--dep` na Fundação.
- **TASK-39**: `--dep` na task de balcão; ACs M10 a M13 (já lançados; ajustar os que citam
  liquidação/transferência para pagamento).
- **Futuras** (M14, M15): só anotadas aqui; nascem com OK explícito quando chegar a vez.
- Cada milestone: marcar os ACs, commit `[UPD] TASK-nn Mx …` só depois da validação e do OK, com os
  `.md` do backlog no mesmo commit.

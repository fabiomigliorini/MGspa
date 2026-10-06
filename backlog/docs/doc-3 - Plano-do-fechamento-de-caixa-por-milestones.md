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
portador) concluído e validado em 30/09/2026** (TASK-188). **M3 (cadastro único de maquinetas)
validado em 30/09/2026** (TASK-188): decisões da conferência na seção do M3, que cresceu (tela
Saurus/S2Pay do negocios e cadastro do POS PagarMe passam para contas → Maquinetas). Pendente do
M3 só o pareamento SafraPay por QR, que confere no go-live. **M4 (pagamento e parcelas da venda)
commitado em 30/09/2026 sem validação** (TASK-188, `ed90fe2e8` + MGsis `b914186`): o Fábio valida M4 e
M5 juntos. **M5 (wizard desacoplado, prazo ajustável) commitado em 30/09/2026 sem validação**
(TASK-188): valida junto com M4 e M6. **M6 (pagamento no lugar da liquidação) implementado em dev em
30/09/2026, na árvore, sem commit**: o Fábio valida M4, M5 e M6 juntos. **M6 validado em 01/10/2026** (`bc911e922`), com uma correção de rumo registrada como
**M6.1**: o wizard foi desacoplado mas não compartilhado, e o contas ganhou um dialog próprio sem
cartão e uma listagem parcial. **M6.1 commitado em 01/10/2026 sem validação**, a pedido do Fábio
(TASK-188): ele valida depois. **M8 (vale colaborador e adiantamentos no PDV) commitado em
01/10/2026 sem validação**, a pedido do Fábio (TASK-188): ele valida depois. **M8.1** (mesmo
dia, na árvore, sem commit): o dialog do M8 virou peça única em `@components`, usada também pelo
contas (o financeiro lança vale e adiantamento por depósito, transferência, cofre, cartão da
empresa). No M8.1 entram também a **limpeza dos tipos de título** (02/10/2026, executada em dev:
14 tipos ativos renumerados, 1xx a receber e 2xx a pagar, mais 5 inativos de histórico; os outros
38 apagados) e o **PDV pagando com qualquer título de crédito**; detalhe na seção M8. **M8.1
commitado em 02/10/2026 sem validação, a pedido do Fábio** (MGspa + `../MGsis` + `../MGdb`): ele
valida depois.
**M8.1 commitado em 02/10/2026** (`43a95c90e`). **M9 commitado em 02/10/2026** (`cf9777580`,
redesenhado): conferências independentes de tudo que o caixa movimenta, fechadas pelo gerente no
contas, com correção dos lançamentos; absorveu a gaveta do M10 (ver seção M9). **M10 (o razão +
o campo `transacao`) commitado em 02/10/2026 sem validação, a pedido do Fábio** (TASK-39): ele
valida depois, junto com M11 e M12; decisões e "Como ficou no código" na seção M10. **M11
(transferências) commitado em 02/10/2026 sem validação**, na mesma conversa que o M12 (TASK-39):
o Fábio valida M10, M11 e M12 juntos. **M12 (períodos no contas) commitado em 03/10/2026 sem
validação, a pedido do Fábio** (TASK-39): ele valida M10, M11 e M12 juntos pelo roteiro único
(seção M12, "Valida M10 + M11 + M12"); detalhe na seção M12. **M13 (itens do caixa, repasse,
ajuste e avulso da gaveta) commitado em 03/10/2026 sem validação, a pedido do Fábio** (TASK-39,
`e8ab52048`):
redesenhado com o Fábio em **uma tela só do caixa**, a mesma no PDV e no contas, com contagem por
cédula e moeda; **fechar passou a ser a conferência** (a etapa às cegas do M9 saiu). Detalhe na
seção M13.
TASK-193 (nova, High): tipo de título só obrigatório quando a natureza gera financeiro.
**Refatoração das telas (03/10/2026)**: as telas de M6.1 a M13 foram feitas antes de existir onde
ver o razão e serão redesenhadas do core para as beiradas, uma por vez. O desenho das telas está no
**doc-4** (`backlog/docs/doc-4 - Refatoração-das-telas-do-dinheiro-portador-e-período.md`), que
manda nas telas; este doc continua mandando no modelo e nas regras. **Painel /portador e tela do
portador e do período (doc-4) commitados em 03/10/2026 sem validação, a pedido do Fábio**: saldo
gravado no período e em `tblportador.saldo` (`portador_saldo.sql`), o Fábio valida pelo Valida do
doc-4.

**Go-live: tudo junto, no final.** Os milestones são commitados no master um a um, depois de
validados em dev, mas **nenhum vai para produção sozinho**: scripts DDL e código de todos os
milestones sobem de uma vez, numa única janela com a API parada, quando o plano terminar. Até lá
produção fica como está.

**Pendências do Fábio no go-live** (não são gaps do plano): quais dos dois portadores em espécie da
filial 101 (100 Caixa Financeiro, 101001 Caixa Atacado) são cofre; pessoa e conta contábil de cada
item do caixa; cadastrar as gavetas (portador em espécie) e vinculá-las em cada PDV de caixa (M2);
conferir a filial dos acessos de site de Brasil Card, Le Card e MultVale (nascem na 101) e as
maquinetas manuais criadas pelos seriais digitados, e validar o pareamento SafraPay por QR com um
pinpad reserva (M3); rodar os scripts DDL em produção, na ordem
dos milestones (`movimento_titulo_colunas.sql`, `portador_tipo.sql`, `maquineta.sql`,
`pagamento.sql`, `cobranca_documento.sql`, `pagamento_liquidacao.sql`, …), com o MGsis (NFe de Terceiros grava parcela desde o M4) e o MG Lara olhando
as views temporárias. `pagamento.sql` leva ~7 min em dev (5,3 milhões de formas). Cada script roda
uma vez, na ordem: `maquineta.sql` grava em `tblnegocioformapagamento` e falha se rodar depois do
`pagamento.sql` (a tabela já virou view). `pagamento_liquidacao.sql` leva ~45 s em dev (175 mil
liquidações, 437 mil movimentos) e precisa do `pagamento.sql` antes. O `.env` de produção do negocios
pode perder os `CODFORMAPAGAMENTO_*` (o código não lê mais). `conferencia.sql` (M9) e depois `razao.sql`
(M10), `caixa_item.sql` (M13), `portador_saldo.sql` (doc-4, saldo gravado), `portador_movimento_tipo.sql` (doc-4, redefinição do dinheiro) e `caixa_item_dinamico.sql` (doc-4, chips), nessa ordem, rodam antes do `tipo_titulo_limpeza.sql`. **`tipo_titulo_limpeza.sql` é o último
script** (renumera os tipos de título; os anteriores usam os códigos antigos), e o
`NfeTerceiroController.php` do MGsis sobe junto (grava Duplicata a Pagar, código novo 200).

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
  vale, outros, compensação, folha, permuta, perda). Lista fixa no código, com os códigos da NF-e.
- **Estado do pagamento**: pendente (amarelo), efetivado (verde), cancelado (vermelho).
- **Parcela do negócio** (`tblnegocioparcela`): o que a venda deixou para depois. Vira título ao
  fechar. Condições: fechamento, parcelado, boleto, entrega, PIX/depósito a receber, vale da
  devolução.
- **Movimento de título** (`tblmovimentotitulo`): o que mudou no título. Uma linha por título em cada
  pagamento, com principal, juros, multa, desconto e total — as mesmas colunas de valor do pagamento.
- **Razão** (`tblportadormovimento`): o que cai em cada portador e quando. Toda linha nasce de um
  pagamento. `transacao` = quando o dinheiro aparece naquela conta (M10).
- **Transação** (`transacao`, padrão desde o M10): data e hora do **fato gerador** — o PIX de ontem
  lançado hoje tem transação de ontem; a hora em que foi digitado é a `criacao`. Pagamento,
  cheque, extrato bancário, bonificação, razão e movimento de título usam este nome; o negócio
  (`tblnegocio.lancamento`) ainda não (task própria).
- **Período** (`tblportadorperiodo`): faixa `[inicio, fim]` de um portador com saldo inicial e final.
  **Corrente** = `fim` nulo. **Aberto** = `fechamento` nulo. **Sessão** = período de gaveta (abre e
  fecha com contagem). **Corte** = a data que fecha um período corrente de cofre/banco. **Fatura** =
  período de cartão da empresa, com vencimento.
- **Envelope**: o que sobra na gaveta ao fechar = `saldofinal` da sessão = `saldoinicial` da próxima.
  Desde o M13 o dinheiro sobe ao escritório; a sangria leva o excesso ao cofre e a contagem do
  fechamento é o que fica (o envelope). Diferença na abertura ou no fechamento = ajuste (decisão 21).
- **Item do caixa**: mercadoria de parceiro fora do fiscal (chips, ingressos, maquinetas de
  terceiros) que passa pela gaveta e vira título de repasse ao parceiro (Duplicata a Pagar, 200;
  Duplicata a Receber, 100, se o líquido for negativo) no fechamento. Modo **C** contagem (o estoque
  a valor de face fica na gaveta e entra na contagem) e **M** maquineta/terceiro (vendido, entrada,
  saída).
- **Maquineta** (`tblmaquineta`): cadastro único dos terminais de cartão, integrados ou não (um por
  POS PagarMe, um por pinpad Saurus, manuais e acessos de site). A adquirente é dado dela.
  **Compartilhada** = aparece no PDV de todas as filiais (acesso de site feito numa filial só).
- **Lote** (`tblmaquinetalote`, M9): o borderô da maquineta. Sempre um aberto por maquineta; o
  cartão cai nele; o gerente fecha digitando crédito e débito do borderô, com a foto.
- **Conferência** (M9): o "conferi" do gerente sobre o que o caixa movimentou — sessão da gaveta,
  lote, cheque, vale recebido, duplicata (confissão), venda desbalanceada. Na sessão da gaveta,
  desde o M13, **fechar é a conferência** (sem etapa às cegas; quem fecha é o caixa ou o gerente). **Pendência** = conferência
  ainda não confirmada; a tela Fechamentos do contas lista as da filial.
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
| M6.1 | Wizard, formas e integrações em `@components`; contas usa o wizard; listagem única de pagamentos; receber notinha e pagar vale do cliente no PDV (absorve o M7) | Fundação | contas → Receber ou Pagar Títulos (cartão com bandeira/autorização/parcelas/maquineta), Pagamentos; negocios → Receber título / Pagar vale, Pagamentos | **Alto** |
| M7 | (absorvido pelo M6.1) | — | — | — |
| M8 | Vale colaborador e adiantamentos, no PDV e no contas | Receber no balcão | negocios e contas → Pagamentos → Vale / Adiantamento | Baixo |
| M9 | Conferências e fechamento do caixa: sessão da gaveta, lote da maquineta, documentos, correção de lançamentos, vendas desbalanceadas (redesenhado em 02/10/2026) | Receber no balcão | negocios → Caixa; contas → Fechamentos, Maquinetas → lotes | **Alto** |
| M10 | Razão do dinheiro (`tblportadormovimento`) e saldo do portador; `transacao` = fato gerador em pagamento, cheque, extrato e bonificação; a sessão da gaveta foi para o M9 | TASK-39 | contas e PDV → detalhe do pagamento (card Razão); listagens com o filtro de período | Médio |
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
    no mesmo dia. Desde o M10 a coluna é `tblportadormovimento.transacao` (timestamp), e a data do
    ato no pagamento é `tblpagamento.transacao` (era `lancamento`).
17. **Pagamento tem as mesmas colunas de valor do movimento de título**: `principal`, `juros`,
    `multa`, `desconto` e `total` (não existe coluna `valor`). `total` = o que andou de dinheiro por
    aquele meio = principal + juros + multa − desconto; `valortroco` à parte (a NF-e precisa do
    troco). Na venda: juros do parcelamento (12x no cartão) e desconto por pagar à vista (PIX,
    dinheiro) ficam no pagamento e são rateados nos itens como hoje se faz com o juros
    (`valorjuros` do item → vOutro; `valordesconto` do item → vDesc); conferência: Σ principal dos
    pagamentos = Σ itens; Σ total dos pagamentos + Σ parcelas = total da venda (o troco fica fora do
    `total`; a NF-e manda vPag = total + troco). Na baixa de título: o pagamento
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
25. **Devolução sempre gera o crédito do cliente** (título **Crédito Cliente, 212**; até a limpeza
    dos tipos do M8.1 era Vale Compras), usado no PDV como vale. Devolver dinheiro é
    **pagar esse crédito**: em dinheiro (gaveta), registrando o cancelamento no cartão (origem =
    adquirente, aponta para o pagamento original), registrando a devolução de PIX (origem = banco,
    aponta para o PIX original), ou por PIX/transferência comum pelo financeiro. Cancelamento e
    devolução são **só registrados** nesta fase; executar pela integração fica no M15.
26. **Duas conferências de cartão, separadas do caixa**: do lote (gerente: lançado × borderô da
    maquineta, M9 — era "do dia", mudou no redesenho do M9 em 02/10/2026) e do repasse (financeiro:
    adquirente × banco, taxa e prazo, M14). O fechamento da gaveta cuida só de dinheiro e itens;
    cartão e PIX aparecem nele como informação.
27. **Itens do caixa** (fora do fiscal): modo contagem (chips, ingressos impressos) e modo maquineta/
    terceiro (Bilhete Agora, BlackTicket, Redeflex, Bradesco Expresso). Cada item tem parceiro e
    conta contábil; no fechamento da sessão o líquido vira título de repasse (Duplicata a Pagar, 200,
    com pessoa e conta do item; sem tipo próprio desde a limpeza dos tipos do M8.1),
    número `AAAA-MM-DD-P{id do período}`, um por sessão por item; o financeiro agrupa e paga. Reabrir
    sessão estorna esses títulos (422 se já movimentados). (M13: líquido negativo gera Duplicata a
    Receber, 100; item sem pessoa só informa; detalhe na seção M13.)
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
| meio | smallint NN | código NF-e: 1 dinheiro, 2 cheque, 3 crédito, 4 débito, 12 vale, 15 boleto, 16 depósito, 17 PIX, 18 transferência, 99 outros (Mercos Pay, cartão do histórico sem débito/crédito); internos: 91 compensação, 92 folha, 93 permuta, 94 perda |
| estado | char(1) NN | P pendente, E efetivado, C cancelado |
| principal | numeric(14,2) NN | > 0; o que foi quitado (itens da venda, saldo de títulos) |
| juros, multa, desconto | numeric(14,2) NN default 0 | positivos; venda: juros do parcelamento e desconto por meio; título: consolidação dos movimentos |
| total | numeric(14,2) NN | o que andou de dinheiro por este meio = principal + juros + multa − desconto |
| valortroco | numeric(14,2) | dinheiro; fora do `total` |
| codtitulo | bigint FK | vale consumido como pagamento (meio 12), antes de virar movimento (M4) |
| parcelas | smallint | cartão de crédito |
| transacao | timestamp NN | data e hora do fato gerador (era `lancamento`; renomeada no M10) |
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
| codportadorperiodo | bigint FK | sessão da gaveta do dinheiro (M9, `CaixaService::vincular`) |
| codcaixaitemlancamento | bigint FK | item do caixa (M13) |
| codperiodocolaboradoracerto | bigint FK | acerto de RH (M6) |
| observacoes | varchar(300) | |
| criacao, codusuariocriacao, alteracao, codusuarioalteracao | | audit |

Índices: `codnegocio`, `(estado, transacao)`, `codportadororigem`, `codportadordestino`,
`(codmaquineta, transacao)`, `codpagamentoorigem`, `uuid` único.

### `tblnegocioparcela` (M4)

| Coluna | Observação |
|---|---|
| codnegocioparcela PK, uuid, codnegocio NN | |
| condicao char(1) NN | F fechamento (último dia útil do mês seguinte, seg a sáb sem feriado), P parcelado (30/60/90), B boleto, E entrega, X PIX/depósito a receber, V vale da devolução (+1 ano) |
| numero smallint, vencimento date NN, valor numeric NN | |
| codtitulo bigint FK | preenchido ao fechar |
| juros numeric NN default 0 | parte de juros do valor (crediário) |
| uuidforma uuid | forma do PDV antigo que gerou as parcelas; some no M5 |
| audit | |

Histórico: uma parcela por título que apontava para forma de pagamento a prazo (vencimento e valor
do título; condição pela forma antiga: 3010/3020/5601 → F, 5100 → P, 4100 → B, 1099 → E, 5606 → X).
`tbltitulo.codnegocioformapagamento` vira `codnegocioparcela`.

### `tblmovimentotitulo` (M1)

`valor` → **`principal`** (efeito no saldo, com sinal); novas `juros`, `multa`, `desconto`
(numeric NN default 0, CHECK ≥ 0) e `total` (numeric NN default 0, valor efetivo da baixa —
dinheiro que andou, ou o que foi levado ao agrupamento —, sinal do principal). Na baixa: |total| =
|principal| + juros + multa − desconto. Implantação e ajuste: total 0. Script
`api/database/movimento_titulo_colunas.sql` (M1). `codpagamento` (FK) já existe desde o M4 (vale
usado no PDV); em M6 é preenchido pela liquidação e perde
`codliquidacaotitulo`.

### `tblportador` (M2)

`tipo char(1) NN default 'O'` (E, B, A, C, O). Config de prazos/taxas de cartão fica para o M14.

### `tblmaquineta` (M3)

`codmaquineta PK, apelido varchar(50) NN, serial, codfilial NN, compartilhada bool NN default false,
codpessoa NN (adquirente), integracao char(1) (nulo manual | P PagarMe | S Saurus), codpagarmepos
(único), codsauruspinpad (único), inativo, audit`. CHECK: manual sem POS/pinpad; P só com POS; S só
com pinpad. `tblpagamento.codmaquineta` (era `tblnegocioformapagamento.codmaquineta` no M3; FK, nulo só no
histórico sem parceiro). Carga
e regras no M3. `tblmaquinetaconferencia` (M9): `codmaquineta, dia, credito, debito, observacoes,
codusuario, audit`, única por maquineta e dia.

### `tblportadorperiodo` (criada no M9, `conferencia.sql`; colunas de conferência na seção M9)

`codportadorperiodo PK, codportador NN, inicio NN, fim (nulo = corrente), fechamento (nulo = aberto),
codusuarioabertura, codusuariofechamento, saldoinicial NN default 0, saldofinal, moedasabertura,
cedulasabertura, moedasfechamento, cedulasfechamento, conferencia, codusuarioconferencia,
valorconferido, observacoes, audit`; o M13 (`caixa_item.sql`) acrescentou
`codpagamentoabertura`/`codpagamentofechamento` (ajuste de caixa da sessão) e
`contagemabertura`/`contagemfechamento` (jsonb `{"200": 3, "0.05": 12}`, quantidade de cada cédula
e moeda; `moedas*`/`cedulas*` continuam gravados, calculados). Único corrente por portador (`fim IS
NULL`). Ainda não existe: `vencimento` (fatura, M14). Cofre, banco e adquirente ganham o corrente sozinhos no primeiro
lançamento do razão (M10).

### `tblportadormovimento` (M10; recriada, a atual está vazia)

`codportadormovimento PK, codportador NN, codportadorperiodo NN, codpagamento NN, valor NN (com
sinal: positivo entrou), transacao timestamp NN (quando cai naquele portador), parcela smallint
(cartão, M14), conciliado bool NN default false, inativo, audit`. Toda linha nasce de um
pagamento (`api/database/razao.sql`). `tblextratobancarioportadormovimento` (existe) continua
sendo a amarração com o extrato para o M14.

### Itens do caixa (M13, `api/database/caixa_item.sql`)

`tblcaixaitem` (`item varchar(50), modo C|M, codfilial nulo = todas, codpessoa, codcontacontabil,
ordem, inativo`, CHECK pessoa ⇒ conta; 6 seeds sem pessoa) e `tblcaixaitemlancamento`
(`codportadorperiodo, codcaixaitem, valorabertura, valorfechamento, valorvendido, valorentrada,
valorsaida (NN default 0), observacoes, codpagamento, codtitulo`, único por período+item, valores ≥
0). `tblpagamento.codcaixaitemlancamento` (o pagamento aponta para o documento, decisão 3;
`codpagamento` do lançamento é o atalho para o mesmo registro).

### O que some

`tblnegocioformapagamento` (vira view temporária), `tblliquidacaotitulo` (idem), `tblportadortransferencia` (caiu no M10, `razao.sql`),
`vwnegocioformapagamento` (`vwnegocioformapagamentototais` ficou no M4, redefinida sobre pagamento
e parcela, porque `vwnegocio`/`vwnegocio_listagem` dependem dela). `tblformapagamento` fica
congelada, só para a view e a forma padrão do cliente (`tblpessoa.codformapagamento`); a tela sai
quando ninguém mais usar. Tipos de movimento 400, 401, 500 e os estornos deles ficam só
para leitura de histórico não convertido.

---

## Convenções confirmadas no código

- **Títulos depois do M0**: `valor`/`saldo` com sinal (positivo = a receber); movimento com
  `principal`, `juros`, `multa`, `desconto`, `total` (M1; `lancar(titulo, tipo, principal, [juros,
  multa, desconto, total], vinculos, unicoPor)`, sem total a baixa calcula), estorno com o tipo do
  original + `codmovimentotituloestorno`; implantação sempre
  100 (`TituloService::implantar`); escritor único `MovimentoTituloService::lancar`; `Titulo::ehReceber()`;
  `tbltipotitulo.natureza` (R/P) e `movimentaportador` (120 Vale Colaborador, 121 Adiantamento
  Fornecedor, 211 Adiantamento Cliente — códigos novos da limpeza do M8.1; antes 2, 120, 220, 230).
  `debito`/`credito` de `tblliquidacaotitulo` ainda existem só para o Totais de
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
  cartão manual. PDVs de caixa: 17 com `alocacao = 'C'`. (Os nomes de arquivo deste levantamento
  são de antes do M6.1; onde as peças estão hoje, no item abaixo.)
- **Tipos de título depois do M8.1** (`api/database/tipo_titulo_limpeza.sql`; detalhe na seção
  M8): ativos 100 Duplicata a Receber, 101 PIX/Depósito Receber, 102 Entrega Receber, 111 Cheque
  Devolvido, 120 Vale Colaborador, 121 Adiantamento Fornecedor, 122 Débito Fornecedor, 200
  Duplicata a Pagar, 201 PIX/Depósito Pagar, 202 Entrega Pagar, 210 Vale Compras, 211
  Adiantamento Cliente, 212 Crédito Cliente (devolução), 220 Rubrica RH; inativos de histórico 130,
  131, 132, 133, 230. Código usa constantes (`TituloService::TIPO_DUPLICATA_RECEBER/PAGAR`,
  `TIPO_VALE`, `TIPO_CREDITO_CLIENTE`, `TIPO_RH`; `TipoTituloService::TIPO_PIX_*`/`TIPO_ENTREGA_*`),
  nunca número solto. `movimentaportador` = 120, 121, 211. Agrupamento gera 100/200 (sem tipo
  próprio). No PDV, **qualquer título com saldo de crédito paga compra** (meio vale, tPag 12).
- **Pagamento e cobrança depois do M6.1** (vale para M8 em diante; detalhe na seção M6.1):
  - Wizard, formas e integrações só em `@components`: `MgCobrancaDialog.vue`, `cobranca/`
    (`Forma*.vue`, `ListaOpcoes`, `PixCobDialog`/`PagarMePedidoDialog`/`SaurusPedidoDialog`,
    `pagamento.js` com MEIO/VISUAL/CONDICAO, `juros.js`, `eventos.js`, `integrada.js`) e
    `stores/{cobrancaStore, pixStore (id pixCob), pagarMeStore, saurusStore}.js`. Nenhuma cópia em
    app: forma nova entra em `@components` e na lista `FORMAS` do `MgCobrancaDialog`.
  - Quem abre o wizard informa `documento` (o que se paga: `tipo` 'negocio' ou 'titulos', com os
    tratadores `aoPagamento`/`aoParcelas`/`aoCobranca` ou, sem eles, os eventos do dialog),
    `contexto` (onde: `pdv` uuid ou nulo no contas, `codfilial`, `carregarMaquinetas`,
    `portadores`, `buscarVale`) e `formasPermitidas`. Um `MgCobrancaDialog` por tela (venda: no
    `TotalNegocio`; Pagamentos do PDV: na página, para o Vale / Adiantamento; Receber Título e
    contas: dentro da `MgBaixaTitulos`).
  - Baixa de títulos (PDV e contas) = a mesma tela, `@components/MgBaixaTitulos.vue` (seletor
    `MgSeletorTitulosAbertos` + pessoa/observação + formas lançadas + wizard; cada app passa onde
    busca, as formas, o contexto e para onde manda) sobre `@components/stores/baixaTitulosStore.js`
    (títulos → formas lançadas → finalizar) + `Mg/Pagamento/PagamentoTituloService::baixar(dados, ?Pdv)`: um
    pagamento por forma, linhas distribuídas por vencimento, portador resolvido pelo meio
    (dinheiro na gaveta do PDV; no contas, cofre/troco/Caixa Financeiro; cheque recebido na
    Carteira; cartão na adquirente da maquineta). Rotas: contas `POST v1/pagamento`; PDV
    `POST v1/pdv/pagamento` (`PdvPagamentoService`, pagar vale só Gerente/Admin).
  - Cobrança integrada sem negócio: o pagamento nasce efetivado e sem documento na confirmação
    (`PixService::processarPixCobNegocio`, `PagarMeService`/`SaurusService::vincularPagamento`);
    quem criou a cobrança o amarra mandando `codpagamento` na forma. Criar cobrança sem PDV:
    `Mg/Pagamento/CobrancaService` + `v1/cobranca/{pix, pagar-me, saurus}`.
  - Listagem única: `PagamentoListaService` (origem V venda / T títulos / X transferência / A
    avulso, filtros), `PagamentoListaResource`/`PagamentoDetalheResource`, `PagamentoController`
    (`v1/pagamento`, contas) e `PdvPagamentoController` (`v1/pdv/pagamento`, travada no PDV); no
    front `MgPagamentoLista`/`MgPagamentoFiltros`/`MgPagamentoDetalhe` + `stores/pagamentoListaStore`.
  - Vale colaborador e adiantamentos (M8.1): `@components/MgAdiantamentoDialog.vue` +
    `Mg/Titulo/TituloAdiantamentoService::lancar(dados, ?Pdv)`; rotas `POST v1/pdv/titulo` e `POST
    v1/titulo/adiantamento`; botão na tela Pagamentos dos dois apps. Contexto do wizard no contas em
    `contas/src/utils/cobranca.js` (`contextoCobranca`, `FORMAS_TITULOS`, `FORMAS_ADIANTAMENTO`).
    Wizard com uma forma só permitida entra direto nela.
  - Maquinetas no cartão (ajuste do M6.1): lista as de todas as filiais, as de outra filial num
    grupo próprio (`MaquinetaService::paraPdv` devolve `outrafilial`), sem bloquear.
- **Razão e `transacao` depois do M10** (detalhe na seção M10):
  - `transacao` = data e hora do **fato gerador**; `criacao` = quando foi digitado. Vale para
    pagamento, cheque, extrato bancário, bonificação, razão, título e movimento de título. O
    negócio ainda usa `lancamento` (TASK-194): ao ler data, conferir de qual tabela ela é.
    Filtro de período da listagem de pagamentos: `transacao_de` / `transacao_ate`.
  - Razão = `Mg/Portador/PortadorMovimentoService::sincronizar(Pagamento)`, **chamado
    explicitamente** por quem grava pagamento efetivado ou muda um (`PagamentoService::{criar,
    contrario, efetivar, cancelar}`, `PagamentoTituloService::{pagamentoDaForma, daBaixa,
    atualizar}`, `PagamentoCorrecaoService::{corrigir, incluir}`). Caminho novo que grave
    pagamento precisa chamá-lo. Idempotente: inativa as linhas que sobram e cria as que faltam.
    Lança hoje dinheiro, boleto, depósito, PIX e transferência efetivados com `transacao` a partir
    do `CONFERENCIA_INICIO`.
  - Períodos e saldo: `Mg/Portador/PortadorPeriodoService::{corrente, imutavel, descricao,
    saldo}`. Gaveta = sessão do M9 (`CaixaService`); os demais portadores ganham o corrente
    sozinhos. Imutável: gaveta conferida; demais, fechados.
- **Caixa depois do M13** (detalhe na seção M13):
  - Uma tela só: `@components/MgCaixaSessao.vue` + `stores/caixaSessaoStore.js` (subpeças em
    `@components/caixa/`), usada pelo negocios `/caixa` (gaveta do PDV) e pelo contas Fechamentos →
    caixa. Rotas `v1/caixa/...` (usuário: Caixa/Gerente da filial, Financeiro, Admin); o PDV só
    pergunta a gaveta em `v1/pdv/caixa`. Transferências da gaveta pelas rotas do contas
    (`v1/pagamento/transferencia`) nos dois apps.
  - `CaixaService::fechar` grava também `conferencia`: o razão trava no fechamento; corrigir
    pagamento da sessão é reabrindo. `CaixaService::pagamentoNaGaveta` mantém o pagamento de
    ajuste e de item (sempre o mesmo registro; zero cancela, voltar reativa).
  - Listagem única: origem **I** "Item do caixa".
- **Transferências e períodos depois do M11/M12** (detalhe nas seções):
  - Transferência = pagamento com os dois lados e sem venda (`PagamentoService::ehTransferencia`);
    nasce no `PagamentoService::transferir`, confirma no `confirmar`, cancela no
    `cancelarTransferencia`; donos de cada lado em `Pagamento/TransferenciaAutorizador`. O razão
    lança a pendente ("a confirmar" = estado P do pagamento).
  - Lançamento de não-gaveta cai no período **da data** (`PortadorPeriodoService::doMomento`), não
    mais sempre no corrente; fechado = 422. Fechar/reabrir/avulso: `PortadorPeriodoService::{fechar,
    reabrir, lancar}`, rotas `v1/portador-periodo`.

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
- **O que mudou em relação ao plano** (conferência de 30/09/2026 no banco e no código, decidido
  item a item com o Fábio):
  - **Serial digitado** (`serialmaquineta`, nasceu na TASK-100): casa com a maquineta pelo **serial +
    filial do negócio** (ativa primeiro); se não achar, vira **maquineta manual daquela filial**
    (apelido = serial, adquirente = parceiro do pagamento). Mesma regra na carga e no PDV antigo que
    ainda manda serial. O mesmo aparelho usado em duas lojas vira duas maquinetas.
  - **Carga com todos os aparelhos**, não só os ativos: 89 POS PagarMe (85 inativos) e 31 pinpads,
    senão ~560 mil pagamentos PagarMe do histórico ficariam sem maquineta.
  - **Saurus: maquineta = pinpad** (como no plano). Cada re-pareamento cria um pinpad novo no mesmo
    PDV Saurus (7 PDVs têm 2 ou 3, de fev–mar/2025): o substituído entra **inativo**; parear de novo
    cria a maquineta nova e inativa a anterior do mesmo PDV Saurus. Apelido vem do PDV Saurus. A
    cobrança continua indo ao PDV Saurus (maquineta → pinpad → PDV Saurus).
  - **Cartão manual no PDV continua perguntando o parceiro**; depois lista as maquinetas daquele
    parceiro na filial (recentes primeiro, sem digitar) e **pula a etapa quando só há uma**.
    Maquineta **obrigatória em todo cartão manual** (422 no `fechar`).
  - **Brasil Card, Le Card e MultVale** são vendidos pelo site do parceiro, com um acesso só (cobram
    por usuário) feito numa filial e usado por todas: uma **maquineta de site por parceiro**, filial
    101, com a coluna nova **`compartilhada`** (aparece no PDV de todas as filiais). MultVale física,
    onde houver, é cadastrada como manual na filial dela.
  - **PDV antigo** (ainda sem a lista): serial → maquineta pela regra acima; sem serial (parceiros
    de site), usa a maquineta do parceiro se for a única.
  - **Histórico sem aparelho**: "Histórico Stone", "Histórico SafraPay" e "Histórico Cielo Lio"
    **inativas, por filial** (PagarMe 2021–2022 sem pedido, manuais Stone/Safra sem serial, Cielo
    Lio); o manual antigo de Brasil Card/Le Card/MultVale aponta para a maquineta do site. A pessoa
    **Cielo S.A.** (CNPJ 01.027.058/0001-91) é criada pelo script. Ficam sem maquineta só os cartões
    manuais antigos sem parceiro (478 mil), por isso a coluna aceita nulo.
  - **Permissão** do cadastro: Admin e Financeiro em todas as filiais; **Gerente só na própria**.
  - **Juntar maquinetas** entra no M3: a manual criada por serial errado é juntada na certa (mesma
    adquirente); os pagamentos passam para a certa e a errada é excluída.
  - **Tela Saurus/S2Pay sai do negocios** e vai para contas → Maquinetas: nova maquineta SafraPay
    (filial + apelido → QR → pinpad lê → confere), parear de novo, editar e inativar (replicando no
    PDV Saurus/pinpad). **POS PagarMe cadastrado dentro da maquineta** (serial + filial + apelido;
    editar e inativar replicam no POS); o webhook continua criando sozinho o POS de serial
    desconhecido, já com maquineta. A tela PagarMe do negocios (pedidos pendentes) fica onde está.
  - Resultado da carga em dev (`api/database/maquineta.sql`): maquinetas PagarMe 4 ativas + 85
    inativas, Saurus 21 + 10, site 3, manuais 3, histórico 14 inativas; pagamentos com maquineta:
    PagarMe 566.261, Saurus 201.336, histórico 288.186, site 835, manuais 7; sem maquineta 478.509.
    Conferência dentro do script (aborta se sobrar cartão com pedido, serial ou parceiro sem
    maquineta). Em dev os seriais digitados são de teste (`123123123`, `asdasdasd`).
  - Como ficou no código: domínio `Mg/Maquineta` (`MaquinetaService`: CRUD, inativar/ativar
    replicando no POS/pinpad/PDV Saurus, `juntar`, `daPagarMePos`, `daSaurusPinPad`,
    `parearSaurusPinPad`, `resolverSerial`, `unicaDoParceiro`, `paraPdv`); rotas `v1/maquineta`
    (+ `adquirente`, `saurus/qrcode` com QR em SVG gerado na API, `saurus/confirmar`, `{id}/inativo`,
    `{id}/juntar`) e `v1/select/maquineta`. Registro e leitura do PDV Saurus saíram do
    `PdvController` para `SaurusService::{registrarPdv, verificarLeitura}`; as 6 rotas
    `v1/pdv/saurus/{registrar-pos, verificar-leitura, pdvs, pdv/…}` foram removidas. PagarMe e
    Saurus gravam `codmaquineta` no `vincularNegocioFormaPagamento`; `PdvNegocioService`
    resolve o serial do PDV antigo no sync e recusa (422) cartão manual sem maquineta no
    `fechar`. O estoque local do PDV traz `MaquinetaS` (ativas da filial + compartilhadas); o
    `FormaCartao` sincroniza sozinho se ainda não tiver a lista. O serial físico da SafraPay fica
    só na maquineta (`tblsauruspinpad.serial` vai como `IdPinPad` na cobrança e continua nulo).
  - O cartão manual continua gravando `serialmaquineta` só no PDV antigo; o novo manda
    `codmaquineta`. A coluna some com a tabela no M4.
  - **Parceiros do cartão manual** (ajuste depois da validação): além dos fixos de
    `cartoes-manuais.json` (logo, tipos, bandeiras), aparece toda adquirente com maquineta ativa na
    filial (ex.: Cielo), sem logo e aceitando o mesmo que a Stone (débito, crédito, voucher e as
    bandeiras dela). Parceiro fixo sem maquineta na filial aparece desabilitado ("Nenhuma maquineta
    nesta filial"), para as teclas não mudarem de loja para loja. O Receber → Cartão busca a lista
    de maquinetas de novo a cada abertura (online): maquineta cadastrada no contas aparece sem
    sincronizar à mão.
  - **Pareamento SafraPay por QR só é validado no go-live**, em produção, com um pinpad reserva:
    o `api/.env` do dev não tem as credenciais da Saurus (`SAURUS_S2PAY_*`) e os PDVs Saurus do
    banco de dev têm os mesmos ids dos de produção na conta Saurus (parear no dev re-parearia o
    terminal real da loja). No dev a falha da API volta como 502 com mensagem.

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
- **O que mudou em relação ao plano** (conferência de 30/09/2026 no banco e no código, decidido
  item a item com o Fábio):
  - **Levantamento**: 68 PHP + 2 blades (não 52), além de `NegocioService::gerarTitulos` (segundo
    gerador de títulos, fluxo sem PDV), `LioService`, `MercosPedidoService`,
    `PdvNegocioDevolucaoService`, `PdvAnexoService`, `ControleController`, `PdvValeEscopoService`,
    `ValeService`, `TituloAgrupamento*`, `BoletoBbService` e os filtros da listagem do PDV. O grupo
    `pag` da NF-e sai de `tblnotafiscalpagamento`, copiada do negócio ao gerar a nota: só a cópia
    (`NotaFiscalNegocioService::formasDoNegocio`) mudou. `tblportadormovimento` (vazia) só perdeu a
    coluna; `tblcheque` apontando para forma: 0 em dev. `tblpessoa.codformapagamento` (forma padrão
    do cliente) continua apontando para a `tblformapagamento` congelada.
  - **MGsis grava**, não só lê: a NFe de Terceiros fazia `INSERT` da forma 3010 e gravava
    `tbltitulo.codnegocioformapagamento`. `NfeTerceiro.php` passa a gravar uma parcela (F) por
    título e `Titulo.php`/`_grid_titulos.php` passam a `codnegocioparcela`; model novo
    `NegocioParcela.php`. **Sobem junto.** A view atende só o Totais de Caixa do MG Lara.
  - **Vale gerado na devolução** (forma 1030, tipo 90, título Vale Compras a pagar) = parcela com a
    **condição nova V**, título pelo tipo da natureza, como numa compra (vence em 1 ano, `N…-DEV`).
  - **`tblpagamento.codtitulo`** (coluna nova) = vale consumido como pagamento (meio 12): o vale é
    escolhido com a venda aberta e só vira movimento no fechar.
  - **Troco**: na tabela antiga `valorpagamento` era o entregue (troco dentro). No pagamento,
    `principal` = entregue − troco, `total` = principal + juros + multa − desconto (o que ficou),
    `valortroco` à parte; conferência Σ total dos pagamentos + Σ parcelas = total da venda (não
    "Σ total − troco"); NF-e manda vPag = total + troco.
  - **Sem coluna da forma antiga**: o código (`codformapagamento`) é deduzido de meio, condição e
    integração (`NegocioFormaPagamentoService`, que vira só o tradutor do formato antigo e sai no
    M5). Perde-se no histórico a diferença entre Fechamento B/C/D (volta 3020) e o PagarMe sem
    pedido de 2021–2022 (volta 2010). Mercos Pay = meio 99 com destino no portador Mercos Pay
    (202046).
  - **Parcela do histórico = título** (valor e vencimento do título); forma a prazo sem título
    (negócio cancelado ou aberto) = uma parcela com o valor da forma. `tblnegocioparcela` ganhou
    `juros` (o juros do crediário, para Σ juros = `valorjuros` do negócio) e `uuidforma` (a forma do
    PDV antigo que gerou as parcelas; o sync recalcula as parcelas em aberto por ela; sai no M5).
    Vencimento no sync: a partir do dia do sync (o fechamento calculava do dia de fechar).
  - **Cartão sem débito/crédito no histórico** (Cartao manual 2011–2024, Cielo Lio, PagarMe sem
    pedido) = **meio 99 outros**; PagarMe com pedido usa o tipo do pedido. O meio 99 entra na lista.
  - **Último dia útil** = segunda a sábado, sem feriado (`tblferiado`), como o RH.
  - **Histórico corrigido**: formas de valor zero não são copiadas; os 4 cartões negativos
    (estorno lançado na própria venda, 2025–2026) viram **pagamento contrário** (origem = portador
    da adquirente, `codpagamentoorigem` = cartão da mesma autorização quando existe); troco lançado
    em dobro (138 formas de 2024–2025 com troco maior que o pago) é redistribuído no negócio como o
    PDV faz hoje (maior pagamento primeiro); CHECK `principal > 0` (zero só na compensação, M6). O
    PDV antigo que ainda manda cartão negativo gera o mesmo pagamento contrário.
  - Forma antiga **incoerente** (`valortotal` ≠ `valorpagamento` + juros, 7 negócios em dev): o
    pagamento sai do `valorpagamento`; a conferência lista esses negócios em vez de abortar.
  - **Integrações** (PIX QR, PagarMe, Saurus, Lio) criam o pagamento já **efetivado**; pedido
    PagarMe zerado **cancela** o pagamento (antes apagava a forma). O sync do PDV não grava nem
    apaga pagamento integrado nem pagamento que já saiu de pendente.
  - **`vwnegocioformapagamentototais` não cai**: `vwnegocio` e `vwnegocio_listagem` (sem uso no
    código; só SQLs avulsos de `MGdb/SQLs`, como o fechamento de comissões) dependem dela. Ela foi
    redefinida sobre pagamentos e parcelas, com as mesmas colunas. `vwnegocioformapagamento` cai.
  - O cadastro Formas de Pagamento do contas **continua com tela** neste milestone (fora do
    escopo pedido); a tabela fica congelada para a view e para a forma padrão do cliente.

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
  quando não houver mais cliente antigo. Herança do M4: o tradutor do formato antigo é
  `NegocioFormaPagamentoService` (`importar`, `formaAntiga`, `filtroForma`/`filtroIntegracao` da
  listagem, códigos deduzidos de meio/condição); `tblnegocioparcela.uuidforma` agrupa as parcelas da
  forma antiga e o sync as recalcula a cada envio (vencimento a partir do dia do sync) — no formato
  novo as parcelas vêm do PDV por `uuid`, com vencimento e valor editados, e o sync não recalcula.
  Filtro da listagem do PDV passa a meio/condição; `PdvNegocioService::fechar` já confere Σ total +
  Σ parcelas = total e Σ juros = `valorjuros`.
- **Valida**: venda exatamente como antes em todas as formas, F6 a F9, pagamento dividido, cancelar
  cobrança, fechamento automático; prazo com vencimentos editados virando títulos com as datas
  certas; fechamento mensal caindo no último dia útil do mês seguinte.
- **O que mudou em relação ao plano** (conferência de 30/09/2026 no banco e no código):
  - **O formato antigo sai já, no go-live** (decisão do Fábio, 01/10/2026): o sync lê e o
    `NegocioResource` devolve só `pagamentos` e `parcelas` no formato novo
    (`Mg/Pdv/PdvNegocioPagamentoService`), e os endpoints de cobrança devolvem a cobrança criada
    (PixCob/pedido). `NegocioFormaPagamentoService` foi apagado (bandeiras, tPag e o portador do
    Mercos Pay foram para `PagamentoService`; tPag da condição para `NegocioParcelaService`);
    `tblnegocioparcela.uuidforma` cai no `cobranca_documento.sql` (só servia à cópia do histórico,
    e a view `tblnegocioformapagamento` do `pagamento.sql` passou a agrupar parcelas por
    condição). PDV com aba aberta na versão antiga precisa recarregar a página depois do deploy.
    O romaneio, a DIMP e o grupo `pag` da nota leem pagamento e parcela direto.
  - **Formato novo no PDV** (Dexie v8): pagamento = `meio`, `principal`, `juros`, `desconto`,
    `total`, `valortroco`, maquineta, cheque, vale (`codtitulo`); parcela = `condicao`, `numero`,
    `vencimento`, `valor`, `juros`. No PDV o dinheiro entregue fica como `total + valortroco` e o
    troco continua redistribuído entre os pagamentos em dinheiro quando o saldo muda. A migração v8
    converte os negócios do aparelho (prazo antigo vira parcelas com o vencimento sugerido) e marca
    os abertos para sincronizar de novo.
  - **PIX por chave e entrega são parcela** (X e E, vencendo no dia), como o M4 já gravava.
  - **Prazo**: condição → plano (como antes) → editor com vencimento e valor de cada parcela; a
    última absorve a diferença ao mexer no valor de outra; foco inicial no valor da 1ª (Enter
    lança com as datas sugeridas; Shift+Tab chega ao vencimento). Vencimento sugerido pela regra do
    servidor (fechamento = último dia útil, seg a sáb, sem feriado; feriados baixados de
    `v1/feriado` uma vez por dia, offline usa o último que baixou). O sync grava por `uuid` e não
    recalcula; o `fechar` recusa (422) parcela vencida antes de hoje. Títulos numerados por
    condição (`N…-1/2`), em ordem de vencimento.
  - **Juros editável** no cartão de crédito: plano com juros abre uma etapa com o juros sugerido.
  - **Desconto por forma só no dinheiro** (tecla − no passo do valor, sugestão 0%); a regra
    completa (forma de pagamento + categoria de cliente) é a TASK-190. O desconto fica no pagamento
    e o PDV o **rateia no `valordesconto` dos itens e vales** (decisão do Fábio, 01/10/2026),
    pelos pesos do juros, ordem por uuid e sobra no último. Cada item/vale guarda a sua fatia em
    `valordescontopagamento` (só no PDV; o `NegocioResource` devolve a mesma conta,
    `PdvNegocioPagamentoService::ratearDesconto`), para refazer o rateio sem perder o desconto
    digitado; os dialogs de item, vale e cabeçalho editam só o digitado. Assim a conferência e a
    nota continuam as de sempre (desconto do item → vDesc). PIX ficou sem desconto: o QR não tem
    onde guardar o desconto (`tblpixcob`) e PIX por chave é parcela.
  - **Cobrança por documento**: `cobranca.js` cria PIX QR/PagarMe/Saurus para o documento (hoje o
    negócio; `codnegocio` opcional, com `codpessoa`), `tblsauruspedido.codnegocio` aceita nulo
    (`api/database/cobranca_documento.sql`), e as stores `pix`/`pagar-me`/`saurus` só avisam
    (`cobrancaAtualizada` com o `codnegocio`); quem estiver com o negócio aberto recarrega. O
    pagamento de cobrança sem negócio ainda não nasce (fica para o M7, que tem o título).
  - Filtro de forma da listagem do PDV: `forma[]` com `m<meio>` e `c<condição>`; o
    `codformapagamento` continua para o PDV antigo.
  - Saiu do `.env` do negocios todo `CODFORMAPAGAMENTO_*` (dev). A tabela `formaPagamento` do Dexie
    fica: mostra a forma padrão do cliente. O romaneio passou a listar pagamentos (com desconto e
    troco) e parcelas por condição.
  - Conferência em dev: 20 cenários por serviço com rollback (dinheiro com troco e com desconto, PIX
    QR com e sem negócio, PIX chave, PagarMe 12x com juros, Saurus com e sem negócio, cartão manual,
    cheque, vale + dinheiro, fechamento com vencimento editado, crediário, boleto sem registrar no
    BB, entrega, dividido em 3, vencimento passado, sync repetido, formato antigo, cancelar), todos
    com Σ total + Σ parcelas = total; NFC-e da venda com desconto (vDesc rateado, vPag 100 com troco
    5); e no navegador, dinheiro com desconto + crediário 2x editado fechando sozinho.

## M6 — Pagamento no lugar da liquidação (Fundação)

- **DDL** (seção 2 de `pagamento.sql`): copia `tblliquidacaotitulo` para `tblpagamento` (código
  novo, `codliquidacaotituloantigo`, `meio` derivado do portador antigo: espécie → dinheiro; banco →
  transferência; pseudoportadores → compensação/folha/permuta/perda; estado E ou C se estornada;
  origem/destino pelo sentido); `tblmovimentotitulo.codpagamento` (já existe desde o M4, com os vales
  usados no PDV; backfill pelo antigo `codliquidacaotitulo`), derruba `codliquidacaotitulo`; derruba a tabela e cria a **view
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
- **O que mudou em relação ao plano** (conferência de 30/09/2026 no banco e no código):
  - **Script próprio** `api/database/pagamento_liquidacao.sql` (não a seção 2 do `pagamento.sql`):
    roda sozinho, depois do `pagamento.sql`, e a cópia só acontece enquanto `tblliquidacaotitulo`
    for tabela (rodar de novo não faz nada). Cópia de antes em dev:
    `mgdb-mgdb-1:/tmp/m6_antes_liquidacaotitulo.dump` e `/tmp/m6_antes_movimento_liquidacao.csv`.
  - **Valores do pagamento** = soma das linhas de baixa (sem os estornos): total = |Σ total|,
    juros/multa/desconto = Σ das colunas, principal = total − juros − multa + desconto. Encontro de
    contas misto que daria principal negativo (229 no histórico) fica com principal = total e
    juros/multa/desconto zerados **no pagamento** (as linhas do movimento continuam com eles).
  - **Meio pelo portador antigo**: espécie → dinheiro; banco **e adquirente** → transferência;
    **cartão da empresa → crédito**; Acerto Folha → folha; Barter → permuta; Perda por Prazo →
    perda; Programação Pagamentos e Cred Pis/Cofins → compensação; demais "outros" (Carteira,
    Cobrador Externo, Brad Expresso, Pagfacil) → outros. Total zero (9.259 encontros de contas) →
    compensação sem portador. Os pseudoportadores ficam como origem/destino no histórico (para a
    conferência por portador fechar) e foram inativados.
  - Resultado em dev: 175.766 liquidações copiadas (5.343 estornadas → C), 436.875 movimentos
    reapontados, dinheiro por portador e mês igual (conferência dentro do script). Conferido fora
    dele contra o dump: `debito`/`credito` da view iguais aos da tabela antiga em todas, menos 8
    liquidações de 2015–2016 com débito negativo gravado (a view calcula certo). Os códigos antigos
    vão até 80.000.003 e os pagamentos novos começam em 80.000.196 (a sequência do M4): a view
    usa `coalesce(codliquidacaotituloantigo, codpagamento)` sem colisão.
  - **View `tblliquidacaotitulo`**: pagamentos com movimento de título e sem negócio (fora o vale
    usado na venda), com `debito`/`credito` das linhas de baixa (qualquer tipo < 900, não só 600) e
    as colunas que o Totais de Caixa lê.
  - **Edição como na liquidação** (decisão do Fábio, 01/10/2026, exceção à regra de o pagamento
    não mudar): pessoa, portador, meio, data e observação, levando portador e data às linhas do
    movimento (`PUT v1/pagamento/{id}`). Valores e títulos não mudam (estorna e lança de novo);
    venda, acerto e baixa de boleto pelo banco não se editam no contas. **Estorno pede
    justificativa** (vai para o cancelamento).
  - **Quem estorna o quê**: acerto de RH só pelo acerto (inativar); baixa de boleto pelo banco não
    se estorna no contas; venda, pelo negócio.
  - **Gaveta recusada no contas**: 422 no backend e o select de portador do "Receber ou Pagar"
    esconde gaveta (`v1/select/portador` devolve `gaveta`; `MgSelectPortador` ganhou `sem-gaveta`).
    Meio sugerido pelo tipo do portador e editável (dinheiro, cheque, crédito, débito, boleto,
    depósito, PIX, transferência, outros). Títulos que se anulam viram encontro de contas, sem
    portador (só Admin/Financeiro/Cobrança, porque a filial vem do portador).
  - **Boleto BB e retorno Bradesco** criam um pagamento por linha de baixa (meio boleto, destino o
    portador do boleto; ocorrência 40 do Bradesco = sentido contrário), regravado no
    reprocessamento (`PagamentoTituloService::daBaixa`). O histórico também (seção 5 do script):
    em dev 70.600 baixas (30,6 mil BB + 40 mil Bradesco) e 16 eventos de acerto ganharam
    pagamento. A view do MG Lara **não** mostra boleto nem acerto (nunca foram liquidação).
  - **Acerto de RH**: um pagamento por evento, valor = |saldo do evento| (rubricas + créditos −
    débitos), meio pela forma. **B é Recarga Bee, não banco** (o doc estava errado): compensação,
    sem portador, porque o dinheiro anda depois, no título da recarga. **D dinheiro pede o caixa ou
    cofre** (portador em espécie; saldo positivo sai dele, negativo entra). **F folha** (meio 92).
    Saldo zero = compensação. `codperiodocolaboradoracerto` no pagamento e os movimentos apontando
    para ele. Inativar cancela; reativar cria outro com o mesmo portador. Em pessoas o evento
    mostra o link do pagamento e o modal pede o portador no dinheiro.
  - **Recarga Bee sem portador** (decisão do Fábio, 01/10/2026): o título a pagar da Beevale nasce
    sem portador; quem diz de onde saiu é o pagamento, quando o financeiro paga. Saiu a escolha de
    portador da recarga.
  - Rotas `v1/pagamento` (index, relatorio, show, store, `{id}/estornar`, recibos) e
    `v1/pdv/pagamento` (listagem do negocios, que corrigiu os filtros: usuário, código e valor não
    filtravam). Domínio em `Mg/Pagamento` (`PagamentoTituloService`, `PagamentoTituloAutorizador`,
    controller, request, resources, `PagamentoRelatorioService`), blades em `views/pagamento`.
  - contas: menu "Recebimentos e Pagamentos", `pages/pagamento` (lista com filtros de sentido,
    portador, meio, pessoa e datas; detalhe com principal/juros/multa/desconto/total e os títulos;
    "Receber ou Pagar Títulos"), `pagamentoStore` com o CRUD; o detalhe do título liga o movimento
    ao pagamento.
  - **MGsis**: `MovimentoTitulo.php` declara `codliquidacaotitulo` como propriedade (a NFe de
    Terceiros grava movimento passando por `Titulo::adicionaMovimento`, que atribui a coluna que
    saiu). **Sobe junto.**

## M6.1 — Um wizard, uma listagem, nos dois apps (Fundação; absorve o M7)

**Por quê** (01/10/2026): o M5 desacoplou o wizard do negócio mas o deixou em `negocios/src`, e o M6
seguiu o plano à risca criando no contas um dialog próprio ("portador + meio") sem bandeira,
autorização, parcelas nem maquineta, e uma listagem só de pagamentos com título. Era o plano que
estava errado: duplicou código e escondeu dado conforme a tela. Decidido com o Fábio:

- O caixa não alterna de app: receber notinha e pagar vale do cliente é no **negocios**.
- O financeiro paga e recebe no **contas**, com **o mesmo wizard**.
- **Tudo em `@components`**, inclusive as integrações: `MgCobrancaDialog` (era `ReceberDialog`),
  `Forma*`, `ListaOpcoes`, `ListaFiltravel` e os stores `cobranca`, `pix`, `pagarMe`, `saurus`.
  O app injeta só o que é dele: instância da `api`, identificação do PDV (negocios) ou nenhuma
  (contas), formas permitidas e portadores de dinheiro disponíveis. Nenhuma cópia local.
- **Formas no contas**: todas as do wizard, menos gaveta — transferência/TED/depósito/PIX chave e
  boleto (banco), dinheiro (cofre, troco, Caixa Financeiro), cartão manual e integrado (maquineta
  da filial), PIX QR, cheque, compensação, cartão de crédito da empresa (portador C).
- **Listagem única** `MgPagamentoLista` em `@components`: todos os pagamentos (venda, título,
  transferência, avulso, pendentes e cancelados, cor por estado); colunas data, origem (venda nº com
  link / títulos / de → para / avulso com motivo), pessoa, meio, maquineta, portador, total, estado,
  PDV, usuário; filtros período, filial, PDV, portador, meio, estado, origem, pessoa, maquineta,
  número do documento; detalhe com documento, títulos movimentados (principal/juros/multa/
  desconto/total) e, do M10 em diante, lançamentos; ações estornar/cancelar e recibo. No
  **negocios a listagem fica travada no PDV atual** (sem filtro de filial/PDV); no contas, tudo, com
  padrão filial do usuário e mês.
- **Apagar**: o dialog de pagamento do contas (`pages/pagamento/Nova.vue`, a parte portador + meio),
  a listagem parcial do contas (`pages/pagamento/Index.vue`), a `LiquidacaoListagemPage` do
  negocios e as cópias dos stores em `negocios/src/stores`. **Fica** a seleção de títulos do contas
  (capital, multa, juros, desconto por título), que passa a abrir o wizard.
- **TASK-191** (unificar PagarMe/Saurus/Lio no backend) fica fora: os stores mudam de pasta como
  estão.

**Entrega**

- `@components`: `MgCobrancaDialog.vue` + `cobranca/Forma*.vue`, `stores/cobrancaStore.js`,
  `stores/pixStore.js`, `stores/pagarMeStore.js`, `stores/saurusStore.js` (injeção: `api`, `pdv`
  opcional, `formasPermitidas`, `portadoresDinheiro`, `maquinetas`); `MgPagamentoLista.vue` +
  `MgPagamentoDetalhe.vue` (endpoint `GET v1/pagamento` com todos os filtros; `v1/pagamento/{id}`).
- **negocios**: `IndexPage`/`negocioStore` consomem o wizard de `@components`; tela **Receber
  título / Pagar vale** (o M7, abaixo); menu "Pagamentos" com `MgPagamentoLista` travada no PDV.
- **contas**: Receber ou Pagar Títulos = seleção de títulos → `MgCobrancaDialog` (sentido pelo
  líquido, formas da lista acima, um pagamento por forma); menu Movimento → Pagamentos com
  `MgPagamentoLista`.
- **Backend**: `GET v1/pagamento` passa a listar todos os pagamentos com os filtros; o que o M7
  previa (`PdvPagamentoService`), abaixo.

### Tela do PDV (o que era o M7)

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
- **Frontend negocios** (como ficou, ver "O que mudou"): página `/pagamento/receber` aberta pelo FAB
  da tela Pagamentos, com a mesma `MgBaixaTitulos` do contas → `MgCobrancaDialog` de `@components`
  (sentido conforme o líquido); para
  pagar crédito, escolha do meio (dinheiro / cancelamento no cartão escolhendo o pagamento original /
  devolução de PIX); store `pagamento.js`; listagem de pagamentos do PDV mostra meio, maquineta e PDV.
- **Valida**: notinha em dinheiro (troco), PIX QR, cheque, cartão nas duas operadoras, cartão manual;
  entrega paga na volta; dividido (dois pagamentos, um recibo); crédito de devolução pago em dinheiro
  (só Gerente), cancelamento parcial no cartão registrado; estorno; contas mostra tudo.

**Valida (tudo junto)**: no contas, pagar título em cartão com bandeira, autorização, parcelas e
maquineta, e em transferência, dinheiro do cofre, cheque e compensação; gaveta não aparece; a
venda de agora há pouco aparece na listagem do contas e na do negocios (travada no PDV); no PDV,
notinha em dinheiro (troco), PIX QR, cheque, cartão nas duas operadoras e manual, entrega paga na
volta, dividido (dois pagamentos, um recibo), vale de devolução pago em dinheiro (só Gerente) e
cancelamento parcial no cartão registrado; estorno; `grep` confirma que não sobrou cópia dos
stores nem do wizard fora de `@components`.

**O que mudou em relação ao plano** (levantamento de 01/10/2026, decidido com o Fábio):

- **Cobrança integrada em título** (PIX QR, Stone, SafraPay): na confirmação o servidor cria o
  pagamento efetivado **sem título** (PIX, PagarMe e Saurus deixaram de exigir negócio); a tela
  manda o `codpagamento` junto ao finalizar, e finaliza sozinha quando as formas fecham o
  líquido. Tela fechada no meio = pagamento avulso sem título na listagem. Sem DDL.
- **Saída no contas** (pagar fornecedor, pagar vale): banco, dinheiro de cofre/troco/Caixa
  Financeiro, cartão da empresa, compensação **e cheque emitido pela empresa** (CMC7 + bom para
  + conta; não entra no controle de cheques recebidos).
- **Listagem do negocios** por `v1/pdv/pagamento` (o dispositivo autoriza e o servidor força o
  PDV), não por `v1/pagamento?codpdv=`. Mesmo serviço e filtros (`PagamentoListaService`).
- **Criar cobrança sem PDV**: rotas `v1/cobranca/{pix, pagar-me, saurus}` e
  `v1/cobranca/maquineta/{codfilial}` para o contas; a criação saiu do `PdvController` para
  `Mg/Pagamento/CobrancaService` (o PDV chama o mesmo).
- **Também foram para `@components`**: os dialogs das integradas (`cobranca/PixCobDialog`,
  `PagarMePedidoDialog`, `SaurusPedidoDialog`), `useConsultaAutomatica`, `LogoPagamento`,
  `pagamento.js`, `parcelamento.js`, `cmc7.js`, `cartoes-manuais.json` e os logos
  (`assets/pagamento`, servidos pelo bundle de cada app). O `@components` não tem `node_modules`:
  o wizard deixou de usar `moment` e `mitt` (avisos por `cobranca/eventos.js`); o `qrcode` tem
  alias nos dois `quasar.config` (como o `pinia`) e entrou no `package.json` do contas, que
  também passou a carregar os ícones MDI.
- Nomes: o store de PIX tem id **`pixCob`** (o contas já tem um `pix`, de Pix Recebidos); arquivos
  `components/stores/{cobrancaStore, pixStore, pagarMeStore, saurusStore}.js`.
- **Um fluxo de baixa só**: `components/stores/baixaTitulosStore.js` (títulos escolhidos →
  formas lançadas → finalizar) serve o Receber título do PDV e o Receber ou Pagar Títulos do
  contas. O wizard devolve o resultado pelos tratadores do documento (`aoPagamento`,
  `aoCobranca`) ou, sem eles, pelos eventos — um wizard por app.
- **Vários pagamentos para os mesmos títulos**: `PagamentoTituloService::baixar(dados, ?pdv)`
  distribui as linhas por vencimento; título que cai entre duas formas é dividido com juros,
  multa e desconto proporcionais; títulos do sentido contrário entram no 1º pagamento.
  Pagamento parcial = editar o capital do título no seletor (como no contas). Fora da venda o cartão não
  tem juros de parcelamento nem a forma dinheiro tem desconto (título não tem onde guardar).
- Juros e multa do título em atraso: regra única em `@components/cobranca/juros.js` (contas e
  PDV); a seleção de títulos do contas continua com os parâmetros editáveis.
- **Listagem**: `MgPagamentoLista` + `MgPagamentoFiltros` + `MgPagamentoDetalhe` + store
  `pagamentoLista` em `@components`; filtros período, origem (venda/títulos/transferência/
  avulso), estado, documento (venda ou título), pessoa, filial, PDV, meio, portador, maquineta,
  usuário e código. Saíram grupo econômico, grupo de cliente e sentido. Novo `v1/select/pdv` +
  `MgSelectPdv` para o filtro de PDV. O relatório PDF usa os mesmos filtros (limite de 5.000
  pagamentos). No contas o detalhe é a página de sempre (edição e recibos PDF no slot); no PDV é
  um dialog (estorno e recibo térmico).
- **PDV**: tela `/pagamento/receber` (layout próprio, volta para Pagamentos) = `MgBaixaTitulos`
  com `MgSeletorTitulosAbertos` (era o `SeletorTitulosAbertos` do contas, foi para
  `@components`), sem atalho de teclado e sem lista própria (decisão do Fábio, 01/10/2026: a tela
  do PDV fica só com a venda; Receber Título e Vale / Adiantamento entram pelos FABs da tela
  Pagamentos). Busca só com pessoa ou grupo econômico (422 sem eles), filial do PDV como padrão;
  multa, juros e desconto editáveis por qualquer um, como no contas. `stores/pagamento.js`
  guarda as formas (`FORMAS_RECEBER`, `FORMAS_ADIANTAMENTO`), o dialog do M8 e o recibo; rotas
  `v1/pdv/pagamento` (index, `{id}`, `titulos`, `originais`, store, `{id}/estornar`,
  `recibo/{impressora}`) e o PDF assinado `pdv/pagamento/recibo/{codpagamentos}` (um recibo
  para os pagamentos do mesmo recebimento, blade `pagamento/recibo-termica`). Pagar vale: só
  Gerente da filial ou Administrador (403); devolução no cartão/PIX escolhe o pagamento original
  dos últimos 12 meses e não passa do que resta dele; cartão sem portador de adquirente (Brasil
  Card, Le Card, Cielo) fica sem origem/destino.
- **Maquineta de qualquer filial** (decisão do Fábio, 01/10/2026: o sistema não bloqueia o
  registro da realidade — o entregador sai com a maquineta de outra loja): o cartão do wizard,
  manual e integrado, no PDV e no contas, lista as maquinetas ativas de todas as filiais
  (`MaquinetaService::paraPdv` com `filial` e `outrafilial`); as de outra filial vêm por último,
  no grupo "Outras filiais", com a filial na legenda e cor de aviso. Nada bloqueia.
- **Cheque só com o nome do emitente** (sem CPF/CNPJ): `ChequeService::sincronizarEmitentes` não
  cria linha de emitente sem CPF/CNPJ (a coluna é obrigatória; o nome fica no cheque). Quebrava o
  fechamento da venda em cheque sem CPF/CNPJ.
- Cheque recebido em título vai para o controle de cheques (como na venda); estornar cancela o
  cheque ainda a repassar. Troco do dinheiro gravado no pagamento.

## M8 — Vale colaborador e adiantamentos, no PDV e no contas (Receber no balcão)

- **Backend** `PdvTituloService::lancar(Pdv, dados)`: cria título com `movimentaportador` (2 Vale
  Colaborador e 120 Adto Fornecedor: saída em dinheiro, origem = gaveta; 220 Adto Cliente: entrada
  pelas formas do Receber título do M6.1), um título por forma, implantação com `total` e pagamento
  ligado (portador pela mesma regra de `PagamentoTituloService`; cobrança integrada amarrada pelo
  `codpagamento` que a confirmação criou); `estornar`
  via `TituloService::estornar` (cancela o pagamento). Permissão: Caixa da filial/Gerente/Admin.
- **Frontend**: `LancarTituloDialog.vue` (tipo, pessoa, valor, observação → `MgCobrancaDialog` de
  `@components` no sentido certo, com documento próprio e tratadores `aoPagamento`/`aoCobranca`,
  como o `baixaTitulosStore`); comprovante térmico com assinatura (como o
  `pagamento/recibo-termica`).
- **Valida**: vale em dinheiro; adiantamento de cliente em dinheiro, PIX QR e cartão; adiantamento a
  fornecedor; títulos no contas com portador certo; estorno.
- **O que mudou em relação ao plano** (conferência de 01/10/2026 no código, decidido com o Fábio):
  - **Conta contábil** (obrigatória no título): padrão por tipo, editável no dialog — 2 Vale
    Colaborador → 42 Despesa Colaboradores; 120 Adto Fornecedor → 1 Compra Mercadoria; 220 Adto
    Cliente e 230 Crédito Cliente → 2 Venda Mercadoria. **Vencimento** em campo, padrão hoje + 30 dias (não antes de
    hoje). Número do título pela regra do `TituloService` (data + sufixo por pessoa).
  - **Sem atalho de teclado**: o botão "Vale / Adiantamento" fica na tela **Pagamentos** (fab-mini ao
    lado do FAB do Receber Título), não na do PDV (ajuste do M6.1, 01/10/2026).
  - **Estorno pela listagem de Pagamentos**, sem rota nova: `PagamentoTituloService::estornar`,
    quando a linha do pagamento é a implantação, chama `TituloService::estornar`, que só desfaz
    título não movimentado (422) e agora leva `total` e `codpagamento` ao estorno (900) e cancela o
    pagamento com a justificativa. Vale também para o estorno do título no contas (o pagamento é
    cancelado com "Estorno do título …"). Regra de quem estorna no PDV é a do M6.1 (Caixa os
    próprios em 2 h; Gerente a filial). As duas exceções genéricas do `TituloService::estornar`
    viraram `abort(422)`.
  - **Como ficou no código** (M8.1, 01/10/2026: o dialog era local do negocios e só servia ao PDV;
    "ao invés de uma tela genérica, reutilizável, ficou capada" — Fábio). Uma peça só, nos dois apps,
    como o `MgBaixaTitulos`:
    - Backend: `TituloService::criar(dados, ?Pagamento)` e `implantar(titulo, ?Pagamento)`
      (implantação com `total` = valor, `codpagamento` e o portador do pagamento);
      `Mg/Titulo/TituloAdiantamentoService::lancar(dados, ?Pdv)` + `TituloAdiantamentoStoreRequest`.
      Rotas `POST v1/pdv/titulo` (`PdvTituloController`, o dispositivo autoriza) e `POST
      v1/titulo/adiantamento` (`TituloAdiantamentoController`; quem baixa título no contas:
      Admin/Financeiro/Cobrança em tudo, Gerente/Caixa na filial deles,
      `PagamentoTituloAutorizador::motivoBloqueioAdiantamento`). Reusa
      `PagamentoTituloService::pagamentoDaForma` (portador pela regra da baixa: no PDV dinheiro na
      gaveta; no contas banco, cofre/troco/Caixa Financeiro, cartão da empresa, cheque emitido;
      cartão na adquirente; cobrança integrada amarrada pelo `codpagamento`) e `gerarCheque`, que
      ficaram públicos. Compensação e devolução recusadas (o título nasce com dinheiro).
    - **Tipos**: os ativos com `movimentaportador`, do cadastro (`v1/select/tipo-titulo`
      devolve a flag); saídas primeiro, Vale Colaborador como padrão. Depois da limpeza dos tipos
      (abaixo) são só **120 Vale Colaborador, 121 Adiantamento Fornecedor e 211 Adiantamento
      Cliente**: o Crédito Cliente (crédito de devolução) perde a flag.
    - **PDV**: data = agora, filial do PDV, Admin ou Caixa/Gerente da filial; saída só em dinheiro
      da gaveta (decisão 24). **Contas**: data e filial escolhidas no dialog (padrão hoje e a
      filial do usuário); vencimento não antes da data.
    - Frontend: `@components/MgAdiantamentoDialog.vue` (tipo, data e filial no contas, pessoa,
      valor, vencimento, conta, observação → wizard no sentido do tipo; formas lançadas e "Lançar
      o que foi pago"; o título que ainda não existe entra no `baixaTitulosStore` como uma linha
      só). Cada app informa formas, contexto e para onde manda; o wizard e os dialogs
      PIX/Stone/SafraPay ficam na página. negocios: fab-mini em Pagamentos (`PagamentoPage`,
      `FORMAS_ADIANTAMENTO`, recibo na térmica e listagem recarregada); contas: fab-mini em
      Pagamentos (`pages/pagamento/Index.vue`, formas do financeiro sem compensação; abre o
      detalhe do pagamento). O contexto do wizard no contas saiu do `Nova.vue` para
      `contas/src/utils/cobranca.js` (`contextoCobranca`, `FORMAS_TITULOS`,
      `FORMAS_ADIANTAMENTO`). Apagados `LancarTituloDialog.vue` e o estado do M8 em
      `negocios/src/stores/pagamento.js`.
    - **Wizard com uma forma só** (`cobrancaStore.abrir`): vai direto a ela, sem lista de uma
      opção, e "Voltar" fecha (vale do PDV: direto no dinheiro).
  - **Comprovante**: o mesmo `pagamento/recibo-termica` (um recibo para as formas do lançamento):
    cabeçalho com o tipo ("VALE COLABORADOR"), "referente a Vale Colaborador" + observação e a
    linha de assinatura (da pessoa na saída; da empresa na entrada). Impresso na impressora do PDV
    pela rota do M6.1. No contas, o detalhe do pagamento tem os recibos de sempre.
  - Os pagamentos aparecem na listagem única como origem "Títulos".
- **Valida (M8.1)**: no PDV (Pagamentos → Vale / Adiantamento) vale em dinheiro (wizard direto no
  dinheiro) e adiantamento de cliente em dinheiro, PIX QR e cartão; no contas (Pagamentos → Vale /
  Adiantamento) vale por transferência, adiantamento a fornecedor no cartão da empresa,
  adiantamento de cliente por depósito e dinheiro do cofre, data de ontem; gaveta não aparece no
  contas; estorno pelas duas listagens.

### Limpeza dos tipos de título (M8.1, decidida e executada em dev em 02/10/2026)

**Por quê**: o dialog Vale / Adiantamento mostrou "Adto Cliente" e "Crédito Cliente" lado a lado,
e o cadastro tinha 26 tipos ativos: tipos que só diferem pela forma de pagar (boleto, débito
automático, CTRC), sem uso, que o agrupamento usava sem precisar, e um Vale Compras que misturava
vale comprado com crédito de devolução. Segunda passada depois da TASK-186 (que inativou 26 em
28/09). Decidido item a item com o Fábio:

- **Ficam 14 tipos, renumerados com 3 dígitos: 1xx a receber, 2xx a pagar.** As FKs de
  `tbltitulo` e `tblnaturezaoperacao` para `tbltipotitulo` já são `ON UPDATE CASCADE`.
- **Cheque Devolvido fica** (cheque de cliente que deu calote).
- **PIX/Depósito e Entrega** (a receber e a pagar) **ficam**: a venda escolhe o tipo pela
  condição da parcela.
- **Vale Compras, Adiantamento Cliente e Crédito Cliente ficam separados**: vale compras = vale
  comprado (ou dado de brinde); adiantamento = o cliente deixou dinheiro; crédito = devolução. Hoje
  a devolução gerava Vale Compras: os 19.041 de devolução (conta "Devolução de Vendas") passam
  para Crédito Cliente, as naturezas de devolução de venda passam a gerar Crédito Cliente e o PDV
  aceita Crédito Cliente como vale (o vale por escola/turma continua só Vale Compras).
  Adiantamento Cliente (e qualquer título com saldo de crédito) paga compra no PDV: ver abaixo.
- **Somem**: Agrupamento Débito/Crédito (o título do agrupamento já se identifica pelo
  `codtituloagrupamento`, pelo número `A…` e pela conta 7; até 2022 o agrupamento gerava tipos
  comuns), Débito Cliente (é Duplicata a Receber, só muda a conta contábil), os de forma de pagar
  (Boleto, CTRC, Programação, Débito Automático), Compra, Compra/Venda Imóvel, Entrada
  Bonificação, Outras Saídas, Remessa Armazenagem e Repasse Parceiro (sem uso; o M13 usa Duplicata
  a Pagar). Naturezas de operação que apontavam para eles → 100 (saída) / 200 (entrada).

| Novo | Antigo | Tipo | Nat. | Movimenta portador |
|---|---|---|---|---|
| 100 | 200 (+921, 240, 950, 945, 946) | Duplicata a Receber | R | não |
| 101 | 201 | PIX/Depósito Receber | R | não |
| 102 | 310 | Entrega Receber | R | não |
| 111 | 1 | Cheque Devolvido | R | não |
| 120 | 2 | Vale Colaborador | R | sim |
| 121 | 120 | Adiantamento Fornecedor (era Adto Fornecedor) | R | sim |
| 122 | 4 (+140) | Débito Fornecedor (era Devolução de Compra) | R | não |
| 200 | 927 (+911, 928, 937, 931, 100, 935, 951, 7, 953) | Duplicata a Pagar | P | não |
| 201 | 930 | PIX/Depósito Pagar | P | não |
| 202 | 320 | Entrega Pagar | P | não |
| 210 | 3 (sem os de devolução) | Vale Compras | P | não |
| 211 | 220 | Adiantamento Cliente (era Adto Cliente) | P | sim |
| 212 | 230 (+ Vale Compras de devolução) | Crédito Cliente | P | não |
| 220 | 952 | Rubrica RH | P | não |

Mais os 5 inativos de histórico da parte 2 (130, 131, 132, 133, 230), abaixo.

- **Como**: `api/database/tipo_titulo_limpeza.sql` (idempotente; conferência de saldo de cada
  título antes e depois; transfere, apaga os 15 tipos que somem, renomeia, desliga a flag do
  Crédito Cliente e renumera em duas passadas, porque códigos novos e antigos se cruzam). Último
  script do go-live. Código: constantes de `TituloService` (vale 210, RH 220, crédito cliente
  212), `TipoTituloService` (PIX/entrega), `TituloAgrupamentoService` (gera 100/200 pelo sinal),
  `BeeRecargaService`, `AcertoService`, `NfeTerceiroIcmsStService` e o PDV aceitando 210 e 212
  como vale; MGsis `NfeTerceiroController.php` (928 → 200) sobe junto. `titulo_valor.sql` deixa de
  criar o 953.
- **Resultado em dev** (1 min 41 s; rodar de novo só confere): 96.462 títulos transferidos, 13
  naturezas reapontadas, 19.041 Vale Compras de devolução → Crédito Cliente, 2 naturezas de
  devolução de venda → Crédito Cliente, 15 tipos apagados; saldo e valor de cada título iguais
  (conferência dentro do script). Títulos por tipo: 100 527.000, 101 6.061, 102 4.293, 111 145,
  120 7.261, 121 5.026, 122 1.321, 200 111.602, 201 408, 202 21, 210 4.162, 211 145, 212 19.585,
  220 364. O nome do tipo passou de 20 para 50 caracteres ("Adiantamento Fornecedor" não cabia).
  O 946 Remessa Armazenagem estava cadastrado como "a pagar" com flag de receber; sem título, foi
  para 100 com as suas três naturezas (todas de saída). Cópia de antes em dev:
  `mgdb-mgdb-1:/tmp/tl_antes_{tipotitulo.dump, titulo_tipo.csv, natureza_tipo.csv}`.
- **Parte 2 — os 23 inativos da TASK-186** (decididos um a um com o Fábio, 02/10/2026: "nada
  inativo com lixo"). Os títulos vão para um tipo ativo e o tipo é apagado; alguns ganham o nome
  antigo na frente da observação (`PROVISAO`, `CONSIGNACAO`, `DEVOLUCAO CONSIGNACAO`, `RETORNO
  CONSERTO`, `REMESSA CONSERTO`, `GARANTIA`, `OUTRAS ENTRADAS`, `CREDITO FORNECEDOR`,
  `EMPRESTIMO`, `COMODATO`, `DESCONTO CONVENIO`, `ALUGUEL` + quebra de linha) e as trocas ganham
  o portador da troca:

  | Antigo | Vai para | Títulos | Observação / portador |
  |---|---|---|---|
  | 936 Provisão a Pagar | 200 | 230 (30 em aberto) | `PROVISAO` |
  | 933 Pacote Soja, 934 Pacote Milho, 942 Pacote Milheto, 944 Troca Prod. Agrícola | 200 | 168, 70, 7, 84 | portador **Barter** (202007, reativado) |
  | 932 Permuta | 200 | 110 | portador **Permuta** (novo, tipo Outros, ativo) |
  | 929 DDA a Pagar | 200 | 50 | — |
  | 943 Entrada Consignação | 200 | 39 | `CONSIGNACAO` |
  | 941 Retorno Conserto | 200 | 8 | `RETORNO CONSERTO` |
  | 5 Outras Entradas (8 naturezas) | 200 | 7 | `OUTRAS ENTRADAS` |
  | 130 Crédito Fornecedor | 200 | 7 | `CREDITO FORNECEDOR` |
  | 949 Empréstimo Recebido | 200 | 7 | `EMPRESTIMO` |
  | 6 Entrada de Comodato | 200 | 1 | `COMODATO` |
  | 947 Devolução Consignação | 122 | 10 | `DEVOLUCAO CONSIGNACAO` |
  | 8 Remessa Conserto | 122 | 31 (2 em aberto) | `REMESSA CONSERTO` |
  | 948 Garantia | 122 | 17 | `GARANTIA` |
  | 940 Desconto Convênio | 100 | 16 | `DESCONTO CONVENIO` |
  | 939 Contrato Aluguel Rec | 100 | 13 | `ALUGUEL` |

  **Ficam inativos, só de histórico** (notas que nunca deviam ter gerado título), renumerados:
  **130 Transferência Saída** (era 922, 72.916), **131 Uso e Consumo** (926, 12.819), **132
  Perda** (925, 2.651), **133 Doação, Brinde** (924, 992) e **230 Transferência Entrada** (923,
  21). As naturezas desses continuam apontando para eles até a **TASK-193** (criada a pedido do
  Fábio, High): tipo de título só obrigatório quando a natureza gera financeiro.
  Em dev: 875 títulos e 19 naturezas, 8 s; cópia de antes em `mgdb-mgdb-1:/tmp/tl2_antes_*`.
- **PDV paga com qualquer título de crédito** (decisão do Fábio, 02/10/2026): "todo título com
  saldo de crédito pode ser usado como pagamento numa compra no PDV" — vale compras, crédito e
  adiantamento do cliente, duplicata a pagar (fornecedor que também é cliente)... `buscarVale`
  aceita qualquer título com saldo negativo; a NF-e sai com tPag 12 (Vale Presente) para todos. O
  vale impresso pela própria venda continua só para Vale Compras e Crédito Cliente.
- SQLs avulsos do `MGdb/SQLs` com códigos antigos atualizados para os novos (5 arquivos; repositório
  MGdb, na árvore).

## M9 — Conferências e fechamento do caixa (Receber no balcão; absorve a gaveta do M10)

> **Mudou no M13 (03/10/2026, com o Fábio):** a sessão da gaveta deixou de ter conferência às
> cegas separada. Fechar (no PDV pelo caixa ou no contas pelo gerente, na mesma tela) é a
> conferência; o borderô traz tudo. Lote, cheque, vale, duplicata e venda continuam como abaixo.

**Por quê** (redesenho com o Fábio, 02/10/2026): o M9 era uma tela de conferência de maquineta por
dia. Discutindo a dinâmica, ficou claro que o fechamento é em dois tempos e que o gerente precisa
**consertar** o que a loja fez errado, não só comparar números. Levantamento em dev (2025–26): 33
pedidos SafraPay com o pagamento gravado duas vezes (mesmo pedido, mesma autorização); 333 de 568
mil vendas fechadas com Σ pagamentos + parcelas ≠ total (207 a mais, R$ 8,5 mil, quase todas com
cartão; 84 a menos, R$ 500); 1.137 de 2.830 devoluções eram de venda em cartão e só 1 tem o
cancelamento do cartão registrado. Na mesma conversa a parte de gaveta do M10 entrou aqui ("tudo
no M9").

**Decisões (M9.x, não reabrir)**

1. **Toda movimentação nasce "a conferir" e pertence a uma conferência independente**: dinheiro →
   **sessão da gaveta**; cartão → **lote da maquineta**; cheque recebido e vale recebido → o
   próprio pagamento, item a item; duplicata a prazo → a venda, **escaneando a confissão**
   (`tblnegocio.confissao`, que já existe); PIX manual/depósito (parcela X) → financeiro, baixando
   o título contra o banco; PIX QR e boleto nascem conferidos (o banco confirmou). Cada conferência
   fecha sozinha, no seu tempo: a duplicata pode estar com o entregador, a maquineta pode estar com
   outro caixa.
2. **Caixa abre e fecha o dinheiro no PDV** (contagem de moedas e cédulas; fechar imprime o
   **borderô do caixa**) e sobe ao escritório com tudo. Sem sessão aberta o Dinheiro fica bloqueado
   no PDV (TASK-39 AC #2). Cartão, PIX, cheque, vale e prazo aparecem no borderô do caixa só como
   informação.
3. **O gerente confere tudo no contas, pensado para o celular**: tela **Fechamentos** = lista das
   conferências pendentes da filial; ele escolhe uma, **digita às cegas** (só depois vê sistema e
   diferença) e confirma. O financeiro vê a mesma tela (todas as filiais, as fechadas também) e
   cuida do PIX manual. Permissão: Gerente da filial, Financeiro, Admin
   (`MaquinetaService::podeGerenciar`).
4. **Lote da maquineta = o borderô da maquineta.** Cada maquineta tem sempre um lote aberto;
   pagamento em cartão com maquineta cai nele ao ser gravado. Fica no lote tudo que entrou até o
   gerente fechar ("tudo até fechar": venda feita depois de emitido o borderô ele **move** para o
   lote seguinte). Fechar pede crédito e débito do borderô e a **foto do borderô**. Maquineta usada
   por mais de um caixa fecha uma vez só, quando o último vier. Cancelamento de verdade (venda
   cancelada, pagamento contrário) cai no lote aberto **no momento do cancelamento** — como no
   extrato da maquineta. Lote usado por mais de um caixa mostra o subtotal de cada PDV.
5. **Correção dos lançamentos pelo gerente** (Gerente da filial, Financeiro, Admin), inclusive
   cartão integrado — a API às vezes erra o valor ou duplica —, com **justificativa obrigatória e o
   dado anterior guardado** (`tblpagamentocorrecao`): corrigir dados (meio crédito/débito, maquineta,
   bandeira, autorização, parcelas), mover de lote, corrigir valor (inclusive dinheiro: o caixa
   descobre pelas câmeras que cobrou errado ou caiu num golpe), **excluir registro indevido**
   (cancela e tira do lote: não é cancelamento na maquineta) e incluir o que faltou (wizard). Total
   da venda, itens, estoque e nota **não mudam** (NFC-e autorizada não se corrige).
6. **Venda desbalanceada vira pendência** da filial (Σ pagamentos efetivados + Σ parcelas + Σ
   acertos ≠ total). O destino da diferença é decidido depois, pelo gerente ou pelo financeiro, e
   gravado como **acerto da venda** (`tblnegocioacerto`): **a menos** → perdoar, vale do colaborador
   (título 120 a receber do colaborador) ou duplicata do cliente (100); **a mais** → perdoar ou
   crédito do cliente (212, usável no PDV como vale; o financeiro pode devolver por PIX baixando
   contra o banco).
7. **Devolução de venda paga no cartão**: a devolução continua gerando o crédito do cliente (212); o
   gerente registra o cancelamento do cartão **pelo lote** (pagamento contrário apontando o
   original, origem = adquirente, mesma maquineta) e o crédito é baixado por esse pagamento.
8. **Conferência confirmada só muda reabrindo** — qualquer correção dentro dela. Reabrir: quem
   confere; fica a trilha (quem/quando).
9. **Histórico fora**: as conferências começam no go-live (`CONFERENCIA_INICIO` no `.env` da API).
   Pagamento e venda anteriores não entram em lote nem viram pendência.
10. **Anexos do negócio numa rota só, fora do prefixo `pdv`** (`v1/negocio/...`; com `pdv` autoriza
    pelo dispositivo, sem ele pelo usuário), serviço movido para `Mg/Negocio`, sem duplicar rota nem
    código; Slim e leitor de confissão em `@components`, usados pelo negocios e pelo contas.
11. A decisão 26 muda: o cartão do dia é conferido **por lote**, no fechamento, pelo gerente; o
    repasse adquirente × banco continua no M14 (o lote é o gancho: "caiu no banco" vira coluna dele).

**Modelo de dados** (`api/database/conferencia.sql`)

- `tblmaquinetalote`: `codmaquinetalote PK, codmaquineta NN, abertura NN, fechamento (nulo =
  aberto), creditoinformado, debitoinformado, creditosistema, debitosistema (gravados ao fechar),
  observacoes, codusuariofechamento, audit`; único aberto por maquineta. Foto do borderô no disco
  de anexos (`maquineta-lote/{cod}/`).
- `tblpagamento`: `codmaquinetalote` (cartão), `codmaquinetalotecancelamento` (lote aberto quando o
  cancelamento aconteceu), `indevido bool NN default false` (registro indevido: cancelado e fora de
  lote), `codportadorperiodo` (sessão da gaveta do dinheiro), `conferencia`/`codusuarioconferencia`
  (cheque e vale recebido, item a item).
- `tblpagamentocorrecao`: `codpagamentocorrecao PK, codpagamento NN, antes jsonb, depois jsonb,
  justificativa NN, audit` (uma linha por correção).
- `tblnegocioacerto`: `codnegocioacerto PK, codnegocio NN, valor (com sinal: positivo = faltou
  pagar), destino char(1) P perdão / C colaborador / D duplicata do cliente / R crédito do cliente,
  codpessoa, codtitulo, justificativa, inativo, audit`.
- `tblportadorperiodo` (do modelo do M10) + `conferencia`, `codusuarioconferencia`,
  `valorconferido` (contado pelo gerente, às cegas). Sessão de gaveta: abre e fecha pelo caixa
  com contagem (`moedas*`, `cedulas*`); `saldofinal` = contado pelo caixa.
- O razão (`tblportadormovimento`) **fica no M10**: a sessão soma o dinheiro pelos pagamentos com
  `codportadorperiodo` (o pagamento de dinheiro só toca um portador). Transferências (M11) e
  períodos de banco/cofre (M12) trazem o razão completo.

**Pontos decididos sem o Fábio, a validar** (02/10/2026, ele almoçando: "trabalha sozinho")

- Perdão não gera título nem pagamento: fica só o acerto (quem, quando, justificativa).
- Crédito do cliente na diferença a mais = 212 Crédito Cliente (não 211 Adiantamento).
- Cheque e vale recebido: conferência marcada no pagamento.
- Itens do caixa (chips, ingressos) continuam no M13.
- Entrada do Caixa no negocios: a definir com o `MainLayout.vue` (estava alterado na árvore por
  outra conversa em 02/10).
- O protótipo `negocios /conferencia` (`ConferenciaPage`, `PdvService::conferencia`) sai quando a
  tela Fechamentos cobrir.

**Como ficou no código** (02/10/2026, na árvore, sem commit):

- **DDL** `api/database/conferencia.sql` (rodado 2x em dev). O lote "corrente" é o mais novo da
  maquineta, se aberto: reabrir um lote antigo deixa dois abertos e o antigo só recebe lançamento
  movido (por isso o índice de aberto não é único).
- **Vínculo automático**: `Pagamento::booted` (`creating`/`updating` — o `saving` do `MgModel`
  devolve `true` e corta os outros ouvintes) chama `Mg/Conferencia/ConferenciaService::vincular`:
  cartão com maquineta → lote corrente (`MaquinetaLoteService::vincular`; trocar a maquineta leva ao
  lote dela; cancelamento cai no lote corrente do momento; registro indevido e pendente cancelado
  sem efetivação ficam fora); dinheiro com portador de gaveta → sessão aberta
  (`CaixaService::vincular`; sem sessão = 422 "Caixa … fechado"; cancelar dinheiro de sessão que o
  caixa já fechou = 422 "reabra"). Vale para venda, baixa de título, vale/adiantamento e qualquer
  outro caminho que grave pagamento.
- **Venda**: `PdvNegocioService::fechar` manda o dinheiro para a gaveta do PDV
  (`CaixaService::gavetaAberta`: PDV sem gaveta ou caixa fechado = 422). O wizard desabilita o
  Dinheiro com o motivo (`contexto.bloqueioDinheiro`, consultado em `v1/pdv/caixa` ao abrir o
  wizard; offline não bloqueia e o servidor recusa no fechar).
- **Backend** `Mg/Conferencia`: `ConferenciaService::pendencias` (sessões, lotes com movimento —
  os da filial e os compartilhados —, cheque e vale recebidos no PDV, venda a prazo F/P/B sem
  confissão, vendas desbalanceadas, PIX/depósito a receber só para o financeiro; sem valores do
  sistema), `PagamentoCorrecaoService` (`corrigir`: meio entre dinheiro/crédito/débito, valor,
  maquineta, lote, bandeira, autorização, parcelas; `indevido`; `incluir`; valor e meio só em
  pagamento sem título; dinheiro vai para a sessão do momento do pagamento, se não conferida),
  `VendaConferenciaService` (diferença = total − Σ pagamentos efetivados com sinal − Σ parcelas −
  Σ acertos; acerto P/C/D/R com título 120/100/212 pelo `TituloService::criar`, conta 42 ou 2,
  vencimento +30 dias ou +1 ano no crédito; desfazer estorna o título), `ConferenciaAutorizador`
  (Financeiro/Admin em tudo, Gerente na filial; o `MaquinetaService::podeGerenciar` passou a usar),
  resources (`ConferenciaPagamentoResource` estende o da listagem única), `ConferenciaController`
  (`v1/conferencia`, `v1/maquineta/{id}/lote`). `Mg/Caixa`: `CaixaService` (abrir, fechar,
  conferir, desconferir, reabrir — só a última sessão —, dinheiro, informativo), `CaixaController`
  (`v1/pdv/caixa`: status, abrir, fechar, borderô na impressora; PDF assinado
  `v1/pdv/caixa/{id}/bordero`), `CaixaBorderoService` + `views/caixa/bordero-termica` (bobina
  80 mm). `Mg/Maquineta/MaquinetaLoteService` (corrente, sistema por PDV, fechar às cegas,
  reabrir, foto do borderô no disco `negocio-anexo`, pasta `maquineta-lote/{cod}`).
  `TituloService::TIPO_VALE_COLABORADOR` (120) novo.
- **Borderô do caixa sem valores do sistema**: traz a contagem do caixa e só a **quantidade** de
  cheques, vales, duplicatas e cartões por maquineta — senão o gerente veria o sistema antes de
  digitar (as duas conferências são às cegas). Decidido sem o Fábio, a validar.
- **Anexos** (decisão 10): `Mg/Pdv/PdvAnexo*` → `Mg/Negocio/NegocioAnexo{Service,Controller}`
  (movidos; `jpeg()` extraído para servir também à foto do borderô); as 8 rotas saíram do grupo
  `pdv` para `v1/negocio/...`. `@components`: `MgSlim.vue` (recorte genérico, emite a imagem),
  `anexo/slim/` (só `slim.module.js` e `slim.min.css` vieram; o resto de
  `negocios/src/utils/pqina` ficou, sem uso), `MgConfissaoScanner.vue`, `stores/confissaoStore.js`.
  negocios: `ConfissaoPage` usa o scanner; `ListagemAnexos`, `MgAnexoImagem`,
  `ConfissaoFaltandoPage`, `sincronizacao.js` nas rotas novas; `stores/confissao.js` e os dois
  `MgSlim*` locais apagados.
- **contas**: menu Movimento → **Fechamentos** (`pages/fechamento/Index.vue`: pendências por
  grupo, filial no topo; cheque e vale com "conferido" na linha; duplicata abre o
  `MgConfissaoScanner`; PIX abre o título), `Lote.vue` (crédito e débito do borderô às cegas,
  foto, lançamentos escondidos até digitar, corrigir/indevido, reabrir, borderô × sistema e por
  caixa depois de conferido), `Sessao.vue` (dinheiro contado às cegas; depois sistema × caixa ×
  gerente; reabrir conferência ou caixa), `Venda.vue` (pagamentos, parcelas, acertos, incluir
  pagamento, destino da diferença); Maquinetas ganhou o botão **Lotes** (`maquineta/:id/lotes`).
  Store `stores/conferenciaStore.js`; componentes `components/conferencia/{ListaLancamentos,
  CorrecaoPagamentoDialog}`. `@components/cobranca/pagamento.js` exporta `BANDEIRAS`.
- **negocios**: `/caixa` (`CaixaLayout`, `CaixaPage`, `stores/caixa.js`): abrir com contagem,
  fechar com contagem (imprime o borderô na impressora do PDV), reimprimir/ver o último borderô;
  item "Caixa" no menu (`MainLayout.vue`, que estava alterado na árvore por outra mão em 02/10).
- **Atenção no go-live**: com o M9, **PDV sem gaveta não recebe dinheiro** (em dev só o PDV 508
  tem gaveta). Cadastrar as gavetas e vincular os PDVs de caixa (já era pendência do M2) é
  pré-requisito; `CONFERENCIA_INICIO` no `.env` da API = dia do go-live.

**Valida (M9)**: (1) negocios → Caixa no PDV 508: abrir com 50 + 200; venda em dinheiro; venda no
cartão manual em três maquinetas (uma delas também usada por outro PDV), uma lançada na maquineta
errada; cancelamento parcial no cartão (pagar vale → devolução no cartão) e uma venda cancelada;
cheque, vale e venda a prazo; fechar o caixa contando → borderô. (2) Wizard com o caixa fechado:
Dinheiro desabilitado com o motivo. (3) contas → Fechamentos (no celular): caixa, lotes, cheque,
vale, duplicata e PIX listados; conferir o caixa às cegas (diferença zero e provocada); lote:
digitar o borderô às cegas, foto, diferença zero; no lote da maquineta errada, corrigir a
maquineta (diferença provocada some), mover uma venda para o lote seguinte, registro indevido de
um cartão duplicado → venda com diferença → acertar (vale do colaborador) e desfazer; reabrir lote
e sessão. (4) Duplicata: escanear a confissão pelo contas. (5) Maquinetas → Lotes. (6) PDV →
Confissão de Dívida e anexos da venda continuam funcionando (rotas novas).

- **Base para o M9** (conferido em 02/10/2026): `tblmaquineta` (M3, domínio `Mg/Maquineta`, tela
  contas → Maquinetas com permissão Admin/Financeiro em todas e Gerente na própria filial);
  `tblpagamento.codmaquineta` em todo cartão (venda, baixa de título, vale/adiantamento, cobrança
  integrada), `meio` 3 crédito / 4 débito, `estado` E efetivado, `codnegocio` (venda) ou movimento
  de título (`PagamentoListaService::origem`); cancelamento parcial no cartão = pagamento contrário
  (`codpagamentoorigem`, origem = adquirente). Listagem única (`MgPagamentoLista`) já filtra por
  maquineta.
- **Coordenação com o M8.1** (commitado em 02/10/2026; o que segue vale se ele voltar a mexer
  antes do commit do M9): o M8.1 mexe em `api/routes/api.php`,
  `backlog/docs/doc-3…md`, `backlog/tasks/task-188…md`, `components/MgCobrancaDialog.vue`,
  `components/stores/cobrancaStore.js`, `contas/src/pages/pagamento/*`, `negocios/src/pages/
  PagamentoPage.vue`, `negocios/src/stores/pagamento.js` e no banco de dev (tipos de título
  renumerados). Se o M9 tocar nesses arquivos antes do commit do M8.1, commitar só os próprios
  trechos (`git add -p`) para um commit não levar o trabalho do outro; no doc-3 e na TASK-188,
  escrever só na seção/notas do M9.

## M10 — Razão do dinheiro e `transacao` = fato gerador (TASK-39)

**Por quê** (planejado com o Fábio em 02/10/2026, depois do redesenho do M9): o M9 ficou com a
sessão da gaveta inteira (`tblportadorperiodo`, abrir e fechar no PDV, conferência e correção pelo
gerente, `tblpagamento.codportadorperiodo`). Sobra para o M10 o **razão** (decisão 1): o que cai em
cada portador e quando, o saldo do portador (decisão 19) e os lançamentos no detalhe do pagamento.
Na mesma conversa o Fábio padronizou o nome da data do fato: **`transacao` = data e hora do fato
gerador**, `criacao` = quando foi digitado ("vou lançar hoje um PIX feito ontem: a transação é
ontem, a criação é agora").

**Decisões (M10.x, não reabrir)**

1. **O que lança agora**: os meios imediatos, em qualquer portador — dinheiro (1), PIX (17),
   depósito (16), transferência (18) e boleto (15) —, **uma linha por lado que tem portador**
   (destino `+total`, origem `−total`). Cartão (3, 4, inclusive o da empresa) e Mercos Pay (99)
   ficam para o M14. Cheque, vale, compensação, folha, permuta, perda e outros: nada.
2. **Quem gera**: `Mg/Portador/PortadorMovimentoService::sincronizar(Pagamento)`, **chamado
   explicitamente** (sem observer): compara as linhas ativas do pagamento com as que ele deveria
   ter e inativa as que sobram e cria as que faltam (idempotente). Chamado em
   `PagamentoService::{criar, contrario, efetivar, cancelar}` (cobre baixa de título, compensação,
   acerto de RH, PIX, Lio, PagarMe, Saurus, o `fechar` e o `cancelar` da venda e o estorno do
   título), em `PagamentoTituloService::{pagamentoDaForma}` (amarração da cobrança integrada),
   `daBaixa` (boleto BB) e `atualizar` (edição do contas), e em
   `Conferencia/PagamentoCorrecaoService::{corrigir, incluir}` (gravam direto). Caminho novo que
   grave pagamento efetivado precisa chamar o `sincronizar`.
3. **Só do go-live em diante**: mesmo corte das conferências (`config('mg.conferencia_inicio')`,
   `CONFERENCIA_INICIO` no `.env`). Pagamento com `transacao` anterior nunca gera razão, nem
   quando editado ou cancelado depois. O `razao.sql` não copia histórico; o saldo de implantação
   de banco e cofre fica no M12.
4. **Período de quem não é gaveta** (cofre, troco, Caixa Financeiro, banco, adquirente): o
   corrente nasce sozinho no primeiro lançamento, com `inicio` = 00:00 do `CONFERENCIA_INICIO` e
   `saldoinicial` 0 (com `lockForUpdate` no portador, como o `CaixaService::abrir`). Como nada
   anterior gera razão, todo lançamento cabe nele. Fechar com corte e reabrir em cadeia: M12.
   **Gaveta**: a linha usa o `codportadorperiodo` que o M9 já gravou no pagamento (sessão).
5. **`transacao` no razão** (no lugar de `data`): `timestamp` = quando o dinheiro aparece naquele
   portador. Nos meios imediatos é a `transacao` do pagamento; no cartão (M14) será a transação do
   pagamento + o prazo de cada parcela (D+1 débito, D+30/60/90 crédito), uma linha por parcela
   (coluna `parcela` já nasce). Saldo (decisão 19) = `saldoinicial` do aberto mais antigo + Σ
   linhas ativas dos abertos com `transacao` ≤ fim de hoje; depois de hoje = "a cair".
6. **`transacao` = fato gerador em todo o dinheiro**: o M10 renomeia `lancamento` → `transacao`
   em `tblpagamento`, `tblcheque`, `tblextratobancario` e `tblbonificacaoevento` (banco, código,
   resources, filtros `transacao_de/ate`, blades, telas). **O negócio fica fora**
   (`tblnegocio.lancamento`: API, views, MGsis, MG Lara, Dexie): **TASK-194**, junto com o
   levantamento do estoque e do resto do sistema, a `transacao` do pagamento da venda (hoje = hora
   do sync) e a data do fato no Receber título do PDV (hoje só o contas tem o campo).
7. **Gaveta trava na conferência**: para o razão, a sessão só é imutável **depois de conferida pelo
   gerente** (`conferencia` preenchida). Entre o caixa fechar e o gerente conferir, a correção do
   M9 (valor, dinheiro ↔ cartão, incluir) acerta o razão da sessão. Cancelar venda em dinheiro com
   o caixa fechado continua 422 (regra do M9, `CaixaService::vincular`). Demais portadores:
   imutável = `fechamento` preenchido (só existe a partir do M12). Linha nova ou inativada em
   período imutável → 422 "reabra". O **saldo** continua por `fechamento` (sessão fechada pelo
   caixa = `saldofinal` contado), como na decisão 19.
8. **Detalhe do pagamento**: card **Razão** no `@components/MgPagamentoDetalhe.vue` (contas e PDV),
   só quando há linha: portador, transação, valor com sinal (entrou verde, saiu vermelho), onde
   caiu ("Caixa 508 · sessão de 02/10 08:00" ou "período corrente") e as inativadas riscadas, com
   quando saíram. `PagamentoDetalheResource` devolve `razao[]`.
9. Critérios da gaveta (#1 a #6 da TASK-39, que diziam M10) foram para a **TASK-188 como M9.1 a
   M9.6** (#33 a #38); a TASK-39 fica com M10.x a M13. O comentário `TASK-39 AC #2` do
   `CaixaService` passa a `TASK-188 AC #34`.
10. `tblportadortransferencia` (vazia) cai no `razao.sql` (decisão 2), com o model
    `PortadorTransferencia` e as relações dela no `Portador`; as relações `PortadorMovimentoS` de
    `Pix` e `MovimentoTitulo` saem (as colunas somem com a tabela antiga).

**DDL `api/database/razao.sql`** (padrão `conferencia.sql`: `\set ON_ERROR_STOP on`, `BEGIN`,
`lock_timeout`/`statement_timeout`, idempotente, FKs guardadas por `pg_constraint`, `COMMIT`;
roda depois do `conferencia.sql` e antes do `tipo_titulo_limpeza.sql`):

1. `lancamento` → `transacao` em `tblpagamento`, `tblcheque`, `tblextratobancario` e
   `tblbonificacaoevento` (só se `lancamento` existir), e os índices com `lancamento` no nome. A
   view `tblliquidacaotitulo` (Totais de Caixa do MG Lara) acompanha sozinha; ela já expõe
   `transacao`.
2. Recria `tblportadormovimento` só se ainda estiver no formato antigo (coluna `lancamento`) **e
   vazia** (senão aborta): derruba a FK de `tblextratobancarioportadormovimento`, a tabela antiga,
   cria a nova e recria a FK. Colunas: `codportadormovimento bigserial PK`, `codportador NN`,
   `codportadorperiodo NN`, `codpagamento NN`, `valor numeric(14,2) NN`, `transacao timestamp(0)
   NN`, `parcela smallint`, `conciliado bool NN default false`, `inativo`, audit. Índices:
   `codpagamento`; `(codportador, transacao) WHERE inativo IS NULL`; `codportadorperiodo`; único
   `(codpagamento, codportador, coalesce(parcela, 0)) WHERE inativo IS NULL`. FKs para portador,
   período, pagamento e usuários.
3. `DROP TABLE tblportadortransferencia` se existir e estiver vazia.

**Backend**

- `Mg/Portador/PortadorMovimento.php` reescrito à mão (fora do gerador), com `Portador`,
  `PortadorPeriodo`, `Pagamento`; `Pagamento::PortadorMovimentoS`.
- `PortadorMovimentoService`: `MEIOS` (1, 15, 16, 17, 18), `desejadas(Pagamento)` (vazio se não
  efetivado, meio fora da lista ou `transacao` antes do início; senão uma por lado com portador:
  período da gaveta = `codportadorperiodo` do pagamento, demais = `PortadorPeriodoService::corrente`),
  `sincronizar(Pagamento)` (compara por portador, valor, transação e período; inativa as que
  sobram e cria as que faltam; 422 "reabra" em período imutável).
- `Mg/Portador/PortadorPeriodoService`: `corrente(Portador)` (cria com início no go-live),
  `imutavel(PortadorPeriodo)` (gaveta: conferida; demais: fechada), `saldo(Portador, ?Carbon)`.
  Sem rota nem tela: o M11 e o M12 usam.
- Chamadas da decisão 2. Rename da decisão 6 nos models (fillable, casts), services, resources,
  `PagamentoListaService` (`transacao_de/ate`), `PdvPagamentoService`, Conferência, Caixa, lote da
  maquineta, Cheque, Extrato BB, Meta/Bonificação e blades (`pagamento/*`,
  `vale-modelo/emitidos-relatorio` e onde mais a data lida for do pagamento). **Nunca** o
  `lancamento` do negócio.
- `PagamentoDetalheResource` com `razao[]` (portador, valor, transação, período: sessão ou
  corrente, inativo).

**Frontend**: `MgPagamentoDetalhe` com o card Razão; rename em `MgPagamentoLista`,
`MgPagamentoFiltros`, `stores/pagamentoListaStore`, `cobranca/FormaEstorno`; contas
(`pages/pagamento`, `pages/fechamento`, `components/conferencia`, extrato e cheque); pessoas (metas
e RH, bonificação); negocios só onde a data lida é do pagamento.

**Valida (M10)**: (1) `razao.sql` 2x em dev (a segunda não faz nada); Totais de Caixa na view. (2)
PDV 508: abrir o caixa, venda de R$ 10 em dinheiro → detalhe do pagamento mostra +10 na sessão;
venda no cartão e no cheque → sem card Razão. (3) contas: receber título por transferência com
data de ontem → linha no banco com transação de ontem e período corrente criado; editar data e
portador → a antiga riscada, nova criada; estornar → riscada; pagar fornecedor com dinheiro do
cofre → −valor no cofre. (4) Boleto BB reprocessado → linha no banco. (5) Fechamentos: corrigir o
valor do dinheiro antes de conferir → razão acompanha; depois de conferir → 422; cancelar venda em
dinheiro com o caixa fechado → 422. (6) Saldo pelo tinker: caixa aberto, caixa fechado, banco. (7)
Pagamento anterior ao `CONFERENCIA_INICIO` editado → sem razão. (8) Rename: filtro de período das
listagens (contas e PDV), importação do extrato BB, cheques, metas/bonificação no pessoas; `grep`
sem `lancamento` de pagamento, cheque, extrato e bonificação.

**Riscos e bordas**

- Caminho novo que grave pagamento efetivado sem passar pelo `PagamentoService` fica sem razão:
  chamar o `sincronizar` (decisão 2).
- **M11**: o `CaixaService::vincular` grava uma sessão só (`destino ?? origem`); transferência
  gaveta → gaveta vai precisar do período de cada lado (o razão já guarda período por linha).
- Portador em espécie com corrente automático (cofre) que passa a ser gaveta: o índice único de
  corrente impede abrir o caixa. Fechar o corrente (M12) antes de vincular o PDV.
- `tblcheque.lancamento` também é lido pelas telas antigas de cheque do MGsis e do MG Lara
  (abandonadas); no MGdb só o modelo ER cita a coluna.

**Como ficou no código** (02/10/2026, executado em dev, na árvore, sem commit):

- `api/database/razao.sql` rodado 2x em dev (a segunda não faz nada). Além do plano: os dois
  índices do pagamento com `lancamento` no nome foram renomeados e o razão ganhou
  `CHECK (valor <> 0)`.
- `PagamentoService::efetivar` também sincroniza quando o pagamento **já está efetivado** (a
  integração reprocessada pode ter regravado valores; sem isso o `efetivar` voltava cedo).
- `PortadorPeriodoService::corrente`: se já existe período fechado depois do go-live (M12), o
  novo corrente começa logo depois do último `fim`.
- O cheque gerado pelo pagamento grava `transacao` = a do pagamento (era a hora da gravação).
- `PortadorTransferencia.php` apagado; relações antigas removidas de `Portador`, `Pix` e
  `MovimentoTitulo`; `IndiceModels.json` sem as duas tabelas (models mantidos à mão, como o
  cartão Bee). `PortadorResource.movimentoconciliar` conta só linhas ativas.
- Conferido com rollback pelo tinker: transferência com data retroativa (corrente criado com
  início no go-live), edição de data e portador (antiga riscada, nova criada), sincronizar
  repetido sem efeito, estorno, dinheiro do cofre saindo, cheque e PIX anterior ao go-live sem
  razão; na gaveta: pendente sem linha → efetivado na sessão, correção do gerente com o caixa
  aberto e fechado (sem conferir), 422 depois de conferida, registro indevido, cancelar com o
  caixa fechado = 422 do M9; saldo aberta (inicial + linhas) e fechada (`saldofinal`); listagem
  com `transacao_de/ate`, relatório, recibo térmico, extrato, bonificação. Templates compilados
  com o `@vue/compiler-sfc`; o card não foi aberto no navegador.
- **Dev**: os pagamentos feitos antes do M10 (inclusive os da validação do M9, sessões 4 e 5,
  já conferidas) não têm razão; só os novos. Workers da fila reiniciados (`queue:restart`), senão
  continuam com o código antigo na memória.

## M11 — Transferências (TASK-39)

- **Backend** `PagamentoService::transferir(origem, destino, total, obs)` (regras da decisão 22:
  quem registra, nasce efetivada se o dono do destino registrou, gavetas com sessão aberta, dois
  lançamentos no registro marcados "a confirmar"), `confirmar`, `cancelar(justificativa)`;
  `pendentes(portador)`; rotas `v1/pdv/caixa/transferencia` (POST, confirmar, cancelar) e
  `v1/pagamento/transferencia` (contas); `GET v1/portador/caixas?codfilial=` (portadores `E` da
  filial com saldo, sessão, pendentes, `ehGaveta`); `GET v1/portador/{id}/saldo`.
- **Base deixada pelo M10** (reusar, não recriar): a transferência é um pagamento com origem e
  destino, e o `PortadorMovimentoService::sincronizar` já lança as duas linhas (−total na origem,
  +total no destino), cada uma no período do seu portador. Hoje só lança efetivado: a
  transferência **pendente "a confirmar"** (decisões 13 e 22) precisa que o `desejadas` aceite o
  estado P quando o pagamento tem os dois portadores. O saldo vem de
  `PortadorPeriodoService::saldo`. **Sessão de cada lado**: o `CaixaService::vincular` do M9
  valida e grava uma sessão só (`destino ?? origem`); gaveta → gaveta precisa validar as duas
  (422 se uma estiver fechada). O razão já acha a sessão do outro lado por
  `CaixaService::sessaoDe`. Meio da transferência entre espécies = dinheiro (1); para banco,
  depósito (16) ou transferência (18).
- **Frontend**: negocios `DialogTransferir.vue` (`MgSelectPortador agrupar`, gavetas fechadas
  desabilitadas com motivo), lista com cores por estado, Confirmar/Cancelar; contas → página
  **Caixas** (`pages/caixa/Index.vue`, `caixaStore`, drawer filial/de/até): abas Portadores e
  Transferências (pendentes, histórico, FAB "Nova transferência" de → para). Menu Movimento → Caixas.
  A transferência já aparece na listagem única (origem X, `PagamentoListaService`); Confirmar/
  Cancelar entram no `MgPagamentoDetalhe`.
- **Valida**: gaveta → cofre 200 pendente, gerente confirma; gerente registra "recebi 250" (nasce
  efetivada); cancelar com justificativa; gaveta → gaveta com destino fechado → 422; fechar gaveta
  com pendente chegando → 422; gaveta → Caixa Financeiro (Financeiro confirma no contas); cancelar
  com sessão já fechada → 422.

**Como ficou no código** (02/10/2026, executado em dev; sem DDL):

- **Backend** `PagamentoService`: `ehTransferencia` (os dois lados e sem venda), `meioTransferencia`
  (espécie → espécie = dinheiro; espécie → banco = depósito; o resto = transferência),
  `transferir(origem, destino, total, obs, ?codpdv)` (trava os dois portadores contra o fechar do
  caixa; gaveta sem sessão aberta = 422; nasce **efetivada** se quem registra opera o destino,
  senão **pendente**; `codfilial` = filial da origem), `confirmar` (só o dono do destino),
  `cancelarTransferencia` (qualquer dos dois donos; o `cancelar` genérico passou a exigir, em
  transferência, as sessões de gaveta onde ela caiu abertas — 422 "reabra"; período fechado de
  não-gaveta o razão já recusa) e `pendentes(portador)`. Donos: `Pagamento/TransferenciaAutorizador`
  (decisão 23; `Portador::CAIXA_FINANCEIRO` = 100). Razão: `desejadas` aceita a transferência
  **pendente** ("a confirmar" = o estado do pagamento, sem coluna própria no razão).
- **Sessão da gaveta e transferência**: `CaixaService::gavetaDoPagamento` escolhe o lado gaveta
  (destino primeiro, depois origem; antes era `destino ?? origem` e gaveta → cofre ficava sem
  sessão) e vale também para meio ≠ dinheiro quando os dois lados existem (depósito da gaveta no
  banco). Gaveta → gaveta grava a sessão do destino; a da origem o razão acha por `sessaoDe`.
  `CaixaService::dinheiro` passou a somar também os pagamentos com linha do razão na sessão
  (documento novo **X**, transferências), contando a transferência desde o registro, ainda a
  confirmar. `CaixaService::fechar` recusa (422) com transferência **chegando** a confirmar.
- **Rotas**: PDV `v1/pdv/caixa/transferencia` (GET as da sessão aberta ou da última e as a
  confirmar; POST com `sentido` E sai da gaveta / R chega nela; `{id}/confirmar`, `{id}/cancelar`)
  no `CaixaController`; contas `v1/pagamento/transferencia` (GET com `codfilial` de qualquer dos
  lados, `estado`, período; POST `codportadororigem/destino`; confirmar, cancelar),
  `v1/portador/caixas?codfilial=` e `v1/portador/{id}/saldo` no `Caixa/CaixasController` +
  `CaixasService` (saldo da decisão 19 e "a cair"). `TransferenciaResource` = linha da listagem
  + os dois lados + `podeConfirmar`/`podeCancelar`; o `PagamentoDetalheResource` ganhou os
  mesmos flags e `transferencia`. Detalhe do PDV mostra transferência que chega na gaveta dele;
  `podeVer` do contas aceita a filial de qualquer dos lados.
- **Saldo de gaveta às cegas**: `v1/portador/caixas` e `/saldo` só mostram saldo de gaveta com a
  última sessão fechada **e conferida** (antes disso o gerente confere às cegas, M9); dos demais,
  para quem confere a filial (`ConferenciaAutorizador`). O PDV usa `v1/portador/caixas` só para
  saber as gavetas fechadas (`bloqueio`).
- **Frontend**: `@components/MgSelectPortador` — `agrupar` agora segue a decisão 22 ("Desta
  filial" = os `E` da filial; o resto em "Mais opções"), props novas `excluir` e `bloqueios`
  (desabilitado com o motivo); `MgPagamentoDetalhe` com Confirmar/Cancelar e "a confirmar" no
  razão (`pagamentoListaStore.transferencia`). negocios: `components/caixa/DialogTransferir.vue`
  (Enviar de / Receber em a gaveta; destinos `E` e `B`) e card Transferências na tela do Caixa
  (cor por estado, Confirmar/Cancelar). contas: Movimento → **Caixas** (`pages/caixa/Index.vue`,
  `stores/caixaStore.js`, `drawers/CaixaFiltrosDrawer.vue`): abas Portadores e Transferências (a
  confirmar e histórico do período), FAB Nova transferência de → para; a linha abre o detalhe do
  pagamento. Sessão do Fechamentos mostra "Transferências" no dinheiro.
- **Conferido** (tinker com rollback, gavetas de teste na filial 102 com as usuárias reais de
  Caixa, Gerente e Financeiro): gaveta → cofre 200 pendente com os dois lançamentos (−200 na
  sessão, +200 no corrente do cofre), caixa não confirma (403), gerente confirma sem mexer no
  razão; gerente registra "recebi 250" efetivada; suprimento cofre → gaveta pelo caixa efetivado;
  cancelar sem justificativa 422, com justificativa inativa os dois; gaveta fechada de origem ou
  destino 422; gaveta → gaveta com cada linha na sessão do seu lado e o dinheiro das duas sessões
  certo; fechar com pendente chegando 422, com pendente saindo fecha; cancelar com a sessão de
  origem fechada 422; saldo do cofre = Σ lançamentos; quem não opera nenhum lado 403; pela camada
  HTTP: PDV → Caixa Financeiro pendente, listagem única com origem X, Financeiro confirma no
  contas, cofre → gaveta fechada 422, cancelamento pelo contas. Lint e templates compilados; não
  aberto no navegador.

**Dúvidas para o Fábio (M11)**, decididas assim até ele dizer:

1. **Saque** (banco → espécie): meio transferência (18); o plano só falava de espécie → espécie e
   espécie → banco.
2. **Destinos**: espécie e banco, nos dois apps. Adquirente e cartão da empresa ficam fora (o
   repasse é do M14).
3. **Suprimento pelo caixa** (cofre → gaveta registrado pelo caixa) nasce efetivado sem o gerente
   confirmar a saída do cofre: é a regra da decisão 22 ao pé da letra (o dono do destino registrou).
4. **Financeiro não opera cofre e troco da loja** (decisão 23 literal): Financeiro sem ser Gerente
   não registra nem confirma transferência só entre cofre/troco/gaveta.
5. **Saldo de gaveta** só depois da conferência (às cegas), na página Caixas e no `/saldo`.
6. O dinheiro da sessão desconta a sangria ainda a confirmar (o dinheiro já saiu da gaveta).

## M12 — Períodos no contas (TASK-39)

- **Base deixada pelo M10** (reusar, não recriar): `PortadorPeriodoService::corrente` (cria o
  corrente no primeiro lançamento, com início no go-live ou logo depois do último `fim`),
  `imutavel` (fechado = `fechamento` preenchido nos não-gaveta) e `saldo` (decisão 19). O razão
  já recusa com 422 mudar linha de período imutável. Fechar com corte = gravar `fim` = corte,
  `fechamento`, `saldofinal` = saldo até o corte, e **reapontar para o corrente novo as linhas
  ativas com `transacao` depois do corte** (`codportadorperiodo`). O saldo de implantação é o
  `saldoinicial` do primeiro período (hoje 0, início no `CONFERENCIA_INICIO`).

- **Backend** `PortadorPeriodoController` (`v1/portador-periodo`: index, show, fechar com `corte`,
  reabrir, lançamento avulso em período de não-gaveta — Financeiro); `CaixaController` lado contas
  (fechar sessão com contagem para Gerente, resumo, PDF).
- **Frontend contas**: Caixas → aba **Períodos** (portador, início, fim, estado, saldos, diferenças
  e PDF quando gaveta; Fechar com `MgInputData` de corte; Reabrir; Lançamento avulso).
- **Valida**: cofre com lançamentos em set e out → fechar com corte 30/09 → `saldofinal` sem outubro,
  corrente novo com outubro; lançar com data de setembro → 422; reabrir em cadeia e fechar na ordem;
  saldo de implantação de banco/cofre; fechar sessão de gaveta pelo contas.

**Como ficou no código** (02/10/2026, executado em dev; commitado em 03/10 sem validação; sem
DDL). Escopo ajustado pelo Fábio: a sessão da gaveta (fechar com contagem, conferir, reabrir) é do M9 e não
entrou aqui (o "`CaixaController` lado contas" do plano original saiu).

- **Backend** `PortadorPeriodoService`: `doMomento(portador, transacao)` (decisão 18: o período
  cuja faixa contém a data; depois do último corte, o corrente) — o razão
  (`PortadorMovimentoService::periodo`) passou a usar no lugar do `corrente`; `corrente` nasce
  com o `saldofinal` do último fechado (era sempre 0); `movimento(periodo, ?ate)`; `fechar(periodo,
  ?corte)`: só não-gaveta, do mais antigo para o mais novo (422), o corrente fecha em `fim` = fim
  do dia do corte (corte entre o início e ontem, senão 422), `saldofinal` = inicial + linhas até
  o fim, as linhas ativas depois do corte vão para o período seguinte (o corrente nasce se
  preciso) e o `saldoinicial` do seguinte recebe o `saldofinal`; o reaberto fecha no mesmo `fim`;
  `reabrir`: só o fechado mais novo do portador (422 "reabra antes o …"), limpa `fechamento` e
  `saldofinal`; `lancar(portador, motivo, valor com sinal, ?transacao, obs)`: pagamento sem
  documento (motivo T/F/R/A, efetivado, meio dinheiro na espécie e transferência nos demais), no
  período da data — fechado = 422 pelo razão; antes do `CONFERENCIA_INICIO` = 422; gaveta = 422.
- **Rotas** `v1/portador-periodo` (`PortadorPeriodoController`, Financeiro/Admin): index
  (`codportador`, `codfilial`, `estado`, `transacao_de/ate` = períodos que encostam no intervalo,
  `gaveta`), show (com os lançamentos), `{id}/fechar` (`corte`), `{id}/reabrir`, `lancamento`
  (`codportador`, `motivo`, `valor` com sinal, `transacao`, `observacoes`).
  `PortadorPeriodoResource`: saldos de sessão de gaveta só depois de conferida (às cegas).
- **contas** → Caixas → aba **Períodos** (só Financeiro/Admin): portador, início, fim, estado
  (corrente, reaberto, fechado; sessão do caixa), saldo inicial e final (aberto: "até agora");
  Fechar com `MgInputData` de corte (padrão: último dia do mês anterior), Reabrir, detalhe com os
  lançamentos (cada um abre o pagamento) e Lançamento avulso (FAB e no detalhe). Sessão de gaveta
  fechada abre a tela Sessão do Fechamentos (M9). Os filtros da página (filial, de, até) valem
  para os períodos.
- **Conferido** (tinker com rollback, `CONFERENCIA_INICIO` simulado em 01/08 para ter dois meses;
  cofre Caixa Botânico e banco Brad Botânico; usuário só Financeiro): implantação +1000 no
  cofre sem período (corrente nasce sozinho); taxa em setembro, rendimento em 01/10, tarifa em
  20/10; saldo 1020 (a tarifa a cair); corte hoje e corte antes do início = 422; corte 30/09 →
  `saldofinal` 970, corrente novo de 01/10 com inicial 970 e outubro levado; avulso e
  cancelamento em setembro fechado = 422; reabrir setembro com outubro fechado = 422; reabrir na
  ordem, lançar em 20/09 cai em setembro reaberto; fechar outubro antes de setembro = 422; fechar
  na ordem propagando os saldos (960, 1010); saldo pela decisão 19; implantação de 5000 no banco;
  avulso e fechar em gaveta = 422; index, show e sessão sem saldos antes de conferida; Gerente =
  403. As conferências do M11 rodadas de novo depois da mudança no razão. Lint e templates
  compilados; não aberto no navegador.

**Valida M10 + M11 + M12** (roteiro único, do mais arriscado para o menos; em dev só o PDV 508
tem gaveta — para gaveta → gaveta, cadastrar outro portador Espécie da 101 e vincular a outro PDV):

1. **Caixa do PDV** (negocios `/caixa`): abrir com 50 + 200; venda de R$ 10 e pagar vale em
   dinheiro → card Razão com +10 na sessão; fechar e conferir às cegas no Fechamentos; o dinheiro
   do sistema bate.
2. **Transferências no PDV**: gaveta → Caixa Atacado 200 amarela (a confirmar); o caixa não
   confirma, o gerente confirma; gerente registra gaveta → cofre 250 verde; cancelar com
   justificativa → riscada e as duas linhas do razão riscadas; gaveta fechada desabilitada com
   "Caixa fechado" (422 pela API); cofre → gaveta registrado no contas por quem não opera a gaveta
   → fechar o caixa = 422; com pendente saindo o caixa fecha e cancelar depois = 422.
3. **contas → Caixas**: Portadores (saldos dos cofres, gaveta "saldo após a conferência",
   chegando/saindo), Transferências (a confirmar, histórico, FAB gaveta → Caixa Financeiro que o
   Financeiro confirma), Confirmar/Cancelar no detalhe do pagamento.
4. **Períodos** (Financeiro): Ajuste +1000 em 02/10 num cofre, taxa −30 em 03/10; corte padrão
   30/09 = 422; corte 02/10 → final 1000 e corrente de 03/10 com a taxa; avulso em 02/10 = 422;
   reabrir o antigo antes do novo = 422; reabrir e fechar na ordem; implantação num banco; sessão
   de gaveta fechada abre o Fechamentos.
5. **M10 restante**: receber título por transferência com data de ontem, editar portador/data,
   estornar (antiga riscada, nova criada); filtro de período nas listagens, extrato BB, cheques,
   bonificação.

Saldos × razão: `select p.portador, pm.codportadorperiodo, sum(pm.valor) from tblportadormovimento
pm join tblportador p using(codportador) where pm.inativo is null group by 1,2 order by 1,2` e
`select * from tblportadorperiodo order by codportador, inicio`.

**Dúvidas para o Fábio (M12)**, decididas assim até ele dizer:

1. **Reabrir "em cadeia"** = um de cada vez, do mais novo para o mais antigo (o botão Reabrir de um
   período antigo recusa enquanto houver um mais novo fechado). Não reabre os seguintes sozinho.
2. **Saldo de implantação** = lançamento avulso de Ajuste na data do go-live (fica no razão e no
   detalhe do período), não um `saldoinicial` editável.
3. **Lançamento avulso por portador e data** (o período vem da data), e não pelo período: a
   implantação precisa funcionar num portador que ainda não tem período.
4. **Corte até ontem**; o corrente novo só nasce se houver lançamento depois do corte (senão no
   próximo lançamento, com o saldo final do fechado).
5. **Aba Períodos só para Financeiro/Admin** (o Gerente vê Portadores e Transferências).
6. Lançamento avulso **na gaveta** (M9.5, TASK-188 #37) continua sem fazer: o M12 é só de
   não-gaveta.

## M13 — Itens do caixa e repasse ao parceiro (TASK-39)

**Por quê** (03/10/2026, com o Fábio, antes de codar): além dos itens dos parceiros, o M13 levou a
pendência da gaveta que ficou do M10 — ajuste de caixa na abertura e no fechamento, e o lançamento
avulso no PDV (TASK-188 M9.5, #37). Revendo o plano, apareceu a incoerência entre "o envelope fica na
gaveta" (glossário) e "o caixa sobe ao escritório com tudo" (M9). O Fábio descreveu a operação
real: o caixa sobe com tudo, às vezes nem fecha no PDV; o gerente faz a sangria ali, reconta o que
sobrou para o troco de amanhã e fecha. Pediu KISS: **uma tela polivalente**, um ajuste só, sem
etapas e controles paralelos.

**Decisões (M13.x, não reabrir)**

1. **Uma tela do caixa** (`@components/MgCaixaSessao.vue`), a mesma no PDV (negocios `/caixa`) e
   no contas (Fechamentos → caixa): abrir, itens, avulsos, transferências, dinheiro, fechar,
   reabrir, borderô.
2. **Fechar é a conferência.** Fecha quem estiver com o dinheiro: o caixa no PDV ou o gerente no
   contas. A conferência às cegas do M9 (gerente digita depois do caixa) saiu; o razão trava no
   fechamento (`conferencia` gravada junto). Corrigir pagamento da sessão: reabrir.
3. **Contagem por quantidade** de cédulas (200, 100, 50, 20, 10, 5, 2) e moedas (1; 0,50; 0,25;
   0,10; 0,05; 0,01), em jsonb na sessão (abertura e fechamento), mais o estoque dos itens C.
4. **Saldo inicial = envelope** (`saldofinal` da sessão anterior; 0 na primeira). Contado ≠ envelope
   na abertura → pagamento de ajuste (motivo A). No fechamento, contado (o que fica depois da
   sangria) ≠ sistema → **um** ajuste; depois dele, sistema = contado (decisão 21). Sempre o mesmo
   registro: reabrir cancela, fechar de novo reativa.
5. **Go-live:** a gaveta abre com 0 e recebe o troco por suprimento do cofre; o saldo de
   implantação vai só no cofre/caixa da filial (avulso de Ajuste do M12).
6. **Modo C dentro do dinheiro da gaveta:** entrada/saída de estoque (bloco de ingressos) gera
   pagamento na gaveta e o estoque contado soma na contagem; o saldo da gaveta inclui chips e
   ingressos a valor de face. Venda de chip não lança nada (dinheiro entra, chip sai).
7. **Item:** um pagamento `entrada − saída` por item na sessão, alterado no mesmo registro (zero
   cancela; voltando, reativa o mesmo). Vendido (modo M) é só informação.
8. **Repasse no fechamento:** por item com pessoa e líquido ≠ 0 (C: abertura + entrada − saída −
   fechamento; M: entrada − saída), título com pessoa e conta do item, filial da gaveta, número
   `AAAA-MM-DD-P{sessão}` + sufixo, transação/emissão/vencimento = dia do fechamento. Líquido > 0 =
   **200 Duplicata a Pagar**; < 0 = **100 Duplicata a Receber** (o parceiro nos deve). Item sem
   pessoa: só informativo. No cadastro, **pessoa obriga conta contábil** (o título exige conta).
9. **Reabrir** (Gerente da filial, Financeiro, Admin; só a última sessão): estorna os títulos de
   repasse (422 se já agrupados ou pagos) e cancela o ajuste de fechamento.
10. **Avulso na gaveta:** entrada ou saída, motivo T/F/R/A e histórico, com o caixa aberto;
    excluir = cancelar, só quem lançou, com o caixa aberto. Ajuste e item não se excluem nem se
    corrigem pela correção do gerente (422): mexem-se pela tela do caixa.
11. **Listagem única:** pagamento de item tem origem nova **I "Item do caixa"** (documento = o
    item); ajuste e avulso continuam origem Avulso com o motivo.
12. **Aba Itens** (contas → Caixas): qualquer usuário consulta.
13. **Borderô com tudo:** contagem por cédula/moeda e itens C (abertura × fechamento), envelope,
    ajustes, sistema × contado, itens com líquido e título, avulsos.

**DDL `api/database/caixa_item.sql`** (padrão `conferencia.sql`; roda depois do `razao.sql` e
antes do `tipo_titulo_limpeza.sql`): `tblcaixaitem` com os 6 seeds (Chips de celular C, Ingressos
impressos C, Bilhete Agora M, BlackTicket M, Redeflex recarga M, Bradesco Expresso M; pessoa e conta
nulas), `tblcaixaitemlancamento`, `tblpagamento.codcaixaitemlancamento`,
`tblportadorperiodo.codpagamentoabertura/codpagamentofechamento/contagemabertura/contagemfechamento`
e as FKs.

**Como ficou no código** (03/10/2026, executado em dev; commitado sem validação em `e8ab52048`):

- **DDL** rodado 2x em dev (a segunda não faz nada). Uma consulta `count(*)` sobre a view
  `tblliquidacaotitulo` estava presa havia 16 h no psql de dev segurando lock de `tblpagamento`:
  foi cancelada (`pg_cancel_backend`) para o `ALTER` passar.
- **Backend `Mg/Caixa`**: `CaixaItem`, `CaixaItemLancamento` (`liquido()`), CRUD
  (`CaixaItemController`/`Service`/`Request`/`Resource`, `v1/caixa-item`, Admin/Financeiro;
  listagem livre), `CaixaItemLancamentoService` (aba Itens, SQL cru, totais por item).
  `CaixaService`: `contagem`, `envelope`, `lancamentos` (cria os de item ativo cadastrado depois da
  abertura), `pagamentoNaGaveta`, `abrir(gaveta, contagem, itens)`, `salvarItem`, `lancarAvulso`,
  `cancelarAvulso`, `ehAjuste`, `fechar(sessao, contagem, itens)` (trava contra transferência
  chegando, ajuste, títulos, grava `conferencia`), `reabrir` (estorna títulos, cancela ajuste),
  `dinheiro` com documentos **I** (item) e **J** (ajuste), `painel` (dinheiro, informativo,
  ajustes, itens, avulsos); `conferir`/`desconferir` saíram. `SessaoResource` mudou para
  `Mg/Caixa` e é sempre completo (painel, transferências, `podeOperar`, `podeReabrir`).
  `CaixaController` reescrito: `v1/pdv/caixa` (só a gaveta do PDV e se está aberta) e
  `v1/caixa/gaveta/{codportador}` (+ `/abrir`), `v1/caixa/sessao/{id}` (+ `/fechar`, `/reabrir`,
  `/item/{codcaixaitem}`, `/avulso`, `/bordero`, `/bordero/{impressora}`),
  `v1/caixa/avulso/{id}/cancelar`, `v1/caixa/item-lancamento`. Saíram as rotas
  `v1/pdv/caixa/{abrir,fechar,transferencia...}` e `v1/conferencia/sessao/...`.
  `TituloService::criar` aceita `sufixo` (sufixa o número informado). Pendências do Fechamentos:
  caixas **abertos**. Correção do gerente: mensagens "caixa fechado"; ajuste e item = 422. Razão:
  "já foi fechado". `PagamentoListaService` origem I. Borderô (`bordero-termica`) completo.
- **Frontend**: `@components/MgCaixaSessao.vue`, `caixa/{ContagemCaixa, ItemCaixaDialog,
  AvulsoCaixaDialog, TransferirCaixaDialog}.vue`, `stores/caixaSessaoStore.js`;
  `pagamentoListaStore.transferencia` usa a rota do contas nos dois apps; `MgPagamentoFiltros` com a
  origem "Item do caixa". negocios: `CaixaPage` só embrulha a tela única; `stores/caixa.js` só
  com a gaveta e o bloqueio do Dinheiro; `components/caixa/DialogTransferir.vue` apagado. contas:
  `fechamento/Sessao.vue` embrulha a tela única (Fechamentos → "Caixas abertos"),
  `conferenciaStore` sem a sessão, Cadastros → **Itens do Caixa** (`pages/caixaItem`,
  `stores/caixaItemStore.js`), Caixas → aba **Itens** (`caixaStore.buscarItens`).
- **Dev**: Chips de celular e Bilhete Agora com o parceiro Tim Celular (1042), Ingressos impressos
  com Vivo (11498), conta 10 Outras Entradas; os outros três sem parceiro. Workers reiniciados
  (`queue:restart`).
- **Conferido** (tinker com rollback, PDV 508, gaveta 202062): abrir com 2×100 + 1×50 + chips 100
  sobre envelope 250 → ajuste +100, sistema 350; abrir de novo 422; ingressos entrada 500; Bilhete
  Agora 130 → 120 (mesmo pagamento), zerado (cancela) e de volta (o mesmo reativado); avulso de
  saída 17,99; outro usuário excluindo = 403, quem lançou exclui; ajuste não se exclui; sangria
  400 ao Caixa Financeiro; correção de item = 422; fechar com 3×100 + chips 85 + ingressos 380 →
  contado 765, ajuste 212,99, sistema = contado, razão da sessão + envelope = contado; títulos 200
  de 15 (Chips, `…-P40`), 120 (Ingressos) e 120 (Bilhete Agora, `…-P40 (1)`); borderô PDF;
  listagem origem I; reabrir com título movimentado = 422; reabrir estorna e cancela o ajuste;
  fechar de novo reusa o mesmo ajuste; item com caixa fechado = 422; abertura seguinte igual ao
  envelope sem ajuste. Pela camada HTTP: CRUD do item (pessoa sem conta = 422), gaveta, abrir,
  itens, avulso (motivo inválido 422), transferência, sessão, fechar, item-lancamento, listagem,
  borderô, pendências, reabrir, `v1/pdv/caixa`. php -l, eslint (contas, negocios e cópia de
  `@components`), templates compilados (`@vue/compiler-sfc`). **Não aberto no navegador.**

**Valida (M13)** (PDV 508 em dev, gaveta "Gaveta Dev Fabio"; envelope atual R$ 250):

1. contas → Cadastros → **Itens do Caixa**: os 6 itens; editar um com parceiro sem conta → recusa;
   inativar/reativar.
2. negocios → **Caixa**: abrir contando cédulas/moedas (ex.: 2×100 e 1×50) e chips 100 → ajuste
   de abertura no quadro Dinheiro (contado − envelope).
3. Vender 1 chip em dinheiro → só a venda aparece (o chip não lança nada).
4. Itens: Ingressos impressos "estoque recebido" 500; Bilhete Agora vendido 120, entrou 120 → no
   Dinheiro, "Itens do caixa" +620. Mudar o Bilhete para 130 e voltar para 120: na listagem de
   pagamentos (origem Item do caixa) continua um pagamento só.
5. Lançamento avulso: saída 17,99 "Ajuste de caixa" com histórico; outro de teste e excluir;
   logado com outro usuário, o botão de excluir não aparece.
6. Transferência (sangria) da gaveta para o cofre.
7. Fechar contando o que fica (cédulas) + chips 85 + ingressos 380 → ajuste de fechamento se
   divergir; borderô impresso/visto com contagem, itens, avulsos e ajustes. contas → Títulos:
   Duplicata a Pagar de 15 (Tim), 120 (Vivo) e 120 (Tim, número com sufixo), vencendo hoje.
8. contas → Caixas → aba **Itens**: as linhas da sessão e os totais por item.
9. Agrupar um dos títulos no contas → Fechamentos/Caixas → abrir a sessão → Reabrir caixa = 422;
   desfazer o agrupamento → reabrir: títulos estornados, ajuste de fechamento desfeito.
10. **Gerente fechando no contas**: abrir o caixa no PDV e não fechar; contas → Fechamentos →
    "Caixas abertos" → mesma tela: sangria, contar, Fechar.
11. Lote, cheque, vale, duplicata e venda no Fechamentos continuam iguais (M9).

Consultas: `select * from tblcaixaitemlancamento order by codcaixaitemlancamento desc limit 10`;
`select codportadorperiodo, saldoinicial, saldofinal, codpagamentoabertura, codpagamentofechamento,
contagemabertura, contagemfechamento from tblportadorperiodo where codportador = 202062 order by
inicio desc limit 3`; razão da sessão: `select sum(valor) from tblportadormovimento where
codportadorperiodo = :id and inativo is null` (+ saldo inicial = saldo final).

**Dúvidas para o Fábio (M13)**, decididas assim até ele dizer:

1. **Contagem C da abertura não se edita** depois de aberto (o ajuste de abertura dependeria
   dela): errou a contagem, a diferença aparece no fechamento.
2. **Item novo cadastrado com o caixa aberto** entra na sessão com abertura 0.
3. **Número do título** `AAAA-MM-DD-P{sessão}` + sufixo cabe nos 20 caracteres até a sessão
   99.999; depois disso precisa encurtar.
4. **Título nasce vencendo no dia do fechamento** (decisão do plano): aparece como vencido no dia
   seguinte até o financeiro agrupar.
5. **Avulso no PDV aceita os quatro motivos** (taxa, tarifa e rendimento também), como no plano.
6. **Fechar no contas** usa a mesma permissão de operar a gaveta (Caixa/Gerente da filial,
   Financeiro, Admin); **reabrir**: Gerente da filial, Financeiro, Admin.
7. A tela do caixa **mostra o sistema** o tempo todo (sem "às cegas"), para o caixa e o gerente.

## M14 — Cartões no razão e conciliação (TASK-195; a definir quando chegar)

Prazos e taxas por adquirente/maquineta; lançamentos do cartão na adquirente por parcela (D+1 débito,
D+30… crédito); repasse adquirente → banco como transferência; taxas e débitos da adquirente como
pagamento sem documento; fatura do cartão da empresa como período com vencimento e compras
parceladas em faturas futuras; conciliação razão ↔ extrato (`tblextratobancarioportadormovimento`),
importação de extrato de adquirente e de fatura. Ligar `PortadorMovimentoService::lancar` para meios
crédito/débito.

## M15 — Pagamento por API de banco e integrações de cancelamento (TASK-196)

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
  **Ainda não criada** (01/10/2026). O M7 foi absorvido pelo M6.1 e virou os ACs M6.1.5 e M6.1.6
  da TASK-188; quando for criada, ela fica só com M8 e M9.
- **TASK-39**: `--dep` na task de balcão; ACs M10 a M13 (já lançados; ajustar os que citam
  liquidação/transferência para pagamento).
- **Futuras**: M14 = TASK-195, M15 = TASK-196 (criadas em 03/10/2026, prioridade Medium).
- Cada milestone: marcar os ACs, commit `[UPD] TASK-nn Mx …` só depois da validação e do OK, com os
  `.md` do backlog no mesmo commit.

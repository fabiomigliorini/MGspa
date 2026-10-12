---
id: TASK-218
title: >-
  Pagamento que não bate com a venda ou com os títulos não aparece em lugar
  nenhum, e baixa no pagamento errado não tem como ser desfeita
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-10 21:30'
updated_date: '2026-10-12 02:17'
labels:
  - contas
  - negocios
dependencies: []
priority: high
type: enhancement
ordinal: 223000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (10/10/2026): desligar o Desamarrar no detalhe do pagamento (contas e PDV) e avaliar se o conceito de amarrar/desamarrar precisa existir. Em 11/10/2026, ao perguntar onde se vê o pagamento que ficou órfão ou que não bate com a venda ou com os títulos (a tela Pagamentos não resolvidos saiu na TASK-220), ele juntou as duas coisas nesta task.

## Decisões (Fábio, 11/10/2026)

**Regra, sem exceção:** o pagamento sempre bate com a soma das linhas dele na tblmovimentotitulo (left join, todas as linhas, estornos inclusive) ou com o negócio. No acerto de RH, somam-se também as rubricas do acerto (o RH não muda agora: go-live amanhã). Transferência entre portadores não é pagamento.

**Tela Divergências** (contas), aberta por um botão no canto de cima da listagem de Pagamentos, como o Vales emitidos:
- venda fechada (natureza financeira) em que pagamentos + parcelas não somam o total, inclusive a venda sem nenhum pagamento;
- pagamento valendo cuja soma das baixas não bate com o valor (menos a devolução); sem baixa e sem venda; em venda aberta (qualquer uma) ou cancelada. O pagamento de venda fechada sem baixa se confere pela venda;
- o histórico todo, com filtro de caso, data e filial; uma lista só; o clique só abre a tela (venda → Venda com diferença; pagamento → detalhe);
- acesso pela regra do painel (ConferenciaAutorizador): Financeiro e Administrador todas as filiais, Gerente só as dele.

**Desfazer a baixa errada:** amarrar e desamarrar deixam de existir. Cancelar o pagamento estorna as baixas dele junto, inclusive no integrado: o PIX volta a ficar sem uso, o cartão volta a ser só o pedido. O boleto não se cancela (é do próprio título). Pagamento de venda continua sendo cancelado pelo negócio.

**Detalhe do pagamento:** saem Saldo, Amarrado e Livre.

**Painel:** o card Vendas com diferença passa a incluir a venda fechada sem pagamento (continua desde o go-live).

**Correção do histórico** (script SQL, o Fábio roda no go-live), só antes do go-live:
- troco em dinheiro não gravado: o pagamento antigo guardava o valor entregue (R$ 200 numa venda de R$ 109); passa a total = o que ficou e valortroco = o resto (total + troco não muda; a DIMP soma os dois). Só quando o troco é menor que o maior pagamento em dinheiro da venda (troco do tamanho do pagamento inteiro não é troco; o banco também não aceita pagamento zerado);
- pagamento repetido: cancela a cópia (mesmo meio e valor) quando, sem ela, a venda bate; justificativa "Pagamento repetido no sistema antigo". Só a cópia lançada à mão, sem baixa nem cheque: o integrado repetido pode ser cobrança em dobro de verdade;
- troco do vale: o mesmo do troco em dinheiro, no vale que não baixou título.
No troco não fica rastro. Script: api/database/pagamento_historico.sql (idempotente, transacional; ~6 min no dev).

O que sobra das vendas antigas (29.404 no dev) e a correção delas ficaram para a TASK-221.

Fica como está: pagamento valendo em venda já cancelada (o Cancelar recusa "cancele pelo negócio"; 0 casos) e o Voltar da Venda com diferença (sempre o painel).

No dev, teste do script com rollback: 1.216.225 vendas que não fecham; troco em dinheiro 1.158.671, repetido 26.043 vendas (26.052 cópias), troco do vale 2.107; sobram 29.404. Pagamentos que não batem: 12 no histórico todo (1 desde o go-live).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Desamarrar desligado no detalhe do pagamento (contas e PDV): a tela não oferece e o servidor recusa com mensagem
- [x] #2 Decidido se amarrar/desamarrar continua existindo e como se desfaz uma baixa feita no pagamento errado
- [x] #3 Cancelar o pagamento estorna as baixas junto, inclusive no PIX e no cartão integrado (o PIX volta a ficar sem uso, o cartão volta a ser só o pedido); boleto não se cancela
- [x] #4 Sai o Desamarrar (serviço, rotas do contas e do PDV) e o Saldo/Amarrado/Livre do detalhe do pagamento
- [x] #5 Tela Divergências no contas, aberta pela listagem de Pagamentos: vendas que não fecham e pagamentos que não batem com as baixas ou com a venda, histórico todo, filtro de caso/data/filial, acesso pela regra do painel
- [x] #6 Painel: Vendas com diferença inclui a venda fechada sem nenhum pagamento
- [x] #7 Script SQL de go-live corrige o histórico: troco em dinheiro, troco do vale e pagamento repetido
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Implementação (11/10/2026)

**Cancelar desfaz as baixas** (`PagamentoTituloService::cancelar`): estorna as baixas valendo (e o título do vale/adiantamento, só se não movimentado) e cancela o pagamento; recusa venda (pelo negócio), acerto de RH (pelo acerto) e boleto (`baixaDoBanco`). O integrado agora se cancela: o PIX volta a ficar sem uso (`PixUsoService::pagamentoCancelado`, já existia) e o pedido da Stone/SafraPay pode ser usado de novo na baixa (`PagarMeService::pagar` reaproveita a mesma linha e limpa `codmaquinetalotecancelamento`). `movimentosAtivos` veio para o `PagamentoTituloService` e ignora o título que soma zero dentro do pagamento (estorno antigo 930 sem `codmovimentotituloestorno`: não estorna duas vezes). Ocorrência de estorno (`pagamentoEstornado`) quando havia baixa.

**Sai o Desamarrar**: método, rotas `pagamento/{id}/desamarrar` e `/estornar` (contas e PDV), `PdvPagamentoService::desamarrar`; o `PagamentoPendenciaService` foi apagado (saldo/amarrado/livre); o `PagamentoDetalheResource` perde `desamarravel`, `estornavel`, `saldo`, `amarrado`, `livre`, e `cancelavel` = efetivado, sem venda/acerto, sem boleto. Textos: "desamarrado" vira "estornado"; o dialog do Cancelar diz que estorna as baixas e libera PIX/cartão.

**Divergências**: `GET v1/pagamento/divergencia?caso=&data_de=&data_ate=&codfilial=` (`PagamentoDivergenciaService`, acesso por `ConferenciaAutorizador`). Venda: `VendaConferenciaService::desbalanceadas($filiais, $de, $ate, $limite)` (com filtro, a conta venda a venda; sem filtro, somas agrupadas). Pagamento: soma de todas as linhas da tblmovimentotitulo (+ rubricas do acerto) contra o valor menos a devolução; sem baixa e sem venda; sem baixa em venda aberta/cancelada (com filtro, a soma pagamento a pagamento). Até 5.000 linhas (as mais recentes) com o total. Contas: botão Divergências em cima da lista de Pagamentos (Admin/Financeiro/Gerente), página `pagamento/divergencias` com a gaveta de filtros (caso, data, filial); a tela pede os cinco casos em paralelo. Venda abre a Venda com diferença; pagamento abre o detalhe.

**Painel**: `desbalanceadas` sem a exigência de ter pagamento (desde o go-live, como antes).

**Script**: `api/database/pagamento_historico.sql`.

## Testes (11/10/2026)
- **Cancelar** (tinker, transação desfeita): dinheiro 80248398 com 2 baixas (títulos reabrem com o principal); PIX 80246945 (baixa + créditos estornados, PIX 264568 sem uso); boleto 80246845 recusado; legado 80107037 (930 sem vínculo) cancela sem estornar de novo; Stone 634898: baixa, cancela e usa de novo na mesma linha.
- **Divergências** (HTTP): baixas 6, sem venda nem título 2, venda aberta 2, cancelada 0; histórico todo: vendas 8 s, baixas 2 s, sem 2 s, cancelada 3 s (em paralelo ≈ 8 s); com filtro de um mês, todos abaixo de 0,6 s. Caso inválido → 422. Painel da filial 101 ok (0,3 s).
- **Tela** (Chrome headless no dev server do contas): botão na lista de Pagamentos; lista carregada em 8 s ("As 5.000 mais recentes de 1.216.235"); filtro Pagamento que não bate com as baixas → 6 linhas; clique abre o detalhe; sem erro de console nem de API.
- **Script** (dev, com ROLLBACK, ~6 min): 1.216.225 vendas que não fecham; troco em dinheiro 1.158.671; repetido 26.043 vendas (26.052 cópias); troco do vale 2.107; sobram 29.404.
- **Lint**: contas completo e negocios (arquivo alterado) sem erro; prettier nos componentes compartilhados.
<!-- SECTION:NOTES:END -->

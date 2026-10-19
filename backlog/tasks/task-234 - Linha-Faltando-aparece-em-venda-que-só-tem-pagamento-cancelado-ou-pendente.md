---
id: TASK-234
title: Linha Faltando aparece em venda que só tem pagamento cancelado ou pendente
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-17 17:36'
updated_date: '2026-10-17 17:38'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 245000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato do Fábio (17/10/2026): venda cancelada com o cartão débito cancelado mostra a linha Faltando com o total inteiro, como se faltasse receber. A linha de saldo (Faltando/Troco) em TotalNegocio.vue aparece quando existe qualquer lançamento no negócio, contando pagamento cancelado, parcela inativa e cobrança PIX/maquineta pendente ou cancelada. Decisão: só pagamento efetivado (pagamento ativo ou parcela ativa) liga a linha de saldo. Sem nada efetivado não aparece nem a linha nem o botão Receber; o caixa recebe pelo F8 ou abrindo a cobrança pendente na lista. A lista de pagamentos e cobranças continua mostrando cancelados e pendentes.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Venda com pagamento cancelado ou cobrança pendente e nada efetivado não mostra a linha Faltando
- [x] #2 Botão Receber continua aparecendo só em venda sem nenhum lançamento; com cobrança pendente ou cancelada e nada efetivado não aparece nem botão nem linha, o F8 segue funcionando
- [x] #3 Lista de pagamentos e cobranças continua mostrando o cartão cancelado riscado e a cobrança pendente em Aguardando Pagamento
- [x] #4 Pagamento parcial efetivado e troco continuam mostrando a linha como hoje
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Causa: em negocios/src/components/offline/TotalNegocio.vue a linha de saldo (mostrarSaldo) usava temLancamento, que conta qualquer pagamento (inclusive estado C), qualquer parcela (inclusive inativa) e cobrança PIX/maquineta não concluída. Correção: novo computed temPagamentoEfetivado (pagamentosAtivos ou parcelasAtivas, o mesmo filtro do valorapagar) liga só a linha de saldo. Botão Receber e a lista de pagamentos/cobranças seguem com temLancamento. Teste: (1) venda cancelada com cartão cancelado não mostra Faltando, cartão fica riscado na lista; (2) venda aberta, gerar PIX e não pagar: sem botão e sem linha, PIX em Aguardando Pagamento, F8 abre o Receber; (3) venda aberta, pagar parte em dinheiro: linha Faltando com o resto; (4) dinheiro acima do total: linha Troco.
<!-- SECTION:NOTES:END -->

---
id: TASK-217
title: Venda cancelada por engano não tem como ser reaberta pelo gerente
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 21:21'
updated_date: '2026-10-10 21:21'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 229000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (10/10/2026): o botão Reabrir do PDV (TASK-30/TASK-209) só aparecia no negócio fechado; o cancelado também precisa reabrir.

Reabre igual ao fechado: status volta a aberto com reabertura, e o F3 reconcilia mantendo data, caixa e PDV originais. O cancelamento já estornou tudo, então o F3 gera de novo o que ficar:
- parcela a prazo: título novo (NegocioParcelaService::gerarTitulos já tratava título estornado);
- vale compras vendido: crédito novo (PdvNegocioValeService::reconciliarCreditos limpa o codtitulo do vale ativo com crédito estornado, e o emitirCreditos emite);
- pagamento manual volta como C: o gerente reativa no PDV (C -> P) ou lança de novo;
- PIX/Stone desamarrados no cancelamento seguem em Pagamentos não resolvidos para amarrar de novo.

O motivo do cancelamento (tblnegocio.justificativa) sai do negócio reaberto e fica na auditoria Negócio reaberto (antes: status 3 + justificativa) e na ocorrência do cancelamento.

Cancelado ainda aberto (nunca fechou) reabre do mesmo jeito: o F3 fecha com a data da última alteração antes do cancelamento.

Teste: gerente abre no PDV um negócio cancelado, Reabrir (cadeado) com motivo, reativa o pagamento, F3; conferir Histórico, título da parcela e crédito do vale.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Botão Reabrir aparece no negócio cancelado para Gerente/Financeiro/Admin
- [x] #2 Servidor reabre negócio cancelado e a auditoria grava o status anterior e o motivo do cancelamento
- [x] #3 F3 do cancelado reaberto emite de novo o crédito do vale compras vendido
<!-- AC:END -->

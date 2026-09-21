---
id: TASK-43
title: Adicionar forma de pagamento Cheque
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-09-19 19:32'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 107000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao IMPORTANTES
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Entra no redesenho do recebimento (TASK-100). Decisoes: forma 1020 tipo NFe 02; valor <= saldo (sem troco); CMC7 + bom para (pre-datado so em tblcheque.vencimento, sem titulo/limite) + emitente CPF/CNPJ e nome digitados; cliente obrigatorio (codpessoa != 1); DDL colunas cmc7/chequevencimento/chequecnpj/chequeemitente em tblnegocioformapagamento e codnegocioformapagamento em tblcheque; tblcheque criado no fechamento; cancelar negocio preenche cancelamento (recusa se repassado).

Implementado junto do TASK-100: receber/FormaCheque.vue (CMC7 validado offline por utils/cmc7.js, bom para, emitente), colunas cmc7/chequevencimento/chequecnpj/chequeemitente no pagamento, Mg\Pdv\PdvNegocioChequeService (valida no fechar, cria tblcheque com codnegocioformapagamento, cancela no cancelamento; recusa se repassado). Aguardando teste.
<!-- SECTION:NOTES:END -->

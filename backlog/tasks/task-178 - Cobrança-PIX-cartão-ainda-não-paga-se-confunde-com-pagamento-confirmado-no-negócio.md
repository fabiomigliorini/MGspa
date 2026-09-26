---
id: TASK-178
title: >-
  Cobrança PIX/cartão ainda não paga se confunde com pagamento confirmado no
  negócio
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-26 20:13'
updated_date: '2026-09-26 20:24'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 192000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No painel de totais do negócio, a cobrança PIX/cartão que o cliente ainda não pagou aparece na mesma lista dos pagamentos confirmados, e o caixa acha que já entrou. Separar: confirmados, Faltando, bloco 'Aguardando Pagamento' e bloco 'Canceladas'.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Cobranças em aberto aparecem sob o título 'Aguardando Pagamento', abaixo do Faltando
- [x] #2 Cobranças canceladas/expiradas aparecem sob o título 'Canceladas', em cinza
<!-- AC:END -->

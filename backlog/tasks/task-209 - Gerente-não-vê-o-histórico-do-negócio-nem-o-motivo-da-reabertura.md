---
id: TASK-209
title: Gerente não vê o histórico do negócio nem o motivo da reabertura
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-10 19:57'
updated_date: '2026-10-10 19:57'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 221000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio em 10/10/2026. Reabrir uma venda fechada (TASK-30) não pede motivo, e no PDV não há onde ver a auditoria e as ocorrências do negócio.

Proposta: o Reabrir pede justificativa (gravada na tblauditoria, coluna justificativa que já existe) e um componente genérico em @components (como o MgInfoCriacao) recebe tabela + código, busca no backend ao clicar e mostra num dialog a auditoria e as ocorrências do registro. Para tblnegocio, o backend traz também a auditoria dos filhos (itens, pagamentos, parcelas, vales). Visível para todos os usuários do PDV (decisão do Fábio).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Reabrir negócio pede justificativa e grava na auditoria Negócio reaberto
- [ ] #2 Componente genérico em @components recebe tabela + código e mostra num dialog a auditoria e as ocorrências do registro
- [ ] #3 No negócio, o histórico traz também a auditoria dos itens, pagamentos, parcelas e vales
- [ ] #4 Botão do histórico no PDV, visível para todos os usuários
<!-- AC:END -->

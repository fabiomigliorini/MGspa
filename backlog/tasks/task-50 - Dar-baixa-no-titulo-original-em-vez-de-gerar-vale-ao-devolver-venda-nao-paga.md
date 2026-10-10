---
id: TASK-50
title: Dar baixa no titulo original em vez de gerar vale ao devolver venda nao paga
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 19:14'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 128000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao DESEJAVEIS
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Devolver venda ainda não paga abate do título em aberto da própria venda, sem gerar crédito/vale
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): hoje a devolução sempre gera o crédito (PdvNegocioDevolucaoService.php:148-159); à mão dá para compensar com a duplicata pelo Receber/Pagar (forma Compensação, TASK-188), mas o Fábio quer automático.
<!-- SECTION:NOTES:END -->

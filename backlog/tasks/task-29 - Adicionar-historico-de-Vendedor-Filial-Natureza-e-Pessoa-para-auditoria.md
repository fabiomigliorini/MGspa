---
id: TASK-29
title: 'Adicionar historico de Vendedor, Filial, Natureza e Pessoa para auditoria'
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 02:49'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 117000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — topo, sem secao. "Para auditar quem alterou e quando."
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Troca de vendedor, filial, natureza e pessoa do negócio grava na tblauditoria (AuditoriaService::registrar, tabela tblnegocio, antes/depois só do campo que mudou), com quem e quando
<!-- AC:END -->

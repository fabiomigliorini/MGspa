---
id: TASK-8
title: Trocar o PDV de setor muda o setor das vendas antigas
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 17:21'
labels:
  - pessoas
dependencies: []
priority: medium
type: feature
ordinal: 11000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pessoas/todo, seção "Metas Novas" ("Campo Alocação do PDV"). Nas metas novas a alocação do PDV virou o setor do PDV (tblpdv.codsetor, já editável no diálogo do PDV). Mas a tblnegocio não guarda o setor: listagem e detalhe do negócio mostram o setor ATUAL do PDV, então trocar o PDV de setor muda o setor de todas as vendas antigas. Pedido do Fábio (10/10/2026, revisão do backlog): o negócio guarda o setor em que foi feito. Absorve a TASK-27 (setor na listagem) e a TASK-28 (setor na identificação do usuário).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O negócio grava o setor do PDV na venda (tblnegocio.codsetor), com carga dos negócios antigos pelo setor atual do PDV
- [ ] #2 Listagem e detalhe do negócio e indicadores do RH leem o setor gravado no negócio (ex-TASK-27)
- [ ] #3 A identificação do usuário no PDV mostra o setor (ex-TASK-28)
<!-- AC:END -->

---
id: TASK-126
title: >-
  Agro/Extrato: dois KPIs lem campos que a API nao devolve (A colher e
  Disponivel zerados)
status: To Do
assignee: []
created_date: '2026-09-21 21:32'
updated_date: '2026-09-23 21:04'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 137000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
ExtratoPage.vue le kpis.acolherkg e kpis.disponivelkg, mas GET v1/safra/{cod}/comercial (SafraService::resumoComercial) nao devolve nenhum dos dois: os campos existentes sao 'disponivel' (em SACAS, ja arredondado) e nao ha 'acolher'. Resultado: os cards 'A colher' e 'Disponivel p/ negociar' mostram sempre 0 sc e - kg. Os outros dois cards (estoquekg/entreguekg) estao certos. A SafraDetailPage consome o MESMO endpoint com os nomes corretos (estoquesc, entreguesc, disponivel), entao o desalinhamento e so da ExtratoPage. Decidir: (a) corrigir o front para 'disponivel' (sacas) e (b) criar 'acolherkg' no backend (producao estimada - colhido) ou remover o card.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:04
---
Dobrada na TASK-170 (regra 3 do CLAUDE.md): virou o critério de aceite #1 da task-mãe, e o texto desta descrição está preservado nas notas da TASK-170. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->

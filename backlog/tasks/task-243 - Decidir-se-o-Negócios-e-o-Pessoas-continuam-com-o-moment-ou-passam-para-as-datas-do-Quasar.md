---
id: TASK-243
title: >-
  Decidir se o Negócios e o Pessoas continuam com o moment ou passam para as
  datas do Quasar
status: To Do
assignee: []
created_date: '2026-10-18 14:09'
labels:
  - negocios
  - pessoas
dependencies: []
priority: low
type: spike
ordinal: 252000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Decidir se o Negócios e o Pessoas continuam usando o moment para datas ou passam para o padrão dos outros apps, sem moment: o date do Quasar e o @components/formatters.

Origem: pedido do Fábio em 18/10/2026, depois da TASK-238 (datas em inglês por causa do idioma do moment) e da TASK-242 (limpeza do moment no Negócios e no Pessoas).

Situação em 18/10/2026:
- Só o negocios e o pessoas têm moment (package.json ^2.29.4, instalado 2.30.1). O negocios tem 14 arquivos importando o moment e o pessoas 15, com ~110 chamadas. Os usos mais comuns: add (49), format (42), startOf (25), isValid (22), subtract (21), toDate (20), fromNow (13), diff (9).
- agro, contas, estoque e notas não usam moment: usam @components/formatters (JS puro, inclusive tempoRelativo/formataFromNow para o 'há 22 minutos') e o date do Quasar (agro 1 arquivo, contas 3, notas 1). O negocios já usa o formatters em 36 arquivos e o pessoas em 58.
- O date do Quasar cobre formatDate, addToDate, subtractFromDate, startOfDate, endOfDate, getDateDiff, isSameDate, isValid e extractDate; nomes de mês e dia vêm do idioma do Quasar (pt-BR em todos os apps, menos o notas, que está en-US). Não tem tempo relativo: esse fica no tempoRelativo do formatters.
- O moment está em modo de manutenção (o próprio projeto pede para não usar em código novo) e custa ~59 KB minificado; o idioma dele no Vite tem pegadinha (ver notas da TASK-238).

Se a decisão for migrar, a TASK-242 perde o sentido ou vira parte da migração.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Levantado o que o Negócios e o Pessoas usam do moment e o equivalente no date do Quasar ou no @components/formatters, uso por uso
- [ ] #2 Decisão registrada na task: continuar com o moment ou migrar
- [ ] #3 Se migrar: plano de troca por app registrado na task, dizendo o que acontece com a TASK-242
<!-- AC:END -->

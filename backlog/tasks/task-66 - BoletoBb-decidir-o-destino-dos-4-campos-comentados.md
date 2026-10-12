---
id: TASK-66
title: 'BoletoBb: decidir o destino dos 4 campos comentados'
status: Done
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-10-10 17:21'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 70000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — api/app/Mg/Titulo/BoletoBb/BoletoBbService.php:338. Campos: valorpagamentoparcial, valorabatimento, valorreajuste, valoroutro.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): já estava feito, fechada. valoroutro vira ajuste no título na liquidação (161 boletos tiveram). Dos 35.816 boletos da base, nenhum veio com pagamento parcial, abatimento ou reajuste diferente de zero. Removido o bloco comentado com o TODO.
<!-- SECTION:NOTES:END -->

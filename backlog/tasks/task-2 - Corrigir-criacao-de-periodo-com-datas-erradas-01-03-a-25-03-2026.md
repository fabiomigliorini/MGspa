---
id: TASK-2
title: Corrigir criacao de periodo com datas erradas (01/03 a 25/03/2026)
status: Done
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 14:46'
labels:
  - pessoas
dependencies: []
priority: high
type: bug
ordinal: 2000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->

Origem: pessoas/todo — "BUG: CRIOu periodo errado 01/03 a 25/03/2026"

<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->

Conferido 15/09/2026: causa encontrada, codigo NAO corrigido. Periodo 1 foi 01/02-25/02 (inicio irregular) e PeriodoService::criarProximoPeriodo faz addMonth() nas duas pontas -> 01/03-25/03. O dado foi corrigido na mao (periodo 4 hoje = 26/02-25/03) e os periodos seguintes sao regulares, entao nao reincide; mas o bug e latente. Correcao: novoInicial = periodofinal do anterior + 1 dia; usar addMonthNoOverflow. Criacao automatica dispara em ProcessarVendaService.php:283.

<!-- SECTION:NOTES:END -->

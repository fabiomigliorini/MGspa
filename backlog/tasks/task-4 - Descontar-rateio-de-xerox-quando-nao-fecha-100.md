---
id: TASK-4
title: Descontar rateio de xerox quando nao fecha 100%
status: To Do
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
Origem: pessoas/todo — "FIX: Quando rateio xerox nao fecha 100% sistema nao esta descontando"
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO corrigida. O rateio de xerox so existe na refatoracao de Metas abandonada (Mg/Meta/Services/BonificacaoService.php VENDA_XEROX paga total x percentual/100 sem normalizar; MetaService PREMIO_META_XEROX normaliza pela soma). Ultimo commit nesses arquivos e de 20/02/2026, anterior ao registro do bug (27/02). O RH novo (CalculoRubricaService) nao tem rateio de xerox. Decidir: morre junto com TASK-7 (aposentar Metas antigas) ou reescrever para o modulo RH.
<!-- SECTION:NOTES:END -->

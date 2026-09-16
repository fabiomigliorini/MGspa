---
id: TASK-57
title: Falha ao alterar observacoes de negocio offline (dizia que alterava valores)
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 15:11'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 10000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao ERROS QUE NAO ENCONTRAMOS MAIS. Nao reproduzido recentemente.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO verificavel. O commit 27287467 'nao estava salvando observacoes' (03/01/2026) e em NotaFiscalRequest, nao no PDV. A causa plausivel continua no codigo: PdvNegocioService::negocioFechado compara $negocio->valortotal != $data['valortotal'] (float) antes de aceitar qualquer alteracao, inclusive so de observacoes. Origem ja era a secao 'erros que nao encontramos mais'. Decidir: fechar como nao reproduzivel ou tratar a comparacao com tolerancia.
<!-- SECTION:NOTES:END -->

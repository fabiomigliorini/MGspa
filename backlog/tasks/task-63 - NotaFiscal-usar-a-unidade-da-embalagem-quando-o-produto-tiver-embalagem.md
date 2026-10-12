---
id: TASK-63
title: 'NotaFiscal: usar a unidade da embalagem quando o produto tiver embalagem'
status: Done
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-10-10 17:21'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 67000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — api/app/Mg/NotaFiscal/NotaFiscalProdutoBarraController.php:21: "// TODO: Fazer o backend entender que se tiver embalagem, precisa buscar a Unidade da Embalagem"
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): já estava feito, fechada. ProdutoBarra::UnidadeMedida() devolve a unidade da embalagem quando há embalagem; tela e XML usam ela. Removido o comentário TODO que sobrou em NotaFiscalProdutoBarraController.
<!-- SECTION:NOTES:END -->

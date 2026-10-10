---
id: TASK-65
title: 'Estoque: gravar o tamanho do lote de transferencia na ELPV'
status: To Do
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-10-10 19:06'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 69000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — api/app/Mg/Estoque/MinimoMaximo/VendaMensalService.php:480
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): arquivada, obsoleta. O TODO está dentro de VendaMensalService::calcularMinimoMaximo(), desligado de propósito desde 10/08/2023 (commit 09844ce46). O lote de transferência sai das embalagens (PedidoService, DistribuicaoService::definirLoteTransferencia); a coluna lotetransferencia está vazia nas 474 mil linhas.
<!-- SECTION:NOTES:END -->

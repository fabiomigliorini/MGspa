---
id: TASK-191
title: >-
  Pagamentos de maquininha guardados em três estruturas diferentes (PagarMe,
  Saurus, Lio)
status: Done
assignee: []
created_date: '2026-10-01 14:41'
updated_date: '2026-10-10 19:06'
labels:
  - api
dependencies: []
priority: medium
type: chore
ordinal: 207000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje cada integração de maquininha tem suas próprias tabelas e colunas (codpagarmepedido, codsauruspedido, codliopedido), com pedido/pagamento/status duplicados em Mg\PagarMe, Mg\Saurus e Mg\Lio. Unificar numa estrutura única de pedido/pagamento de adquirente, com o provedor como atributo, para que venda, conciliação e estorno tratem as três do mesmo jeito.

Relacionadas: TASK-115 (cancelar não estorna PIX/PagarMe/Saurus), TASK-118 (maquininha falha e PDV não fica sabendo), TASK-188 (pagamento guardado de três jeitos).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Estrutura de dados única de pedido/pagamento de adquirente, com o provedor como atributo (substitui codpagarmepedido/codsauruspedido/codliopedido)
- [x] #2 Código unificado: um núcleo comum (interface/contrato de provedor) que cada integração (PagarMe, Saurus, Lio) implementa só no que é específico dela, em vez de três módulos completos e separados
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): resolvida pela TASK-188, confirmado pelo Fábio: o aparelho é cadastro único (tblmaquineta: integracao, codpagarmepos, codsauruspinpad — M3, 78cd48526) e o pagamento é o fato único (tblpagamento com codmaquineta — M4, ed90fe2e8). As tabelas de pedido de cada provedor (tblpagarmepedido, tblsauruspedido, tblliopedido) ficam como tabelas da integração.
<!-- SECTION:NOTES:END -->

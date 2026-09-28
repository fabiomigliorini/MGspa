---
id: TASK-181
title: >-
  Pátio deixa lançar grão no contrato ou talhão errado (milho em contrato de
  soja)
status: To Do
assignee: []
created_date: '2026-09-28 21:11'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 194000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O pátio aceita contrato de outra cultura, talhão de outra safra, contrato de venda como origem, transferência de um silo para ele mesmo e tara maior que o PBT: o servidor só confere que os códigos existem (CargaSincronizarRequest, regras exists). Plano: specs/PLANO-AGRO-ARMAZENAMENTO.md (Fase 4), com o diagnóstico SQL no dev e na PROD antes de ligar as regras. Acréscimos de /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 5): unidade ou contrato inativo recusado como ponto novo; contrato de outra safra da mesma cultura decidido no diagnóstico.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Contrato de outra cultura é recusado, e o select só oferece contratos da cultura da safra
- [ ] #2 Talhão de outra safra é recusado como origem
- [ ] #3 Origem e destino seguem as combinações permitidas por sentido (D2)
- [ ] #4 Tara maior que o PBT é recusada com mensagem clara
<!-- AC:END -->

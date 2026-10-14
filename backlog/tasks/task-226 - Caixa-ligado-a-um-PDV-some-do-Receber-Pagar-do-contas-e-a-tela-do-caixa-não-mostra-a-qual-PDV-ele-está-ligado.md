---
id: TASK-226
title: >-
  Caixa ligado a um PDV some do Receber/Pagar do contas, e a tela do caixa não
  mostra a qual PDV ele está ligado
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-14 15:46'
updated_date: '2026-10-14 20:28'
labels:
  - contas
dependencies: []
priority: medium
type: bug
ordinal: 237000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Em 14/10/2026 o Caixa Financeiro (portador 100) não apareceu no Dinheiro do Receber/Pagar do contas. Em produção ele está vinculado a um PDV (tblpdv.codportador = 100), o que o marca como gaveta, e o contas esconde gaveta por uma trava que ninguém pediu ("no contas não se baixa título em gaveta"). A tela do portador só mostra o selo Gaveta, sem dizer a qual PDV ele está ligado nem onde trocar.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Dinheiro do contas (Receber/Pagar títulos, Vale/Adiantamento) lista também o portador ligado a PDV, e o servidor aceita
- [x] #2 Detalhe do portador mostra os PDVs vinculados, com link para o cadastro do PDV
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Trava retirada em 4 lugares: filtro !p.gaveta em contas/src/utils/cobranca.js e components/cobranca/portadores.js, abort(422) em PagamentoTituloService::portadorDaForma e a prop semGaveta (sem uso) do MgSelectPortador. PortadorResource passa a mandar pdvs (codpdv, apelido, filial, inativo); contas/src/pages/portador/Detalhe.vue mostra o card PDVs vinculados abaixo do cabeçalho, com link para NEGOCIOS_URL/dispositivo/:codpdv.

Teste: (1) contas > Receber ou Pagar títulos > Dinheiro: Caixa Financeiro e gavetas das filiais do usuário aparecem e gravam sem 422. (2) /portador/100: card mostra o PDV vinculado e o link abre o cadastro no negocios. Validado no dev via tinker (Resource da gaveta 202075 traz os 2 PDVs; portadorDaForma aceita gaveta sem PDV).
<!-- SECTION:NOTES:END -->

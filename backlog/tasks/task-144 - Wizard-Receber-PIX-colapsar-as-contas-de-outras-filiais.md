---
id: TASK-144
title: 'Wizard Receber PIX: colapsar as contas de outras filiais'
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-22 20:30'
updated_date: '2026-09-22 20:30'
labels:
  - negocios
dependencies: []
priority: medium
type: enhancement
ordinal: 154000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Operadores se confundem com a lista cheia de contas PIX na etapa QR Code do wizard Receber: hoje vem uma conta de cada filial da empresa (o select filtra por codempresa, nao por codfilial) e o operador corre o risco de cobrar na filial errada. Passa a mostrar so a conta Sicredi da empresa mae (0) e o Banco do Brasil da filial do negocio (1), com uma linha 'Selecione portador de outra filial' que abre a sub-etapa 'outras' com as demais (2,3,4...). Os dois primarios saem por codbanco (748 Sicredi / 1 BB), sem codportador fixo no codigo. Sub-etapa em vez de expandir in-place porque o ListaOpcoes guarda o indice: expandindo no lugar, o cursor de quem chega no pivo por seta+Enter cai na primeira 'outra' conta e um segundo Enter cobraria na conta errada. Irma da TASK-142 (mesmo padrao para cartao).
<!-- SECTION:DESCRIPTION:END -->

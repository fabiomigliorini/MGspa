---
id: TASK-100
title: 'Redesenhar recebimento do negocio: wizard por teclado (setas alteravam valor)'
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-16 14:58'
updated_date: '2026-09-16 15:58'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 99000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ao adicionar um pagamento num negocio (negocios/src/components/offline/Pagamento*.vue), o autofocus cai no campo valor (MgInputValor). O MgInputValor incrementa/decrementa com ArrowUp/ArrowDown (components/MgInputValor.vue ~L222-230). Como o campo tem estilo diferente e nao parece um q-input, o usuario nao percebe que o foco esta nele, usa as setas achando que navega entre campos/opcoes e altera o valor sem querer.

Origem: relato de uso (2026-09-16).

Caminhos possiveis (decidir antes de implementar):
- desligar o incremento por setas no MgInputValor por prop (ex.: nas telas de pagamento) ou por padrao;
- deixar claro visualmente que o campo esta focado/editavel;
- rever para onde vai o autofocus nos dialogs de pagamento.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Escopo ampliado (2026-09-16): redesenho da tela de pagamentos. Plano em /home/usuario/.claude/plans/agora-quero-pensar-em-calm-scott.md. Resumo: wizard Receber (F6-F9 abrem, passo 1 valor em texto + Insert edita, passo 2 forma por numero, passos por forma com setas/Enter/Esc), pagamento dividido reinicia wizard com saldo, dialogs especialistas PIX/PagarMe/Saurus mantidos + auto-consulta, cartao manual grava serialmaquineta (lista filtravel + cache), serial fisico no tblsauruspinpad, cheque (TASK-43).
<!-- SECTION:NOTES:END -->

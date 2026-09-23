---
id: TASK-166
title: >-
  Cancelamento nao trata cStat 573 (duplicidade de evento): nota fica AUT aqui e
  cancelada na SEFAZ
status: To Do
assignee: []
created_date: '2026-09-23 20:38'
labels:
  - api
dependencies: []
priority: medium
type: bug
ordinal: 175000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits (verificacao do achado sobre retry, revisor). PRE-EXISTENTE, nao introduzido pelas mudancas recentes, mas a TASK-148 ampliou a janela.

processarEventoCancelamento (NFePHPService.php ~:1000) so reconhece cStat 101, 135 e 155. Nao trata 573 ('Duplicidade de evento'). Quando uma tentativa de cancelamento chega na SEFAZ mas a resposta se perde (timeout do cURL, 504), o retry do chamarSefazComRetry reenvia e recebe 573: o evento JA foi registrado, mas o nosso lado nao reconhece, nao grava nfecancelamento e a nota fica AUT no banco enquanto esta CANCELADA na SEFAZ. Divergencia fiscal silenciosa.

Ja era alcancavel pelo codigo 28 (timeout) que sempre esteve na lista de erros transitorios; a TASK-148 acrescentou 502/503/504, ampliando a janela.

Fix: ao receber 573 no cancelamento, consultar a chave e vincular o evento de cancelamento que ja existe na SEFAZ (mesmo padrao da recuperacao por duplicidade do enviarSincrono, que trata 204). O inutilizar ja trata o equivalente (256/563, ~:847-857) — usar como referencia.

Conferir no banco se ha notas nessa situacao: AUT aqui, canceladas na SEFAZ.
<!-- SECTION:DESCRIPTION:END -->

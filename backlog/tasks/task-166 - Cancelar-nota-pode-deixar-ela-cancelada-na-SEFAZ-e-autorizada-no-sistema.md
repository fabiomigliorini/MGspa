---
id: TASK-166
title: Cancelar nota pode deixar ela cancelada na SEFAZ e autorizada no sistema
status: In Progress
assignee: []
created_date: '2026-09-23 20:38'
updated_date: '2026-11-16 19:55'
labels:
  - api
dependencies: []
priority: high
type: bug
ordinal: 11000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits (verificacao do achado sobre retry, revisor). PRE-EXISTENTE, nao introduzido pelas mudancas recentes.

processarEventoCancelamento (NFePHPService.php ~:1000) so reconhece cStat 101, 135 e 155. Nao trata 573 ('Duplicidade de evento'). Quando uma tentativa de cancelamento chega na SEFAZ mas a resposta se perde (timeout do cURL, 504), o retry do chamarSefazComRetry reenvia e recebe 573: o evento JA foi registrado, mas o nosso lado nao reconhece, nao grava nfecancelamento e a nota fica AUT no banco enquanto esta CANCELADA na SEFAZ. Divergencia fiscal silenciosa.

Ja era alcancavel pelo codigo 28 (timeout), que sempre esteve na lista de erros transitorios. A TASK-148 tinha ampliado a janela ao aceitar 502/503/504 no funil inteiro, mas a TASK-168 desfez isso: ehErroTransitorioSefaz so aceita 5xx quando a operacao e enviaLote (NFePHPService.php:235), entao os eventos de cancelamento voltaram a reenviar apenas em erro de transporte. A janela voltou ao tamanho de sempre e o bug continua valendo por si.

Fix: ao receber 573 no cancelamento, consultar a chave e vincular o evento de cancelamento que ja existe na SEFAZ (mesmo padrao da recuperacao por duplicidade do enviarSincrono, que trata 204). O inutilizar ja trata o equivalente (256/563, ~:847-857) — usar como referencia.

Conferir no banco se ha notas nessa situacao: AUT aqui, canceladas na SEFAZ.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Cancelar uma NF-e ou NFC-e que a SEFAZ aceitou deixa a nota Cancelada no sistema, inclusive quando a resposta se perde e a nova tentativa volta 'duplicidade de evento' (573)
- [ ] #2 A causa do caso relatado em 16/11/2026 está identificada no log do cancelamento; se não for a 573, vira critério próprio
- [ ] #3 Levantadas as notas Autorizadas aqui e canceladas na SEFAZ, e as devoluções emitidas em cima delas
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Relato de 16/11/2026: o usuário cancela a NF-e ou NFC-e, a SEFAZ cancela, mas o sistema continua 'Autorizada'. Ele acha que não cancelou e pede a devolução, que gera nota de devolução de nota já cancelada na SEFAZ. Com a nota Cancelada no sistema, a devolução do notas já recusa (NotaFiscalDevolucaoService::gerarDevolucao só aceita Autorizada ou Lançada). Contorno a confirmar: o Consultar da nota (NFePHPService::consultar) lê o evento de cancelamento (tpEvento 110111) e deve corrigir a situação. Prioridade subiu de Medium para High.
<!-- SECTION:NOTES:END -->

---
id: TASK-150
title: >-
  Corrida na geracao do PDF do DANFE: front, MGprint e MailJob regravam o mesmo
  {chave}.pdf sem lock
status: Done
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 16:38'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 159000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (U15, 1/1 verificador). NFePHPService::danfe()/quebraPdfDanfePaginas() (~:1169) escrevem {chave}.pdf e um .tmp.pdf fixo; apos autorizar, o GET /danfe do front (abrirDanfe) e o GET /danfe do MGprint apos o POST /imprimir (~:1267-1269) — e o NFePHPMailJob quando a pessoa tem e-mail com nfe=true — podem escrever ao mesmo tempo; leitor (FPDI, response()->file(), MGprint) pode ver arquivo truncado e dar 500 / PDF corrompido. Front mostra Notify vermelho do abrirPdf.js (~:54-59) com o Emitir concluindo normal. Fix: temporario unico por processo ("{$pathDanfe}." . getmypid() . ".tmp") + rename() atomico; NotaFiscalController::danfe reaproveitar o PDF existente.
<!-- SECTION:DESCRIPTION:END -->

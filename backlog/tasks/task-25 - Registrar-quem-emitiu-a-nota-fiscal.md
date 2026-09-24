---
id: TASK-25
title: Registrar quem emitiu a nota fiscal
status: Done
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-24 12:24'
labels:
  - notas
dependencies: []
priority: medium
type: feature
ordinal: 181000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: notas/todo.md — bloco 30/03 (estava sob DONE, mas conferido no codigo: nao ha campo de usuario de emissao em Mg/NotaFiscal nem Mg/NFePHP).
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO ha campo de usuario emissor em tblnotafiscal (so codusuariocriacao/alteracao). Porem desde 07/08/2026 (50f1b189) a tblsefazcomunicacao grava codusuariocriacao por operacao com codnotafiscal (SefazLogService::iniciar), entao quem transmitiu ja fica registrado. O endpoint nota-fiscal/{id}/sefaz (NotaFiscalController@sefazComunicacoes) NAO devolve o usuario e a tela nao mostra. Sugestao de escopo: expor o usuario no log SEFAZ da NotaFiscalViewPage.
<!-- SECTION:NOTES:END -->

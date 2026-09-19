---
id: TASK-68
title: 'RH: implementar a integracao de recarga do cartao Bee'
status: To Do
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-09-15 15:11'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 11000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — api/app/Mg/Rh/RecargaApiBee.php:8 e :22. Hoje o metodo lanca RuntimeException. Pendencias declaradas no docblock: credenciais Bee (usuario + token, em config/env e nao no codigo), cadastro de beneficiario por CPF, geracao da recarga em lote a partir de BeeRecargaService::linhasDoLote() e retorno do protocolo. BLOQUEADO POR TERCEIRO: a Bee ainda nao disponibilizou a API de recarga. Nao ha o que
implementar hoje — nao e so falta de credencial. Reavaliar a prioridade (hoje Low) quando
a Bee publicar a API.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: NAO feita. RecargaApiBee::gerarRecarga ainda lanca RuntimeException (linha 26). Bloqueio externo (API da Bee) permanece.
<!-- SECTION:NOTES:END -->

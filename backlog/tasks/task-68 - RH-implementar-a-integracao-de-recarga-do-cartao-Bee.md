---
id: TASK-68
title: 'RH: implementar a integracao de recarga do cartao Bee'
status: To Do
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-09-12 16:15'
labels:
  - api
dependencies: []
type: feature
ordinal: 68000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — api/app/Mg/Rh/RecargaApiBee.php:8 e :22. Hoje o metodo lanca RuntimeException. Pendencias declaradas no docblock: credenciais Bee (usuario + token, em config/env e nao no codigo), cadastro de beneficiario por CPF, geracao da recarga em lote a partir de BeeRecargaService::linhasDoLote() e retorno do protocolo. BLOQUEADO ate termos as credenciais.
<!-- SECTION:DESCRIPTION:END -->

---
id: TASK-247
title: Observação do título não deixa escrever mais que 255 letras
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-21 13:41'
updated_date: '2026-10-21 16:01'
labels:
  - contas
dependencies: []
priority: low
type: enhancement
ordinal: 256000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A observação do título (contas, Editar/Novo título) trava em 255 caracteres e não cabe uma lista de materiais como a do título 645726, que ficou com exatamente 255. Em 21/10/2026, 108 títulos estavam com 250 ou mais. O Fábio decidiu subir para 2000 caracteres.

O limite está em três camadas: a coluna tbltitulo.observacao é varchar(255); a API valida max:255 em TituloStoreRequest, TituloUpdateRequest e TituloAdiantamentoStoreRequest; o campo da tela tem maxlength=255 em contas/src/pages/titulo/Novo.vue e Detalhe.vue.

Aumentar varchar no Postgres é só metadado (sem reescrever a tabela) e nenhuma view depende da coluna. O DDL fica em api/database/titulo_observacao.sql e roda no go-live.

Origem: conversa de 21/10/2026.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Coluna tbltitulo.observacao aceita 2000 caracteres (api/database/titulo_observacao.sql, roda no go-live)
- [x] #2 API aceita observação de até 2000 caracteres no cadastro, na edição e no adiantamento
- [x] #3 Campo Observação do Novo título e do Editar título deixa digitar até 2000 caracteres
<!-- AC:END -->

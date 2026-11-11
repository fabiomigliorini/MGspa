---
id: TASK-69
title: 'Seguranca: rota publica de detalhes do Pix e enumeravel por PK sequencial'
status: In Progress
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-11-11 18:34'
labels:
  - api
dependencies: []
priority: critical
type: bug
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — api/routes/api.php:765: "// TODO segurança: codpixcob é PK sequencial → enumerável. Migrar p/ auth_or_signed (signed URL) como a rota pix.cob.pdf logo abaixo, antes de considerar definitivo." A rota pix/cob/{codpixcob}/detalhes roda com withoutMiddleware('auth:api').
<!-- SECTION:DESCRIPTION:END -->

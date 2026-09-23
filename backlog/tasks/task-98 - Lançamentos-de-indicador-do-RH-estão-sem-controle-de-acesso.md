---
id: TASK-98
title: Lançamentos de indicador do RH estão sem controle de acesso
status: To Do
assignee: []
created_date: '2026-09-15 15:11'
updated_date: '2026-09-23 21:06'
labels:
  - api
dependencies: []
priority: medium
type: bug
ordinal: 97000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto ao conferir a TASK-3 (15/09/2026). E o unico metodo dos controllers de api/app/Mg/Rh sem Autorizador::autoriza(['Recursos Humanos']) — qualquer usuario autenticado consegue ler os lancamentos de qualquer indicador pelo id. Os demais metodos do IndicadorController exigem o grupo. Medium: exige login e e so leitura, mas expoe numeros de venda/meta fora do RH.
<!-- SECTION:DESCRIPTION:END -->

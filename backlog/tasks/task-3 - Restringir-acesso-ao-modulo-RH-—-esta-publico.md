---
id: TASK-3
title: Restringir acesso ao modulo RH — esta publico
status: Done
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-15 15:11'
labels:
  - pessoas
dependencies: []
priority: critical
type: bug
ordinal: 2000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pessoas/todo — "BUG: RH Está Publico". Exposicao de dados: o modulo RH esta acessivel sem a restricao de permissao esperada.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conferido 15/09/2026: protegido. Backend: grupo rh dentro de auth:api e todos os controllers com Autorizador::autoriza(['Recursos Humanos']) (excecao deliberada: MeuPainelController, que filtra pelo proprio colaborador via Auth::user()->codpessoa). Front: menu RH escondido por user.temPermissao('Recursos Humanos') desde 18/05/2026 (6b019880) e paginas com podeEditar. Residuo encontrado: IndicadorController@lancamentos sem Autorizador — virou task propria.
<!-- SECTION:NOTES:END -->

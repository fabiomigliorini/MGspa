---
id: TASK-99
title: 'Validar campos de permissao da tela de usuario (/usuarios/:codusuario)'
status: To Do
assignee: []
created_date: '2026-09-16 12:16'
labels:
  - pessoas
dependencies: []
priority: medium
type: bug
ordinal: 98000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Revisar campo a campo as telas pessoas/src/pages/usuarios/usuariosview.vue e usuarioseditar.vue (rotas /usuarios/:codusuario e /usuarios/:codusuario/editar) no que diz respeito a permissoes: grupos por filial (tblgrupousuariousuario), filial padrao, acesso a Caixa e demais flags. Hoje os campos estao bagunçados — conferir o que cada um grava, se o que a tela mostra bate com o que o backend le (Autorizador / Mg\Usuario\UsuarioController grupos, gruposAdicionarERemover em api.php:552-560) e se nao ha campo sem efeito ou efeito sem campo. Lembrar que a fonte unica de autorizacao e tblgrupousuariousuario; o RBAC Yii legado nao conta. Validacao feita por @fabio na tela; registrar aqui os campos com problema antes de corrigir.
<!-- SECTION:DESCRIPTION:END -->

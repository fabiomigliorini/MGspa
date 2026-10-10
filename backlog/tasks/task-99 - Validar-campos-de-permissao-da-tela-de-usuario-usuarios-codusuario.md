---
id: TASK-99
title: Refatorar permissões e excluir campos obsoletos do cadastro de usuário
status: To Do
assignee: []
created_date: '2026-09-16 12:16'
updated_date: '2026-10-10 19:19'
labels:
  - pessoas
dependencies: []
priority: medium
type: bug
ordinal: 98000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O cadastro de usuário do pessoas (/usuarios/:codusuario e /usuarios/:codusuario/editar) acumulou campos de permissão de épocas diferentes: uns gravam coisas que o MGspa não lê mais, outros não mostram o que de fato vale. Hoje a autorização vem de dois lugares: grupos por filial (tblgrupousuariousuario, lido pelo Autorizador) e, para o dinheiro, o papel do usuário em cada portador (tblportadorusuario: depositante, operador, gestor). O RBAC Yii legado não conta.

Refatorar a tela: tirar os campos obsoletos, conferir que cada campo que fica grava o que o backend lê (UsuarioController grupos, gruposAdicionarERemover) e mostrar no usuário as permissões que ele tem em outras entidades, começando pelos portadores.

Campo obsoleto já identificado: Portador (tblusuario.codportador, FormUsuario.vue:65 e perfil.vue:138). Nada no MGspa lê; só o MGsis legado (LiquidacaoTituloController, TituloAgrupamentoController, NegocioFormaPagamento). A permissão do dinheiro passou para o papel no portador (commit 44d585388). No dev, 34 de 86 usuários ativos têm o campo preenchido.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Campos obsoletos saem do cadastro de usuário, começando pelo Portador (tblusuario.codportador)
- [ ] #2 Cada campo de permissão que fica na tela grava o que o backend lê (grupos por filial em tblgrupousuariousuario)
- [ ] #3 A tela do usuário mostra as permissões dele em outras entidades, como os portadores em que tem papel (depositante, operador, gestor)
<!-- AC:END -->

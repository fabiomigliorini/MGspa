---
id: TASK-276
title: >-
  Caixa que troca de loja depende do suporte para poder mexer nos caixas da loja
  nova
status: To Do
assignee: []
created_date: '2026-11-16 19:55'
labels:
  - contas
dependencies: []
priority: high
type: feature
ordinal: 289000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido de 16/11/2026: os caixas trocam de filial com frequência e cada troca vira chamado de suporte. Precisa de um jeito simples de fazer essa troca.

A permissão no dinheiro é a lista de usuários de cada portador (tblportadorusuario: depositante, operador e gestor; PortadorAutorizador), mantida à mão no cadeado de cada portador (TASK-222). O PDV também depende dela: o caixa precisa do papel na gaveta. Quem muda de loja tem que entrar em cada portador da loja nova e sair dos da antiga, um por um.

A permissão por grupo e filial existiu e saiu na TASK-39. Relacionada: TASK-99 (mostrar, no cadastro do usuário, os papéis dele nos portadores).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Decidido o jeito simples de trocar o caixa de loja: quem faz e em que tela
- [ ] #2 Trocar um caixa de loja é uma ação só, sem editar a lista portador por portador
<!-- AC:END -->

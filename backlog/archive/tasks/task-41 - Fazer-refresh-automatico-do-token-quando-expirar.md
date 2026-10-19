---
id: TASK-41
title: Fazer refresh automatico do token quando expirar
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-19 21:47'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 61000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao IMPORTANTES (prefixo FIXME)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O token renova sozinho antes de vencer, sem o caixa ver o login no meio da venda
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): hoje só renova pelo botão do menu do usuário (renovarToken em negocios/src/stores/auth.js); no 401 o axios limpa o token e abre o login.

Revisão com o Fábio (19/10/2026) — arquivada, não é bug: é o comportamento desejado.

Origem: FIXME do negocios/todo de jan/2024 ("Quando expira o token ele precisa dar o refresh automaticamente"). Em 16/08/2025 (f44729458) foi corrigido o bug real ("login expira e continua mostrando como se estivesse logado"): no 401 o interceptor de negocios/src/boot/axios.js limpa o token e abre o login. O FIXME sobrou e virou task na importação do todo (12/09/2026) sem ser repensado.

Por que fica como está: o token vence à meia-noite de propósito — api/app/Providers/AuthServiceProvider.php, Passport::tokensExpireIn(now()->endOfDay()), herdado do MGAuth. Refresh automático deixaria o PDV logado para sempre: quem logou ontem continuaria vendedor hoje, e o negócio sai no nome de quem está logado. O login no meio da venda só aparece se alguém vender depois da meia-noite ou deixar o PDV aberto de um dia para o outro — e aí pedir login de manhã é o certo.

Fato de passagem: o Renovar do menu do usuário (renovarToken em negocios/src/stores/auth.js) não renova nada quando o login veio pelo cookie SSO compartilhado (não há refresh_token); só revalida o usuário via /userinfo.
<!-- SECTION:NOTES:END -->

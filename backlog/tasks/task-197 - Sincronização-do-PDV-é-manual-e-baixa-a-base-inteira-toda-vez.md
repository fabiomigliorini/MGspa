---
id: TASK-197
title: Sincronização do PDV é manual e baixa a base inteira toda vez
status: To Do
assignee: []
created_date: '2026-10-05 15:40'
updated_date: '2026-10-05 15:56'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 210000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje o PDV só sincroniza quando alguém clica no botão, e cada sincronização baixa de novo todos os produtos, pessoas e configurações. Cadastro recém-feito (cliente, produto, preço) não chega ao PDV até a próxima sincronização manual.

Origem: hotfix do quiosque (TASK-154, 05/10/2026). Na migração para a api/, o grupo v1/pdv/* ficou dentro do auth:api e o quiosque sem usuário logado parou de consultar preço. O fix simples tirou as rotas de leitura do auth:api; o controller autoriza só pelo uuid que vem na query string (PdvService::autoriza). Funciona, mas o uuid não é credencial: qualquer um que conheça o uuid de um PDV autorizado lê o catálogo e o cadastro de clientes.

Ideia do Fábio para a sincronização:
- Autenticação própria do PDV, para os casos sem usuário logado (quiosque, sincronização): o dispositivo autorizado recebe um token/credencial em vez de mandar só o uuid. Consolidar as rotas do PDV que hoje usam withoutMiddleware('auth:api').
- Sincronizar sozinho ao iniciar o sistema, sem depender do botão.
- Incremental: trazer só o que mudou desde a última sincronização. Pensado: arquivo JSON cacheado no backend e só o diff de cada alteração para o front.
- Full semanal como rede de segurança (pega exclusões e qualquer divergência do incremental).

Consolida TASK-36 (sincronizar pessoas novas) e TASK-54 (forçar sincronização ao entrar se a última for antiga).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 PDV autorizado se autentica com credencial própria (não só o uuid na query string) nas rotas usadas sem usuário logado: quiosque e sincronização
- [ ] #2 Sincronização roda sozinha ao iniciar o sistema, sem clicar no botão (ex-TASK-54)
- [ ] #3 Sincronização incremental traz só o que mudou desde a última, inclusive cadastro recém-feito de pessoa (ex-TASK-36), produto e preço
- [ ] #4 Full semanal recarrega a base inteira e corrige o que o incremental não pegou (exclusões, divergências)
- [ ] #5 Sincronização não fica esperando a localização: hoje o Chrome no Linux às vezes não devolve a posição (mesmo com permissão) e a sincronização recusa depois de 10 s; localização continua obrigatória
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Pendência do hotfix da TASK-154 (05/10/2026): dispositivo() pede navigator.geolocation com timeout 10 s e maximumAge 10 min; sem posição, recusa a sincronização. No Chrome de dev no Linux a posição às vezes não vem e a sincronização recusa. Avaliar maximumAge Infinity (última posição conhecida) ou registrar a localização separado da sincronização.
<!-- SECTION:NOTES:END -->

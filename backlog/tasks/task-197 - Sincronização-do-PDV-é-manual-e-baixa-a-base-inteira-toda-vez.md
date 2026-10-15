---
id: TASK-197
title: Sincronização do PDV é manual e baixa a base inteira toda vez
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-05 15:40'
updated_date: '2026-10-14 20:24'
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
- [ ] #6 Quiosque sem resposta do backend (offline, rede caída, PDV não autorizado) não mostra o saldo do cache como estoque atual: avisa que é estoque não confirmado e de quando é; sem cache, mostra estoque indisponível (não zero) (ex-TASK-154)
- [x] #7 Sincronizar funciona sem usuário logado: o botão não fica desabilitado nem avisa de login ou de sessão expirada
- [x] #8 Ao abrir o sistema com a última sincronização há mais de 2 horas (ou nunca feita), a janela Sincronizar abre e já sincroniza sozinha
- [x] #9 Duas janelas do mesmo navegador nunca sincronizam ao mesmo tempo: a segunda recusa avisando que outra já está sincronizando
- [x] #10 Ao abrir sem usuário logado (ou com a sessão vencida), o PDV não abre negócio sozinho e o login não aparece; F2/Novo continuam abrindo, inclusive sem login e sem rede
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Pendência do hotfix da TASK-154 (05/10/2026): dispositivo() pede navigator.geolocation com timeout 10 s e maximumAge 10 min; sem posição, recusa a sincronização. No Chrome de dev no Linux a posição às vezes não vem e a sincronização recusa. Avaliar maximumAge Infinity (última posição conhecida) ou registrar a localização separado da sincronização.

Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): convivência com a TASK-46 #7 (sem login o botão Sincronizar fica desabilitado; sessão expirada só avisa): o botão manual continua pedindo usuário logado; a sincronização automática (#1/#2) usa a credencial própria do PDV, sem usuário.

Revisão com o Fábio (14/10/2026): a decisão de 10/10 (o botão manual continua pedindo usuário logado; TASK-46 #7) deixou de valer — sincronizar não pede login. A janela que abre ao iniciar já sincroniza (não espera o clique). Trava entre janelas pelo Web Locks do navegador (navigator.locks), que solta sozinha se a janela fechar. Sem negócio aberto, a tela do PDV mostra 'Aperte F2 ou clique em Novo para começar uma venda'.
<!-- SECTION:NOTES:END -->

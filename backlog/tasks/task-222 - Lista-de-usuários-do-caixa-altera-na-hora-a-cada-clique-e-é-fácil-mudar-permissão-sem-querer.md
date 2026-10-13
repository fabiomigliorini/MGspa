---
id: TASK-222
title: >-
  Lista de usuários do caixa altera na hora a cada clique e é fácil mudar
  permissão sem querer
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-12 15:40'
updated_date: '2026-10-12 15:54'
labels:
  - contas
dependencies: []
priority: low
type: enhancement
ordinal: 233000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na janela de usuários do portador (cadeado no detalhe do portador, contas), cada inclusão, troca de papel ou exclusão ia direto para o banco. Um clique sem querer no papel de alguém já mudava a permissão no dinheiro. Pedido do Fábio em 12/10/2026: a lista vira um formulário — mexe só na tela e só efetiva no Salvar; o Cancelar descarta.

Técnico: PortadorUsuariosDialog.vue edita uma cópia local da lista; periodoStore.salvarUsuarios faz PUT v1/portador/{cod}/usuario com a lista inteira; PortadorUsuarioController@update sincroniza numa transação (exclui quem saiu, updateOrCreate dos demais). Saíram o POST e o DELETE por usuário.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Incluir, trocar o papel e tirar da lista só mexem na janela; nada vai para o banco antes do Salvar
- [x] #2 Salvar manda a lista inteira e o servidor inclui, altera e exclui tudo de uma vez
- [x] #3 Cancelar (ou fechar a janela) descarta o que foi mexido
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
12/10/2026 — PUT testado no tinker na base de dev (Caixa Botanico, transação desfeita): tirar a adriana, trocar a daci para Operador e incluir o fabio como Gestor de uma vez deu certo; lista vazia tira todo mundo; usuário repetido na lista é recusado (distinct). Na tela: o + põe o novo no topo da lista (à vista numa lista longa); o X tira sem pedir confirmação, porque agora só vale no Salvar.

12/10/2026 — Testado no navegador (Chromium, contas-dev :8086, Caixa Botanico, como fabio): tirar a adriana, trocar a daci para Operador e incluir o fabio como Gestor só mudou a tela (fabio no topo) e nada foi para o banco; Cancelar não gravou e reabrir mostrou a lista original; repetir e Salvar gravou tudo de uma vez (aviso Usuários salvos) e reabrir mostrou o salvo, com os gestores no topo. Base de dev restaurada depois do teste.
<!-- SECTION:NOTES:END -->

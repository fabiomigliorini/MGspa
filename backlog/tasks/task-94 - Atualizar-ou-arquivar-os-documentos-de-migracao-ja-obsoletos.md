---
id: TASK-94
title: Atualizar ou arquivar os documentos de migracao ja obsoletos
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-09-15 15:05'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 94000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto ao importar o backlog: api/RESUMO.md e "blueprint-rh-indicadores .md" listam pendencias que ja foram resolvidas, e induzem a erro quem os le hoje.
- RESUMO.md: os 6 observers ja estao registrados no AppServiceProvider (linhas 54-73); os 11 pacotes composer listados como faltando estao todos no composer.json; comandaVendedor nao retorna mais 501; PessoaResource->aberto usa PdvNegocioPrazoService::emAberto; PessoaService::importar ja usa SEFAZ + ReceitaWS + NFePHPService; PermissaoController::index esta implementado.
- blueprint-rh-indicadores: Fase 3 completa (4/4 services), Fase 4 com 5/6 controllers (o PeriodoColaboradorSetorController foi eliminado pela refatoracao do setor), Fase 5 e 6 entregues.
- specs/SPEC-ACERTOS.md ja foi implementado por inteiro.
- estoque/docs/BLUEPRINT_MIGRACAO_MGLARA.md (incluído na revisão de 10/10/2026): originou as TASK-74 a 83 com premissas erradas — ProdutoController@cobreEstoqueNegativo morto no MGLara desde 2016 (TASK-78), zerar saldo que já existia na conferência (TASK-77), estoque-mes tratado como fechamento descontinuável (TASK-83).
<!-- SECTION:DESCRIPTION:END -->

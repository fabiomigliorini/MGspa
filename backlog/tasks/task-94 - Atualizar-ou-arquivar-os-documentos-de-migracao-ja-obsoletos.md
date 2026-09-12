---
id: TASK-94
title: Atualizar ou arquivar os documentos de migracao ja obsoletos
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
labels:
  - feature
  - api
dependencies: []
ordinal: 94000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto ao importar o backlog: api/RESUMO.md e "blueprint-rh-indicadores .md" listam pendencias que ja foram resolvidas, e induzem a erro quem os le hoje.
- RESUMO.md: os 6 observers ja estao registrados no AppServiceProvider (linhas 54-73); os 11 pacotes composer listados como faltando estao todos no composer.json; comandaVendedor nao retorna mais 501; PessoaResource->aberto usa PdvNegocioPrazoService::emAberto; PessoaService::importar ja usa SEFAZ + ReceitaWS + NFePHPService; PermissaoController::index esta implementado.
- blueprint-rh-indicadores: Fase 3 completa (4/4 services), Fase 4 com 5/6 controllers (o PeriodoColaboradorSetorController foi eliminado pela refatoracao do setor), Fase 5 e 6 entregues.
- specs/SPEC-ACERTOS.md ja foi implementado por inteiro.
<!-- SECTION:DESCRIPTION:END -->

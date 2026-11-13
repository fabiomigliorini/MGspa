---
id: TASK-263
title: >-
  Colaborador vê a comissão desatualizada no Meu Painel e não tem como
  recalcular
status: Done
assignee:
  - '@eduardo'
created_date: '2026-11-03 21:29'
updated_date: '2026-11-03 22:01'
labels:
  - pessoas
dependencies: []
priority: medium
type: feature
ordinal: 271000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Reclamações recorrentes: a comissão no Meu Painel fica desatualizada. Causa: a venda (ProcessarVendaJob -> ProcessarVendaService::processar) atualiza o indicador, mas não recalcula as rubricas; o valor só muda quando o RH clica em Recalcular na tela do colaborador. O Meu Painel não tem o botão.

Decisão (03/11/2026): só o botão, fazendo a mesma coisa que já existe — sem rota nova nem recálculo automático. Vale para todo colaborador: a rota existente (POST v1/rh/periodo/{codperiodo}/colaborador/{codpc}/recalcular) passa a aceitar também o próprio colaborador, só ele mesmo e só enquanto aberto.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Meu Painel tem o botão Recalcular (mesmo do RH), visível só com o colaborador aberto
- [x] #2 Colaborador sem grupo RH recalcula o próprio painel pela mesma rota; recalcular outra pessoa sem ser RH dá 403
- [x] #3 Rota recusa colaborador encerrado (422)
- [x] #4 Recalcular da tela do RH continua igual
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
IMPLEMENTADO 03/11/2026 — aguardando validação (nada commitado).

API: PeriodoColaboradorController::recalcular (mesma rota) — busca o PeriodoColaborador pelo período; quem não é do grupo Recursos Humanos só passa se MeuPainelService::resolverColaborador() for o próprio (senão 403); colaborador encerrado dá 422 para todos (o botão do RH já sumia nesse caso). Cálculo igual (CalculoRubricaService::calcularColaborador).

FRONT: MeuPainelDashboard.vue — botão refresh 'Recalcular' no cabeçalho (só com status A), chama sRh.recalcular (ação existente), toast 'Recalculado' e recarrega o painel.

CONFERIDO no dev (transação com rollback, período 8): evilin (sem RH) recalcula o próprio = 200; evilin recalcula a jucielma = 403; eduardo (RH) recalcula a jucielma = 200; evilin encerrada = 422 (para ela e para o RH).

CAUSA DE FUNDO (fora do escopo, decisão do usuário): a venda atualiza o indicador mas não recalcula as rubricas; recalcular sozinho a cada venda seria automação nova.
<!-- SECTION:NOTES:END -->

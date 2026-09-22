---
id: TASK-141
title: >-
  Agro/Patio: refino do romaneio (sentido editavel, sync num botao so, totais em
  sacas)
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 13:51'
updated_date: '2026-09-22 13:51'
labels:
  - agro
dependencies: []
priority: medium
type: enhancement
ordinal: 151000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Rodada de refino do Patio de Cargas feita em 22/09/2026, a partir da revisao geral do /agro:

1. Tipo de romaneio (sentido) editavel ate FINALIZAR — antes travava na 1a pesagem. Como trocar reposiciona a carga na 1a etapa do novo fluxo, peso/classificacao/NF ja preenchidos passam a continuar visiveis mesmo que o novo fluxo ainda nao tenha chegado na etapa deles (senao ficariam escondidos e ineditaveis, ainda influenciando o liquido). Helper cargaPesada removido: existia so pra essa trava.

2. O botao de sincronizar do resumo (direita) passa a fazer as DUAS coisas num clique: destrava a carga (limpa syncerro, que o ciclo normal pula de proposito) e roda o ciclo completo forcado, igual ao da nuvem. Dicas dos dois botoes diferenciadas.

3. Banner de finalizadas mostra o desconto em sacas no lugar do percentual (totaisFinalizadas.pct deu lugar a descontoSacas, mesma divisao por pesosaca do liquido).
<!-- SECTION:DESCRIPTION:END -->

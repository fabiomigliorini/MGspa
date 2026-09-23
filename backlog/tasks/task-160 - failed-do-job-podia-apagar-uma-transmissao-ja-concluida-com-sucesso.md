---
id: TASK-160
title: failed() do job podia apagar uma transmissao ja concluida com sucesso
status: Done
assignee: []
created_date: '2026-09-23 20:33'
updated_date: '2026-09-23 20:33'
labels:
  - api
dependencies: []
priority: medium
type: bug
ordinal: 169000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits (achado A2 do revisor). Corrigido no mesmo dia.

O failed() adicionado na TASK-147 chamava registrarFalha() sem olhar o estado atual do progresso. Como o retry_after devolve o job e, com $tries = 1, um segundo worker o marca como failed antes do handle(), esse failed pode chegar DEPOIS de a transmissao original ter concluido — apagando um 'concluido' com sucesso e trocando por 'erro'. O operador veria erro e nao sairia cupom, com a nota autorizada na SEFAZ.

Corrigido: registrarFalha() nao sobrescreve estado terminal ('concluido'/'erro'), so 'processando' ou ausencia de progresso. Testado no tinker nos tres casos.
<!-- SECTION:DESCRIPTION:END -->

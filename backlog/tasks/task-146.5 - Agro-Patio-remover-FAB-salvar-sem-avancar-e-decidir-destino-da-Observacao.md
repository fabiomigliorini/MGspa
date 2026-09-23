---
id: TASK-146.5
title: 'Agro/Patio: remover FAB ''salvar sem avancar'' e decidir destino da Observacao'
status: To Do
assignee: []
created_date: '2026-09-23 19:56'
labels:
  - agro
dependencies:
  - TASK-146.1
  - TASK-146.2
  - TASK-146.3
  - TASK-146.4
parent_task_id: TASK-146
priority: medium
type: enhancement
ordinal: 160000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Com os 4 blocos (Caminhao, Pesagem, Classificacao, Origem/Destino) salvando individualmente via persistirBloco(), o FAB cinza 'salvar sem avancar' (CargaForm.vue, funcao salvarSemAvancar) fica redundante — exceto pelo campo Observacao (textarea livre, hoje fora de qualquer bloco, CargaForm.vue:996-1003), que perderia forma de salvar se o FAB sumir. Recomendacao do plano: mover Observacao para dentro do dialog do bloco Caminhao (campo mais 'de contexto geral' da viagem) e remover o FAB cinza do template principal (a funcao salvarSemAvancar pode virar a base de persistirBloco, entao nao desaparece do codigo, so deixa de ser exposta como FAB). Fazer so depois que os 4 blocos estiverem validados em uso real no patio — mais seguro avaliar com a tela nova rodando de verdade. Ver TASK-146 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.
<!-- SECTION:DESCRIPTION:END -->

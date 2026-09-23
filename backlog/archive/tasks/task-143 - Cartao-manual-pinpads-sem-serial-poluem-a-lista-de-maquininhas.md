---
id: TASK-143
title: 'Cartao manual: pinpads sem serial poluem a lista de maquininhas'
status: To Do
assignee: []
created_date: '2026-09-22 20:26'
updated_date: '2026-09-23 21:02'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 153000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto durante a TASK-142. Na etapa 'maquineta' do cartao MANUAL (FormaCartao.vue, opcoesMaquineta) a lista vem de posSaurus, que traz uma linha POR PINPAD. Como nenhum pinpad tem serial cadastrado no banco (todos null em tblsauruspinpad.serial), o Botanico mostra 13 linhas, todas desabilitadas com 'Sem serial cadastrado - digite o serial acima', e com apelidos repetidos (BOT Alfa 3x, BOT Bravo 3x, BOT Foxtrot 3x). Pior: o valor cai no fallback 'sem-serial-${codsauruspdv}', que repete entre pinpads do mesmo PDV e vira key duplicada no v-for do ListaOpcoes. Diferente da lista de ENVIO (deduplicada na TASK-142), aqui nao da pra deduplicar por codsauruspdv: o que importa e o serial de cada aparelho. Decidir entre esconder os sem serial, agrupar por PDV ou cadastrar os seriais (TI). Ver tambem TASK-100, que removeu o pedido do serial no 1o uso.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:02
---
Dobrada na TASK-142 (regra 3 do CLAUDE.md): o conteúdo está nas notas da task-mãe. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->

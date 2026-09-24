---
id: TASK-132
title: 'Agro/Patio: sem dia filtrado o pull baixa a safra inteira, pagina por pagina'
status: To Do
assignee: []
created_date: '2026-09-21 21:33'
updated_date: '2026-09-23 21:04'
labels:
  - agro
dependencies: []
priority: medium
type: enhancement
ordinal: 143000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
dataFiltro nasce null (o filtro de dia do CargaLeftDrawer comeca vazio) e puxarCargasDoDia chama puxarCargas(codsafra, null), que cai em puxarPaginasCarga({codsafra}) - varrendo TODAS as paginas de TODAS as cargas da safra a cada ciclo de sync (50 por pagina, MgModel::$perPage). Numa safra com 3.000 romaneios sao 60 requisicoes por sync, e a lista de finalizadas exibida e cortada em 30 (LIMITE_FINALIZADAS_SEM_DATA) - ou seja, quase tudo que foi baixado nem aparece. Opcoes: default do filtro = hoje; ou pull sem data limitado as ultimas N (sort -data e parar na primeira pagina); ou usar as ETAPAS_ABERTAS tambem no caso sem data.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
21/09/2026: o campo 'Dia' do CargaLeftDrawer foi REMOVIDO da tela (veio do legado e nao servia no layout novo). Com isso dataFiltro fica null sempre e o pull largo passou a ser o unico caminho — o que torna esta task o lugar onde se decide o recorte. store.definirData continua existindo mas sem nenhum chamador; ou volta como filtro/periodo aqui, ou sai junto com a correcao do pull.
<!-- SECTION:NOTES:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:04
---
Dobrada na TASK-169 (regra 3 do CLAUDE.md): virou o critério de aceite #3 da task-mãe, e o texto desta descrição está preservado nas notas da TASK-169. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->

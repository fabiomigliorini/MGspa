---
id: TASK-138
title: 'Agro: tela de listagem de romaneios com filtros na lateral'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 12:23'
updated_date: '2026-09-22 13:54'
labels:
  - agro
dependencies:
  - TASK-137
priority: medium
type: feature
ordinal: 148000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tela nova /cargas, no molde da listagem de notas (notas.mgpapelaria.com.br/nota). O Patio (/carga/:uuid) fica INTOCADO: ele e offline-first e so ve a safra ativa; a listagem e online e ve tudo.

Escopo:
- Rotas em agro/src/router/routes.js: 'cargas' (name cargas, meta.leftDrawer = CargasFiltrosDrawer via markRaw(defineAsyncComponent)) e 'cargas/:codcarga' (name carga-detalhe). Plural vs singular, sem ambiguidade. O MainLayout ja renderiza meta.leftDrawer generico.
- Entrada 'Romaneios' no menuGroups do MainLayout, grupo Operacao.
- stores/cargaListagem.js: SETUP store (convencao do agro) com persist no 3o argumento; pinia-plugin-persistedstate ja esta registrado em stores/index.js e nenhuma store usa ainda. persist: { pick: ['filtros','agrupar'] }.
  - params() traduz a UI: inativo = canceladas ? 9 : 1 (nunca omitir).
  - buscar(reset) espelha o fetchNotas do notas, MAS com requisicaoId incremental: o notas aborta se loading e perde o filtro digitado durante um scroll.
  - Cadastros vem da API, nao do Dexie. carregarContratos() tem que varrer todas as paginas (MgModel::$perPage = 50, senao o 51o contrato some do select) - copiar o laco de puxarTabela em sincronizacao.js.
  - Default na 1a abertura: data_inicio = 1o dia do mes corrente.
- CargasFiltrosDrawer.vue: v-model direto em store.filtros.*, sem botao Aplicar, watch deep + useDebounceFn 800ms. Contador de filtros ativos + limpar. Blocos: periodo/safra/cultura | sentido/etapa/papel/canceladas | unidade/talhao/contrato/cliente | placa/carreta/motorista/n romaneio | agrupar (relatorio).
  - agrupar fica FORA do watch (trocar agrupamento nao refaz a busca da tela).
- CargasPage.vue: 3 estados (spinner / empty-state / q-infinite-scroll offset 250 com q-list separator), barra de totais do RECORTE INTEIRO no topo, q-item clickable :to carga-detalhe.

NAO reusar SelectUnidade/SelectContrato/SelectTalhao: leem useCargaStore(), populado so pelo onMounted do CargaPage. Num navegador que nunca abriu o Patio vem SEM OPCAO NENHUMA, em silencio. Usar q-select comum alimentado pela store nova. Nao tocar nesses componentes - o Patio depende deles offline.

Ajuste aditivo em CargaListItem.vue: prop sync (default true) com v-if no bloco cloud_done/cloud_off - dado do servidor nao tem sincronizado/syncerro e toda linha mostraria 'Pendente' falso.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
REABERTA: a listagem entregue ficou ruim visualmente.

Causa: reusei o CargaListItem, que e item do drawer de 300px do patio (avatar grande,
labels empilhados, barra de progresso). Esticado numa pagina larga virou bloco solto,
sem colunas, com a barra de progresso deformada. O cabecalho de totais tambem tinha
q-separator vertical dentro de row q-col-gutter-md, que renderiza como bloco cinza.
eslint, prettier, quasar build e o transform do Vite passaram todos -- nenhum ve layout.

Refeito espelhando a NotasPage de verdade:
- q-page cru: sem q-pa-md, sem max-width/margin auto, sem q-card em volta da lista.
- q-item dense hoverable clickable class="q-py-xs" em q-list separator.
- q-item-section avatar min-width 32px com q-icon puro (nao q-avatar).
- Conteudo num GRID: div.row items-center com celulas q-px-sm col-<xs> col-md-<n> em
  text-caption, somando 12 no md: romaneio 2 | placa 2 | origem->destino 3 | chegada 2 |
  desconto 1 | liquido 2. Segunda linha col-12 com etapa pendente (colorida), motorista e safra.
- Barra de totais repetindo as MESMAS larguras (2+2+3 = col-md-7, depois 2, 1, 2) pra cada
  total cair em cima da sua coluna.
- Tres estados e FAB fab-mini iguais aos da notas.
- CargaListItem revertido ao original (as props sync/ambosLados viraram codigo morto quando
  a pagina parou de usa-lo). O patio segue como estava.

Verificado com SCREENSHOT: o headless Chrome VOLTOU a funcionar neste ambiente (a memoria
que dizia signal 16 estava desatualizada). Renderizei o markup exato da template com os
dados REAIS do endpoint e o CSS do Quasar do node_modules, em 1600px e em 430px.
O estreito pegou um defeito adicional: liquido em col-12 deixava uma linha inteira vazia
so com o travessao nas cargas sem pesar -- virou col-6, dividindo a linha com o desconto
(que e exatamente o que a notas faz no campo valor).

eslint e prettier limpos; os dois modulos compilam pelo Vite.

FALTA VALIDACAO VISUAL do usuario em https://sistema-dev.mgpapelaria.com.br:8088/#/cargas
<!-- SECTION:NOTES:END -->

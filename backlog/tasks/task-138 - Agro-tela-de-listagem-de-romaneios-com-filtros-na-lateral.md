---
id: TASK-138
title: 'Agro: tela de listagem de romaneios com filtros na lateral'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 12:23'
updated_date: '2026-09-22 12:40'
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
Implementado.

Arquivos novos:
- agro/src/stores/cargaListagem.js -- setup store com persist (pick filtros+agrupar). pinia-plugin-persistedstate ja estava registrado em stores/index.js; esta e a primeira store do agro a usar.
- agro/src/components/cargas/CargasFiltrosDrawer.vue
- agro/src/pages/CargasPage.vue
- agro/src/utils/abrirPdf.js (copia do estoque)

Alterados:
- agro/src/router/routes.js: rotas cargas e cargas/:codcarga + import do drawer com markRaw/defineAsyncComponent.
- agro/src/layouts/MainLayout.vue: item Romaneios no grupo Operacao.
- agro/src/components/carga/CargaListItem.vue: props sync (default true) e ambosLados. Sem a prop sync toda linha da tela nova mostraria "Pendente" (dado do servidor nao tem sincronizado/syncerro).
- agro/src/utils/carga.js: + normalizarCargaParaExibicao() e rotulosDoPapel().

Decisoes que valem lembrar:
- Selects proprios no drawer (q-select alimentado pela store) em vez de SelectUnidade/SelectContrato/SelectTalhao: aqueles leem o Dexie, populado so pelo onMounted do CargaPage. Quem cai direto em /cargas teria selects VAZIOS e sem erro nenhum.
- params() manda inativo 1 ou 9 SEMPRE -- sem a chave o backend traz as canceladas.
- buscar() usa requisicaoId incremental em vez de abortar por loading (o fetchNotas do notas perde o filtro digitado durante um scroll).
- todasPaginas() varre a paginacao dos cadastros (perPage 50; o 51o contrato sumiria).
- agrupar fica fora do watch de filtros: trocar agrupamento nao refaz a busca da tela.
- data_inicio default = 1o dia do mes corrente.

Verificado: eslint limpo; os 8 modulos compilam pelo Vite (HTTP 200 em https://localhost:8088/<modulo>); endpoint devolve envelope data/links/meta/totais com totais no TOPO.

FALTA VALIDACAO VISUAL do usuario em https://sistema-dev.mgpapelaria.com.br:8088/#/cargas
<!-- SECTION:NOTES:END -->

---
id: TASK-177
title: Listagens de telas diferentes aparecem com fonte e visual diferentes
status: To Do
assignee: []
created_date: '2026-09-26 17:37'
updated_date: '2026-10-17 18:58'
labels:
  - components
dependencies: []
priority: low
type: chore
ordinal: 191000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: validacao da tela de Vales Emitidos em 26/09/2026. As listagens do projeto misturam q-table e q-list (com q-infinite-scroll), inclusive telas vizinhas do mesmo modulo: Modelos de Vale usa q-table (fonte 13px, do CSS do Quasar) e Vales Emitidos usa q-list (14px, herdado do body). Contagem de paginas em src/pages: contas 13 q-table / 5 q-list, pessoas 7/5, negocios 2/4, notas 2/5, estoque 0/3, agro 1/1.

Objetivo: definir UM padrao de listagem (q-table ou q-list, e quando cada um), documentar (CLAUDE.md) e ajustar as telas. Registro com duas linhas por item e possivel nos dois (q-table: duas linhas na mesma celula, ou duas q-tr por registro no slot #body).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Drawer de filtros das listagens com o mesmo visual em todos os apps (hoje são duas versões copiadas)
- [x] #2 Listagem de notas fiscais no padrão: card central no fundo cinza, largura máxima, espaço no fim para os botões flutuantes, linhas da mesma altura e sem texto quebrando
- [ ] #3 Outras listagens do notas (Naturezas, NFe de Terceiro, DFe) também em card central: com o fundo cinza no layout, a lista solta delas fica sobre o cinza
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Achado em 10/10/2026 (TASK-207): FilterDrawerShell.vue e FilterGroup.vue estão copiados em agro, contas, estoque e negocios (src/components), em duas variantes. contas/estoque: separador entre os grupos e titulo com q-mb-md; agro/negocios: sem separador (comentado), titulo com q-mt-md q-mb-sm e o shell com no-wrap. Caminho: escolher um visual, mover os dois para @components (prefixo Mg) e trocar os imports (31 arquivos usam).

17/10/2026 — Listagem de notas fiscais (notas/src/pages/NotasPage.vue) no padrão card central:
- Fundo cinza no layout do notas (q-page-container bg-grey-2), como nos outros apps. Efeito colateral: as listas soltas de Naturezas, NFe de Terceiro e DFe agora ficam sobre o cinza (AC #3).
- Conteúdo em div q-pa-md com max-width 1086px; lista em q-card flat bordered; vazio com MgEmptyState; div q-py-xl no fim para os FABs não cobrirem o último registro; FABs em row items-end com tooltip por cima.
- Linha em duas colunas empilhadas: número/filial e cliente/natureza de operação; data e valor na altura da primeira linha (items-start). Cidade do cliente saiu da linha e do NotaFiscalResource (só a listagem lia; o NotaFiscalDetailResource continua com cidade). Isso também acabou com 2 consultas lazy por nota (Pessoa->Cidade->Estado) no index; o NegocioResource deixou de carregar Pessoa.Cidade.Estado para as notas do negócio.
- Linha mais alta que o conteúdo (68px com texto de 40px): o div.row dentro do q-item-section (flex column) era medido sem largura definida, as colunas em % ficavam estreitas e o texto quebrava nessa medição. Classe full-width no row resolve (48px). Medido numa réplica no Chrome headless.
- Todos os campos com ellipsis. Ações: coluna da direita no md+, embaixo do valor no sm (row justify-end, porque o MgNotaFiscalAcoes é um flex row e text-right não o move), escondidas no xs (ficam no detalhe da nota).
<!-- SECTION:NOTES:END -->

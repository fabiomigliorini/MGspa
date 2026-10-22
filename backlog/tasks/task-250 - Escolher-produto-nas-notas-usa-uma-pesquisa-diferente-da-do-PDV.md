---
id: TASK-250
title: Escolher produto nas notas usa uma pesquisa diferente da do PDV
status: To Do
assignee: []
created_date: '2026-10-20 16:36'
updated_date: '2026-10-21 20:53'
labels:
  - notas
dependencies: []
priority: low
type: enhancement
ordinal: 260000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Nas telas do app de notas, o produto se escolhe por um select de texto que acha por semelhança (traz produto sem a palavra digitada, como Amarelo Limão pesquisando 'tinta tecido acrilex vermelh'), enquanto o PDV e as telas novas usam a pesquisa em grade do F1 (TASK-173), que só traz o que tem todas as palavras. O Fábio quer uma pesquisa só: o MgSelectProdutoBarra deve ser eliminado e as notas passam a usar a pesquisa em grade.

Origem: conversa da TASK-173 em 2026-10-20.

Onde está:
- components/MgSelectProdutoBarra.vue: só em notas/src/pages/NfeTerceiroItemViewPage.vue (campo Produto da janela 'Informar Detalhes' do item da NFe de Terceiros).
- notas/src/stores/selects/produtoBarra.js: lista própria de produtos de notas/src/components/dialogs/NotaFiscalItemDialog.vue, que lê a mesma rota do select.
- Backend do select: api/app/Mg/Select/SelectProdutoBarraController.php, rotas GET v1/select/produto-barra e v1/select/produto-barra/{id}. A rota v1/select/produto-barra/barras/{barras} do mesmo controller é a que o @components/MgInputProdutoBarras usa para resolver o código bipado: muda para o domínio de produto antes de apagar o controller.
- A pesquisa em grade é @components/MgInputProdutoBarras + MgDialogPesquisaProduto, com GET v1/produto-barra/pesquisa (ProdutoBarraService::pesquisa).
- Fora do escopo: o quasar/ v1 (tela de etiquetas) tem um componente homônimo próprio.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O campo Produto do 'Informar Detalhes' do item da NFe de Terceiros escolhe o produto pela pesquisa em grade (F1)
- [ ] #2 A escolha de produto do item da nota fiscal usa a mesma pesquisa em grade
- [ ] #3 MgSelectProdutoBarra, o store selects/produtoBarra do notas e as rotas v1/select/produto-barra saem do código; a resolução do código bipado fica numa rota do domínio de produto
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Em 21/10/2026, na TASK-53, a resolução do código bipado do MgInputProdutoBarras saiu do select: agora é GET v1/produto-barra/barras/{barras} (ProdutoBarraController@barras, sem a vwProdutoBarra). Do AC #3 falta tirar MgSelectProdutoBarra, o store do notas e as rotas v1/select/produto-barra e v1/select/produto-barra/{id}.
<!-- SECTION:NOTES:END -->

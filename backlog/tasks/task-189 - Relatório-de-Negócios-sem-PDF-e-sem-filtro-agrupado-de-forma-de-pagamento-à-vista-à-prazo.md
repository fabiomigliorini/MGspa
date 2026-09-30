---
id: TASK-189
title: >-
  Relatório de Negócios sem PDF e sem filtro agrupado de forma de pagamento (à
  vista/à prazo)
status: To Do
assignee: []
created_date: '2026-09-30 18:05'
updated_date: '2026-09-30 18:08'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 202000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No MGsis (legado) a listagem de negócios tem um relatório em PDF com filtro por forma de pagamento (print de referência anexado no pedido). No app /negocios atual (Quasar) a listagem equivalente já existe (negocios/src/pages/ListagemPage.vue + negocios/src/components/drawers/ListagemLeftDrawer.vue), com filtro granular de forma de pagamento (sListagem.filtro.codformapagamento, multi-select — ver negocios/src/stores/listagem.js:19-48: Dinheiro, PIX Chave, Cartão Manual, Entrega, Fechamento, Carteira, Boleto), mas falta o relatório em PDF e falta um filtro pelo agrupamento generalizado que os usuários usam vindos do legado: À Prazo x À Vista.

Pedido por Fábio em 30/09/2026, com print do relatório de negócios do legado (colunas Filial, Usuário, Oper, #, Data, À Prazo, À Vista, Total, Status, #Pessoa, Fantasia, Vendedor) como referência de layout.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Relatório de negócios pode ser gerado em PDF, com layout idêntico ao do legado MGsis (mesmas colunas: Filial, Usuário, Oper, #, Data, À Prazo, À Vista, Total, Status, #Pessoa, Fantasia, Vendedor)
- [ ] #2 Filtro de forma de pagamento da tela /negocios ganha as opções generalizadas 'À Prazo' e 'À Vista', selecionáveis junto (multi-select), somando-se ou substituindo as formas específicas já existentes
- [ ] #3 Categoria 'À Prazo' cobre PIX Chave, Entrega, Fechamento, Boleto e Carteira
- [ ] #4 Categoria 'À Vista' cobre Dinheiro e Cartão Manual
- [ ] #5 O PDF gerado respeita o filtro de forma de pagamento aplicado na tela (categorias e/ou formas específicas)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Achados técnicos (investigação de apoio, 30/09/2026):
- Filtro de forma de pagamento já existe também no backend: PdvController::getNegocios (api/app/Mg/Pdv/PdvController.php ~L372-379), whereIn via subquery em tblnegocioformapagamento. Rota GET pdv/negocio (routes/api.php:874).
- tblformapagamento já tem coluna booleana 'avista' (model api/app/Mg/FormaPagamento/FormaPagamento.php). O agrupamento à vista/à prazo pode vir dessa coluna em vez de lista fixa no front.
- Componente compartilhado @components/MgSelectFormaPagamento.vue já existe (multi-select, usado em /contas e /pessoas via GET v1/select/forma-pagamento). O ListagemLeftDrawer.vue atual usa q-select cru com opções fixas via process.env — trocar por MgSelectFormaPagamento é o padrão do projeto.
- Padrão de PDF pronto para seguir: TituloController::relatorioListagem + TituloListagemRelatorioService (api/app/Mg/Titulo/), usa mpdf (não dompdf). Outros exemplos: NegocioController/RomaneioService, ValeEmitidoRelatorioService.
- ATENÇÃO / A CONFIRMAR: backend tem constante CODFORMAPAGAMENTO_ENTREGA_AVISTA (NegocioFormaPagamentoService.php:40) para a forma 'Entrega' (código 1099), sugerindo que Entrega seria À VISTA — mas o pedido original classificou Entrega como À PRAZO. Confirmar com quem pediu antes de implementar o agrupamento, pode ser nome de constante desatualizado ou categoria realmente diferente do que foi descrito.
<!-- SECTION:NOTES:END -->

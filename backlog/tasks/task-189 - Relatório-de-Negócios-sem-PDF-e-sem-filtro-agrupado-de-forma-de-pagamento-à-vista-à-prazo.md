---
id: TASK-189
title: >-
  Relatório de Negócios sem PDF e sem filtro agrupado de forma de pagamento (à
  vista/à prazo)
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-30 18:05'
updated_date: '2026-10-10 19:44'
labels:
  - negocios
dependencies: []
priority: critical
type: feature
ordinal: 205000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No MGsis (legado) a listagem de negócios tem um relatório em PDF com filtro por forma de pagamento (print de referência anexado no pedido). No app /negocios atual (Quasar) a listagem equivalente já existe (negocios/src/pages/ListagemPage.vue + negocios/src/components/drawers/ListagemLeftDrawer.vue), com filtro granular de forma de pagamento (sListagem.filtro.codformapagamento, multi-select — ver negocios/src/stores/listagem.js:19-48: Dinheiro, PIX Chave, Cartão Manual, Entrega, Fechamento, Carteira, Boleto), mas falta o relatório em PDF e falta um filtro pelo agrupamento generalizado que os usuários usam vindos do legado: À Prazo x À Vista.

Pedido por Fábio em 30/09/2026, com print do relatório de negócios do legado (colunas Filial, Usuário, Oper, #, Data, À Prazo, À Vista, Total, Status, #Pessoa, Fantasia, Vendedor) como referência de layout.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Relatório de negócios pode ser gerado em PDF, com layout idêntico ao do legado MGsis (mesmas colunas: Filial, Usuário, Oper, #, Data, À Prazo, À Vista, Total, Status, #Pessoa, Fantasia, Vendedor)
- [x] #2 Filtro de forma de pagamento da tela /negocios ganha as opções generalizadas 'À Prazo' e 'À Vista', selecionáveis junto (multi-select), somando-se ou substituindo as formas específicas já existentes
- [x] #3 À Prazo traz o negócio com valor a prazo, como no MGsis: toda parcela (Fechamento, Crediário, Boleto, Entrega, PIX Chave, vale da devolução)
- [x] #4 À Vista traz o negócio com valor à vista, como no MGsis: todo pagamento no balcão (dinheiro, cartão manual ou integrado, PIX QR, cheque, vale)
- [x] #5 O PDF gerado respeita o filtro de forma de pagamento aplicado na tela (categorias e/ou formas específicas)
- [x] #6 Filtros Forma de pagamento e Integração da listagem de negócios voltam a filtrar (o servidor ignora desde 01/10, TASK-188 M6, commit bc911e922)
- [x] #7 Listagem de negócios no padrão atual das telas (lista em card, drawer de filtros com contador e limpar, selects compartilhados)
- [x] #8 Menu Negócios do MGsis desligado (depois do PDF em produção)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Achados técnicos (investigação de apoio, 30/09/2026):
- Filtro de forma de pagamento já existe também no backend: PdvController::getNegocios (api/app/Mg/Pdv/PdvController.php ~L372-379), whereIn via subquery em tblnegocioformapagamento. Rota GET pdv/negocio (routes/api.php:874).
- tblformapagamento já tem coluna booleana 'avista' (model api/app/Mg/FormaPagamento/FormaPagamento.php). O agrupamento à vista/à prazo pode vir dessa coluna em vez de lista fixa no front.
- Componente compartilhado @components/MgSelectFormaPagamento.vue já existe (multi-select, usado em /contas e /pessoas via GET v1/select/forma-pagamento). O ListagemLeftDrawer.vue atual usa q-select cru com opções fixas via process.env — trocar por MgSelectFormaPagamento é o padrão do projeto.
- Padrão de PDF pronto para seguir: TituloController::relatorioListagem + TituloListagemRelatorioService (api/app/Mg/Titulo/), usa mpdf (não dompdf). Outros exemplos: NegocioController/RomaneioService, ValeEmitidoRelatorioService.
- ATENÇÃO / A CONFIRMAR: backend tem constante CODFORMAPAGAMENTO_ENTREGA_AVISTA (NegocioFormaPagamentoService.php:40) para a forma 'Entrega' (código 1099), sugerindo que Entrega seria À VISTA — mas o pedido original classificou Entrega como À PRAZO. Confirmar com quem pediu antes de implementar o agrupamento, pode ser nome de constante desatualizado ou categoria realmente diferente do que foi descrito.

Revisão do backlog com o Fábio (10/10/2026), URGENTE (Critical a pedido dele): desde o commit bc911e922 (TASK-188 M6, 01/10) o PdvController::getNegocios não tem mais os cases 'forma' e 'integracao'; a tela (negocios/src/stores/listagem.js:86-87, ListagemLeftDrawer.vue) continua mandando filtro.forma e filtro.integracao e o servidor ignora em silêncio, devolvendo a lista sem filtrar. PdvNegocioPagamentoService::filtroForma ficou sem chamada. As notas técnicas de 30/09 acima estão velhas: NegocioFormaPagamentoService foi apagado no M6; hoje a forma é o meio do pagamento (tblpagamento) ou a condição da parcela (tblnegocioparcela), e tblnegocio.valoravista/valoraprazo já são gravados (PdvNegocioService).

Implementação (10/10/2026):
- Resolvido o 'A CONFIRMAR' da Entrega: a forma 1099 'Entrega A Vista' tem tblformapagamento.avista = false, então já era à prazo no MGsis; no modelo novo é parcela E, que entra no valoraprazo. Desde jun/2026 valoravista + valoraprazo = valortotal em todos os fechados (dev).
- Filtros: api/app/Mg/Pdv/PdvNegocioListagemService::filtrar (o switch que estava inline no PdvController::getNegocios), usado pela listagem e pelo PDF. forma: avista/aprazo = valoravista/valoraprazo > 0 (como o MGsis, Negocio::search do legado), m<meio>/c<condição> = PdvNegocioPagamentoService::filtroForma, tudo com OU. integracao: PdvNegocioPagamentoService::filtroIntegracao de volta (Integrado = pagamento com codpixcob/codpagarmepedido/codsauruspedido/codliopedido; Manual = pagamento sem integração ou parcela ativa).
- PDF: GET v1/pdv/negocio/relatorio (?html=1 devolve o HTML), PdvNegocioRelatorioService + resources/views/negocio/relatorio.blade.php, cópia do MGRelatorioNegocios do MGsis (A4 retrato, 190mm, mesmas colunas e larguras, agrupado por status com Total e Total Geral, ordem status/lançamento desc/código desc). Fonte DejaVu Sans Condensed (as larguras do legado eram para helvetica). Limite de 2000 negócios (~4ms e ~0,15MB por linha no mPDF; cabe nos 15s do axios do app e nos 512MB), acima disso 422 pedindo para refinar o filtro.
- Tela: ListagemPage no padrão da listagem de pagamentos (card, MgEmptyState, contador, FAB de impressão); drawer com FilterDrawerShell/FilterGroup e os MgSelect compartilhados; SelectPdv e SelectUsuario locais apagados (só a listagem usava).
- MGsis (repo próprio): item 'Negócios *' comentado em protected/views/layouts/main.php, como o [DEL] Liquidacoes (6d6d7c6).

- Validação (10/10): o PDF dava 500 ('Trying to access array offset on int' no Mpdf.php) porque o packTableData do mPDF quebra com célula mesclada; a linha de Total de cada grupo ficou sem colspan.
<!-- SECTION:NOTES:END -->

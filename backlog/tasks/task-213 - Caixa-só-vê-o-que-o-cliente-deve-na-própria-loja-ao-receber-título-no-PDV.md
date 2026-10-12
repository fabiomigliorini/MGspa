---
id: TASK-213
title: Caixa só vê o que o cliente deve na própria loja ao receber título no PDV
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 20:44'
updated_date: '2026-10-10 21:02'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 225000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Reportado pelo Fábio em 10/10/2026, com print de /pagamento/receber (Receber Título / Pagar Vale do PDV) abrindo com o filtro Filial já preenchido com a loja do caixa. Se o cliente deve em outra loja, o caixa não vê o título e não cobra, a menos que limpe o filtro na mão.

Origem: TASK-188 (M6.1, commit 3030dd4e8) passou filialPadrao: sSinc.pdv.codfilial ao MgBaixaTitulos em negocios/src/pages/PagamentoReceberPage.vue. Não foi pedido nem está em doc. O servidor (PdvPagamentoController@titulos) não força filial, só repassa o filtro.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Receber Título no PDV abre com o filtro de filial vazio e traz os títulos do cliente em todas as filiais; o filtro continua lá para quem quiser restringir
- [x] #2 Diálogo de Receber/Pagar abre com o foco na Pessoa quando ela vem vazia; com pessoa, no botão Receber/Pagar; sem wizard (encontro de contas, amarrando pagamento) ou com a forma já escolhida, no Gravar. A Data não pega mais o foco (o calendário abria sozinho)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Removido filialPadrao do seletor em negocios/src/pages/PagamentoReceberPage.vue (e o comentário do topo ajustado). O filtro de filial começa vazio; o MgSeletorTitulosAbertos já trata null como todas. O filialPadrao do contas/Pendentes.vue não mexe: lá é a filial onde o vale/adiantamento é criado, não filtro. Teste: PDV > Pagamentos > Receber Título, buscar pessoa com título em outra loja.

Foco do diálogo em components/MgBaixaTitulos.vue (compartilhado: vale também para o contas — Pagamentos, Novo pagamento, Não resolvidos). O q-btn repassa o atributo autofocus ao button nativo e o QDialog foca o [autofocus] ao abrir.
<!-- SECTION:NOTES:END -->

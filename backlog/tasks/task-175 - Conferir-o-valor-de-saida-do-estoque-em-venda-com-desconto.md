---
id: TASK-175
title: >-
  Compra com desconto lançada pelo negócio pode entrar no estoque com o custo
  errado
status: To Do
assignee: []
created_date: '2026-09-25 15:49'
updated_date: '2026-10-10 17:22'
labels:
  - estoque
dependencies: []
priority: low
type: task
ordinal: 189000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Surgiu no milestone 4 do vale compras (TASK-38). O job de estoque do MGLara
(app/Jobs/EstoqueGeraMovimentoNegocioProdutoBarra.php:114-118) calculava o valor de saida aplicando
o desconto do CABECALHO rateado sobre o item: valortotal * (1 - negocio.valordesconto /
negocio.valorprodutos). Esse rateio era resquicio de quando tblnegocioprodutobarra ainda nao tinha a
coluna valordesconto; hoje tem, e o item ja chega com o desconto rateado.

A mudanca foi feita: o job passou a usar NegocioProdutoBarra.valorprodutos - valordesconto.
Alem de corrigir, tira o job de qualquer dependencia de valores de cabecalho -- era isso que o
deixava errado quando o negocio tem vale, porque o valorvales nao entra no denominador.

O QUE FALTA: o efeito nunca foi observado. Nenhum negocio real passou pelo job depois da mudanca.

Conferir, depois de fechar um negocio com desconto de cabecalho:
  docker exec mgdb-mgdb-1 psql -U mgsis -d mgsis -c "select em.saidavalor, npb.valorprodutos,
  npb.valordesconto from tblestoquemovimento em join tblnegocioprodutobarra npb on ... "
O saidavalor tem que bater com valorprodutos - valordesconto do proprio item.

Prioridade Medium porque, se estiver errado, o custo de saida sai errado em TODA venda com
desconto -- silenciosamente, so aparecendo no custo medio e na margem.

O job inteiro sera refatorado depois; esta task e' so a conferencia da mudanca.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): a premissa da venda estava errada. A venda (tipo 3001, preco=2) sai pelo custo médio; nas 18 vendas com desconto desde 25/09 a saída está no custo médio, como deve. A mudança do MGLara (commit 29135a9) só vale para movimento com preço informado, que no negócio é só a Compra (2001): 16 em 30 dias, nenhuma com desconto. Conferir na primeira compra com desconto lançada pelo negócio: entradavalor = valorprodutos - valordesconto do item.
<!-- SECTION:NOTES:END -->

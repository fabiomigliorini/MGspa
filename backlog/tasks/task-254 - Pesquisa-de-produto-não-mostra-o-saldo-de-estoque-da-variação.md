---
id: TASK-254
title: Pesquisa de produto não mostra o saldo de estoque da variação
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-22 16:31'
updated_date: '2026-10-23 21:02'
labels:
  - components
dependencies: []
priority: low
type: feature
ordinal: 264000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do pessoal: na janela de variação/embalagem da pesquisa em grade (MgGradeProduto, a mesma do PDV e das telas online), mostrar o saldo físico de estoque da variação em foco, uma linha por local de estoque e o total, como na consulta de preços do quiosque, só que na vertical. A consulta vai ao backend pelo codprodutovariacao a cada troca de variação, e a resposta fica guardada num store Pinia por alguns minutos para não repetir enquanto a pessoa navega.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Terceira coluna Saldo na janela da variação, com uma linha por local e o total (saldo físico)
- [x] #2 Saldo da variação guardado num store Pinia por 5 minutos; voltar a uma variação já consultada não refaz a requisição
- [x] #3 Rota GET v1/produto-barra/{codprodutobarra}/saldo devolve o saldo físico por local da variação daquela barra (a linha da pesquisa e o catálogo do PDV não têm o codprodutovariacao)
- [x] #4 Saldo acompanha a embalagem em foco: na CX C/4 cada local mostra quantas caixas inteiras dá (arredonda para baixo, negativo em direção ao zero) e o Total é a soma das linhas
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
codprodutovariacao entrou na linha da pesquisa (ProdutoBarraService::pesquisa, ProdutoBarraPesquisaResource) e do catálogo do PDV (PdvCatalogoService::produto). Como o campo muda todas as linhas, o primeiro diff do catálogo depois do deploy traz o catálogo inteiro e o incremental dos PDVs atualiza sozinho; até lá, a coluna mostra 'Saldo indisponível'. Cache em components/stores/saldoVariacaoStore.js (5 min por variação).

Saldo na embalagem (AC #4): calculado no front, em MgGradeProduto (saldoNaEmbalagem), com o saldo em UN que já veio da rota e a quantidade da embalagem que já está na linha da barra -- trocar de embalagem não consulta o backend. Cada local vira Math.trunc(saldo / quantidade) (para baixo; negativo em direção ao zero), com toFixed(6) antes para o resto de ponto flutuante; o Total é a soma das linhas, não o total em UN dividido.
<!-- SECTION:NOTES:END -->

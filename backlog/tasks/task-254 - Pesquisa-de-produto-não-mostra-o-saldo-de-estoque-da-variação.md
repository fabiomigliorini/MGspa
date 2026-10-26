---
id: TASK-254
title: Pesquisa de produto não mostra o saldo de estoque da variação
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-22 16:31'
updated_date: '2026-10-24 21:50'
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
- [x] #5 O card da pesquisa mostra um chip com o saldo do produto, vindo de GET v1/produto/{codproduto}/saldo[/{codestoquelocal}] e guardado no Pinia por 5 minutos
- [x] #6 Um select Saldo no cabeçalho (Em todos os locais, Deposito, Botanico...) escolhe de onde vem o saldo, sem filtrar a lista nem refazer a pesquisa; a tela que chama passa o local inicial
- [x] #7 Card sem saldo (zero ou negativo) no que está escolhido aparece com a foto cinza
- [x] #8 No celular, as opções do cabeçalho ficam num botão só, que abre uma janela pequena
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
codprodutovariacao entrou na linha da pesquisa (ProdutoBarraService::pesquisa, ProdutoBarraPesquisaResource) e do catálogo do PDV (PdvCatalogoService::produto). Como o campo muda todas as linhas, o primeiro diff do catálogo depois do deploy traz o catálogo inteiro e o incremental dos PDVs atualiza sozinho; até lá, a coluna mostra 'Saldo indisponível'. Cache em components/stores/saldoEstoqueStore.js (5 min por variação).

Saldo na embalagem (AC #4): calculado no front, em MgGradeProduto (saldoNaEmbalagem), com o saldo em UN que já veio da rota e a quantidade da embalagem que já está na linha da barra -- trocar de embalagem não consulta o backend. Cada local vira Math.trunc(saldo / quantidade) (para baixo; negativo em direção ao zero), com toFixed(6) antes para o resto de ponto flutuante; o Total é a soma das linhas, não o total em UN dividido.

Chip do card (AC #5 a #8): GET v1/produto/{codproduto}/saldo[/{codestoquelocal}] (ProdutoService::saldo, SQL cru, ~0,3 ms) soma o saldo físico de todas as variações do produto, em todos os locais ou num só. O store virou saldoEstoqueStore, com dois caches de 5 min: variacoes (janela) e produtos ('codproduto|codestoquelocal', chip). A pesquisa (produto-barra/pesquisa) não mudou; cada card pede o seu saldo, e trocar o select Saldo não refaz a pesquisa. O MgGradeProduto só liga o chip com a prop saldo: a pesquisa offline do PDV não liga e não faz requisição. O select de locais usa o v1/select/estoque-local, que passou a devolver a sigla. No celular (lt.sm), o cabeçalho fica com o campo e um botão (tune) que abre Ordem, Saldo, Limpar e Fechar pesquisa.

O estoque fica no canto superior direito da foto, com o rótulo Estoque miúdo em cima do número (verde > 0, cinza 0, vermelho < 0). Quem usa o MgInputProdutoBarras passa :codestoquelocal para o select Saldo começar no local da tela (na requisição, a requisitada); sem ele, Em todos os locais.

Opções do select Saldo: Em todos os locais, depois os locais pelo nome (o label do v1/select/estoque-local), na ordem do código: Deposito, Botanico, Centro, Imperial, Andre Maggi... A sigla que o endpoint passou a devolver não é mais usada aqui.
<!-- SECTION:NOTES:END -->

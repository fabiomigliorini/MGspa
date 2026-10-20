---
id: TASK-173
title: >-
  Pesquisa de produto repete a mesma caneta em 12 cards e cada tela navega de um
  jeito
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-24 21:44'
updated_date: '2026-10-19 23:35'
labels:
  - negocios
  - components
dependencies: []
priority: medium
type: enhancement
ordinal: 187000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quem pesquisa 'caneta bic cristal comum' recebe uma tela cheia de cards que sao o MESMO produto: muda so a embalagem (UN, CX C/1.000, CX C/1.200, PT C/108, CX C/50) e a variacao (azul, preta, verde). Sao 12 cards de caneta Bic para o operador ler um a um antes de achar o que quer, e ainda aparecem dois cards identicos de 1.296,00 (duas barras da mesma embalagem).

Junto disso, as duas telas que fazem essa mesma pesquisa nao se comportam igual: a do PDV so aceita clique, a nova (telas online) anda pelo teclado. Quem usa as duas no mesmo dia tem que lembrar em qual esta.

E essa mesma grade e o que deve substituir as pranchetas impressas do balcao, usadas hoje para achar produto que nao tem codigo de barras para bipar (vinha da TASK-49, unificada aqui em 2026-09-24).

Origem: conversa de 2026-09-24, durante o cadastro do modelo de vale-compras. A parte da prancheta vem do antigo negocios/todo, secao DESEJAVEIS ('substituir pranchetas impressas').

Onde esta:
- components/MgInputProdutoBarras.vue + components/MgDialogPesquisaProduto.vue (online, telas novas): Enter pesquisa, setas andam, Enter escolhe, Esc volta ao campo, F1 abre, ordem com Relevancia, infinite scroll.
- negocios/src/components/offline/InputBarras.vue (PDV, offline): o dialog de pesquisa esta embutido no proprio arquivo, so clique, ordem Alfabetica/Preco/Codigo/Barras, e tem o que a outra nao tem (leitor de camera, comanda, orcamento, vale).
- Backend da grade online: api/app/Mg/Select/SelectProdutoBarraController.php le vwProdutoBarra, que devolve UMA linha por codprodutobarra. O agrupamento e por codproduto, com codprodutoembalagem/quantidade/sigla e variacao por baixo.

Decisoes de tela (agrupar, e como escolher a embalagem depois) a combinar antes de implementar.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Pesquisando um produto com varias embalagens/variacoes, a grade mostra UM card do produto em vez de um card por codigo de barras; a embalagem/variacao e escolhida depois, sem sair da pesquisa
- [ ] #2 Codigos de barras diferentes da mesma embalagem nao geram dois cards iguais
- [x] #3 A pesquisa do PDV (negocios/offline) e a das telas online navegam igual: mesmas teclas para pesquisar, andar pelos cards, escolher e fechar; mesmo comportamento de foco e destaque
- [ ] #4 O que hoje so existe numa das duas (ordem por Relevancia e infinite scroll de um lado, leitor de camera do outro) fica disponivel nas duas ou tem motivo registrado para nao ficar
- [ ] #5 A grade de pesquisa serve de prancheta digital: da para achar pelo nome e pela foto o produto que nao tem codigo de barras para bipar, aposentando as pranchetas impressas do balcao (era a TASK-49)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fábio (17/10/2026), decisões de tela fechadas na conversa da TASK-197: a grade agrupa o resultado da pesquisa por codproduto, depois pelo texto da variação, depois por embalagem (sigla + quantidade). Só aparece o que casou com o texto: pesquisou 'verde', não mostra a azul; sem buscar irmãos que não casaram. Escolha em etapas, que colapsam quando há uma opção só: 1ª clique no card do produto; 2ª na variação, se houver mais de uma; 3ª na embalagem, se houver mais de uma. A 3ª é por embalagem, não por barra: a mesma embalagem pode ter dois códigos de barras (critério #2) e aparece uma vez; o item do negócio leva qualquer barra dela, o preço é o mesmo. Teclado: setas andam pelas opções da etapa, Enter avança, Esc volta uma etapa. Depende do campo nome (nome base do produto, sem variação e embalagem) que o catálogo da TASK-197 passa a trazer em cada barra; variacao, sigla, quantidade e codimagem já vêm hoje e bastam para agrupar.

Implementação #1/#2 (19/10/2026, aguardando validação): grade compartilhada components/MgGradeProduto.vue (agrupa produto -> variação -> embalagem sigla+quantidade, etapas que colapsam, teclado: setas, Enter avança, Esc volta uma etapa; infinite scroll desligado nas etapas 2/3), usada por negocios/src/components/offline/DialogPesquisaProduto.vue (PDV e quiosque) e components/MgDialogPesquisaProduto.vue (online). Card do produto lista as embalagens que casaram, uma por linha com o preço (Fábio, 19/10); opção única fica no card de antes. Online: rota nova GET v1/select/produto-barra/pesquisa (SelectProdutoBarraController::pesquisa) pagina 20 PRODUTOS com todas as barras deles que casaram; filtro extraído para filtro(), comum ao index (select segue igual). PDV: teto de 200 passa a ser de produtos (stores/produto.js). Ordem dos dois diálogos virou MgSelect; o online ganhou o manterFoco do PDV.
<!-- SECTION:NOTES:END -->

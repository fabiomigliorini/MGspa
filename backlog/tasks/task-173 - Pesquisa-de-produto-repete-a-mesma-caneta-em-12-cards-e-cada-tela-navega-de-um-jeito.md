---
id: TASK-173
title: >-
  Pesquisa de produto repete a mesma caneta em 12 cards e cada tela navega de um
  jeito
status: To Do
assignee: []
created_date: '2026-09-24 21:44'
updated_date: '2026-10-17 19:49'
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
<!-- SECTION:NOTES:END -->

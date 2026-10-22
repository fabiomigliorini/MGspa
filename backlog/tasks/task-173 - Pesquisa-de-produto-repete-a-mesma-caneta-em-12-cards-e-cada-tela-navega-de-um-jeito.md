---
id: TASK-173
title: >-
  Pesquisa de produto repete a mesma caneta em 12 cards e cada tela navega de um
  jeito
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-24 21:44'
updated_date: '2026-10-21 18:26'
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
- [x] #1 Pesquisando um produto com varias embalagens/variacoes, a grade mostra UM card do produto em vez de um card por codigo de barras; a embalagem/variacao e escolhida depois, sem sair da pesquisa
- [x] #2 Codigos de barras diferentes da mesma embalagem nao geram dois cards iguais
- [x] #3 A pesquisa do PDV (negocios/offline) e a das telas online navegam igual: mesmas teclas para pesquisar, andar pelos cards, escolher e fechar; mesmo comportamento de foco e destaque
- [x] #4 O que hoje so existe numa das duas (ordem por Relevancia e infinite scroll de um lado, leitor de camera do outro) fica disponivel nas duas ou tem motivo registrado para nao ficar
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fábio (17/10/2026), decisões de tela fechadas na conversa da TASK-197: a grade agrupa o resultado da pesquisa por codproduto, depois pelo texto da variação, depois por embalagem (sigla + quantidade). Só aparece o que casou com o texto: pesquisou 'verde', não mostra a azul; sem buscar irmãos que não casaram. Escolha em etapas, que colapsam quando há uma opção só: 1ª clique no card do produto; 2ª na variação, se houver mais de uma; 3ª na embalagem, se houver mais de uma. A 3ª é por embalagem, não por barra: a mesma embalagem pode ter dois códigos de barras (critério #2) e aparece uma vez; o item do negócio leva qualquer barra dela, o preço é o mesmo. Teclado: setas andam pelas opções da etapa, Enter avança, Esc volta uma etapa. Depende do campo nome (nome base do produto, sem variação e embalagem) que o catálogo da TASK-197 passa a trazer em cada barra; variacao, sigla, quantidade e codimagem já vêm hoje e bastam para agrupar.

Implementação #1/#2 (19/10/2026, aguardando validação): grade compartilhada components/MgGradeProduto.vue (agrupa produto -> variação -> embalagem sigla+quantidade, etapas que colapsam, teclado: setas, Enter avança, Esc volta uma etapa; infinite scroll desligado nas etapas 2/3), usada por negocios/src/components/offline/DialogPesquisaProduto.vue (PDV e quiosque) e components/MgDialogPesquisaProduto.vue (online). Card do produto lista as embalagens que casaram, uma por linha com o preço (Fábio, 19/10); opção única fica no card de antes. Online: rota nova GET v1/select/produto-barra/pesquisa (SelectProdutoBarraController::pesquisa) pagina 20 PRODUTOS com todas as barras deles que casaram; filtro extraído para filtro(), comum ao index (select segue igual). PDV: teto de 200 passa a ser de produtos (stores/produto.js). Ordem dos dois diálogos virou MgSelect; o online ganhou o manterFoco do PDV.

Fábio (20/10/2026): variação e embalagem saem da grade e vão para uma janela própria, de duas colunas: à esquerda q-list das variações (foto + nome), à direita as embalagens com o preço, que acompanham a variação em que o cursor está. Enter na variação passa para a direita (com uma embalagem só, já escolhe), Enter na embalagem escolhe, Esc da direita volta para a esquerda e da esquerda fecha a janela. Uma variação só: a janela abre com o cursor na direita. Opção única sai direto da grade.

Fábio (20/10/2026): o filtro online tem que ser o do offline (o da similaridade trazia Amarelo Limão pesquisando 'tinta tecido acrilex vermelh'). Rota nova GET v1/produto-barra/pesquisa (ProdutoBarraController::pesquisa -> ProdutoBarraService::pesquisa), fora do select, com o texto de busca do catálogo do PDV e a mesma regra: 1ª palavra começa uma palavra, as outras em qualquer ponto, sem diferença de maiúscula. A rota select/produto-barra/pesquisa saiu e o SelectProdutoBarraController voltou ao que era. Eliminar o MgSelectProdutoBarra ficou na TASK-250.

AC #4, Relevância (Fábio, 21/10/2026): fica só na pesquisa online, como ordem padrão de lá. Motivo: a conta de semelhança é do banco (strict_word_similarity do pg_trgm); o PDV ordena a base local, que não tem essa conta.

AC #4, rolagem infinita (Fábio, 21/10/2026): fica só na pesquisa online. Motivo: a online pagina porque busca no servidor; o PDV já tem tudo na base local e mostra até 200 produtos de uma vez, com o aviso para refinar.

AC #4, leitor de câmera (Fábio, 21/10/2026): fica só no PDV, que roda em tablet e celular. Motivo: o campo de bipar online (MgInputProdutoBarras) hoje só aparece no cadastro do modelo de vale-compras, tela de escritório, com leitor de mão ou digitação.

AC #5 (prancheta digital, era a TASK-49) removido (Fábio, 21/10/2026): substituir as folhas impressas do balcão já é papel da Prancheta, o catálogo reduzido de produtos que existe desde 2024 (aba Prancheta no painel lateral da venda do PDV; manutenção em /prancheta). A ideia que saiu daqui, acrescentar produto na Prancheta pela pesquisa F1, foi para a TASK-208, que é da mesma tela.
<!-- SECTION:NOTES:END -->

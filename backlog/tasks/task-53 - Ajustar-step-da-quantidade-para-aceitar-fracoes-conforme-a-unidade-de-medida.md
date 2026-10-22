---
id: TASK-53
title: Ajustar step da quantidade para aceitar fracoes conforme a unidade de medida
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-21 20:54'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 131000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo, seção DESEJÁVEIS: "somente para produtos vendidos em metro, kg, etc (vincular ao cadastro de unidade de medida)".

O PDV aceitava fração em qualquer produto, e unidade, pacote, rolo etc. recebiam 1,3 ou 0,2 por erro de digitação. A unidade de medida passa a dizer quantas casas decimais a quantidade aceita: MT 2 (0,53 = 53 cm), KG 3 (0,712 = 712 g), UN 0.

Decisões do Fábio (21/10/2026):
- Fração que a unidade não aceita é recusada e avisada, nunca arredondada.
- Na devolução vale a mesma trava, mas a fração passa quando a quantidade é todo o disponível do item (venda antiga feita fracionada por engano).
- A resolução do código bipado do MgInputProdutoBarras sai da view vwProdutoBarra e vai para o domínio de produto (Eloquent + resource).

Levantamento (último ano): 930 itens fracionados em UN/PT/RL/CX. 833 são "# Uso Consumo Diversos" de NF de terceiro importada (não passam pela tela, continuam entrando). 97 foram digitados no PDV, parte erro (caderno 1,3, bloco 1,002) e parte jeito de acertar preço (Impresso Mg A4 2,2 ou 0,3, Serviço Ajuste Foto 0,3, Kit Encadernação 0,921): esse jeito acaba.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Unidade de medida tem as casas decimais da quantidade (0 a 3), editáveis no cadastro do Estoque; DDL api/database/unidademedida_decimais.sql já nasce com MT, M2 e R$ em 2 e KG, LT e TO em 3
- [x] #2 PDV recusa e avisa fração que a unidade não aceita ao bipar ou escolher na pesquisa com N*
- [x] #3 Editar item do negócio e item do vale recusam fração que a unidade não aceita
- [x] #4 Modelo de vale recusa fração no N* e no editar item; o código bipado sai da view vwProdutoBarra (rota v1/produto-barra/barras)
- [x] #5 Devolução recusa fração que a unidade não aceita, menos quando é todo o disponível do item
- [x] #6 Servidor recusa item novo com fração que a unidade não aceita na transmissão do negócio do PDV; NF de terceiro e Mercos entram como vieram
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Onde está:
- DDL: api/database/unidademedida_decimais.sql (coluna tblunidademedida.decimais smallint not null default 0). Aplicada no banco de dev em 21/10.
- API: UnidadeMedida (fillable/cast) e UnidadeMedidaController (required, 0 a 3). Catálogo do PDV (PdvCatalogoService::produto) e pesquisa em grade (ProdutoBarraService::pesquisa) trazem decimais = coalesce(embalagem, produto), igual à sigla. ValeModeloProdutoBarraResource traz sigla e decimais.
- Rota nova GET v1/produto-barra/barras/{barras} (ProdutoBarraService::barras + ProdutoBarraPesquisaResource, shape da linha da pesquisa); saiu GET v1/select/produto-barra/barras/{barras} do SelectProdutoBarraController. Adianta parte do AC #3 da TASK-250.
- Front: regra única em negocios/src/utils/quantidade.js (fracaoRecusada). Sem decimais no produto (catálogo baixado antes do campo) não trava: vale 3 casas, como antes.
- Recusa no N*: negocioStore.itemAdicionar (bipe e pesquisa do PDV) e valeModeloStore.itemAcrescentar. Rule nos dialogs: ListagemProdutos, ListagemItensVale, ValeModeloFormPage, DevolucaoPage (o Confirmar fica fora do q-form, valida antes do dialog).
- Deploy: a primeira rodada do job pdv:catalogo depois do deploy gera um diff com o catálogo inteiro (toda linha ganha o campo). É uma vez só.

Roteiro de teste:
1. Estoque > Unidades de medida: editar UN, MT, KG e conferir as casas no select e no caption da lista.
2. PDV: sincronizar o catálogo. Digitar 1,5* e bipar um produto UN: não entra, aviso "UN não aceita fração (1,5)!". 0,53* num produto MT entra; 0,535* recusa.
3. PDV: Editar item UN com 1,5: o campo mostra "UN não aceita fração" e não salva.
4. Vale compras no negócio: Editar item com fração em UN recusa.
5. Modelo de vale: 1,5* + código de produto UN recusa; código de 6 dígitos e barras continuam achando o produto (e a pesquisa F1); Editar item recusa fração.
6. Devolução de venda com item UN fracionado (ex. Impresso 0,3): devolver 0,3 (todo o disponível) passa; numa venda de 2 UN, devolver 1,5 recusa.

Servidor (pedido do Fábio em 21/10): só no adicionar item. PdvNegocioService::negocio (PUT pdv/negocio) recusa o item NOVO com fração que a unidade não aceita, pela UnidadeMedidaService::fracaoRecusada (a mesma regra do front). Item já gravado passa como está, e Fechar, devolução e modelo de vale não validam no servidor. NF de terceiro e pedido do Mercos gravam os itens por conta própria, sem passar por aí: entram com a quantidade que veio.
Deploy: PDV que ainda estiver com o front antigo e lançar fração em UN tem a transmissão recusada com o nome do item; corrige a quantidade na tela e transmite de novo.

Verificação em 21/10/2026 (dev, transação desfeita): 78 checagens na API (regra, código bipado 200/404/409, resource igual à linha da pesquisa, catálogo com decimais int, validação do cadastro 0 a 3, transmissão recusando item novo UN 1,5 e MT 0,535 e passando item já gravado), 20 na regra do front (incluindo 0,000 a 100,000 em MT e KG), compilação dos .vue alterados, eslint/prettier e php -l. As telas não foram clicadas no navegador.
<!-- SECTION:NOTES:END -->

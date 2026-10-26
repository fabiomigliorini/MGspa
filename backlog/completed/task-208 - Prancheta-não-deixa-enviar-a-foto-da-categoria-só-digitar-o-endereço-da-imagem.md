---
id: TASK-208
title: >-
  Montar a Prancheta exige digitar o endereço da foto da categoria e o código de
  barras de cada produto
status: Done
assignee: []
created_date: '2026-10-10 19:42'
updated_date: '2026-10-26 16:22'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 220000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na tela da Prancheta (negocios /prancheta), a imagem da categoria é um campo de texto onde se digita o endereço da imagem. Não existe upload: para pôr uma foto nova, alguém precisa colocar o arquivo na pasta public/categorias do app negocios e publicar uma versão, ou colar um link de site de terceiro.

Pedido do Fábio (10/10/2026): o usuário envia a imagem pela tela, ela fica salva no backend e, na sincronização da prancheta, o front baixa e guarda as imagens em cache para mostrar também offline.

Situação hoje (dev, 10/10/2026): tblpranchetacategoria.imagem é varchar(20000) com o caminho. Das 103 categorias, 99 têm imagem:
- 88 em /categorias/N.webp: arquivos estáticos em negocios/public/categorias (82 arquivos), que entram no precache do workbox junto com o build;
- 3 em https://api-mgspa.mgpapelaria.com.br/imagens/prancheta/...;
- 8 em sites externos (papelariagiga, cdnwnd...), que podem sumir a qualquer hora.

A sincronização vem de PdvPranchetaService::getPrancheta (cat.* inteiro) e vai para a store prancheta do Dexie (negocios/src/boot/db.js). O drawer offline (OfflineLeftDrawerTabPrancheta.vue:143) usa cat.imagem direto no <img>, então hoje só funciona offline o que está em /categorias.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Na tela da Prancheta o usuário escolhe um arquivo e envia a imagem da categoria, sem digitar endereço
- [ ] #2 A imagem enviada fica salva no backend e a categoria guarda a referência a ela
- [ ] #3 Na sincronização da prancheta o PDV baixa e guarda as imagens das categorias, e o drawer da prancheta mostra as imagens offline
- [ ] #4 As 99 categorias que já têm imagem continuam aparecendo (as de /categorias, as da api-mgspa e as de sites externos)
- [ ] #5 Na manutenção da Prancheta (/prancheta), o produto se acrescenta pelo campo de bipar com a pesquisa F1 (MgInputProdutoBarras, o mesmo do modelo de vale-compras), achando pelo nome e pela foto, e não só digitando o código de barras
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
A decidir antes de implementar:
- Onde guardar: reaproveitar o domínio Mg\Imagem (tblimagem, POST v1/imagem, arquivos em /opt/www/Arquivos/Imagens) com codimagem na categoria, ou pasta própria da prancheta.
- Como fazer o cache no front: blob na própria store prancheta do Dexie, ou runtime caching do workbox (Cache API) nas URLs das imagens.
- O que fazer com as 99 existentes: subir para o backend (migração), ou só manter o caminho antigo funcionando.

Relação: TASK-64 centraliza a montagem da URL de imagem num service do Mg\Imagem. Se ela sair antes, a URL da imagem da categoria passa por esse service.

Critério da pesquisa F1 veio da conversa da TASK-173 (Fábio, 21/10/2026): hoje o Acrescentar produto abre uma caixa que só aceita o código de barras (negocios/src/pages/PranchetaPage.vue:290, sProduto.buscarBarras).
<!-- SECTION:NOTES:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-10-26 16:22
---
Absorvida pela TASK-82 (decisão do Fábio em 25/10/2026): o motor de imagens (specs/SPEC-IMAGENS.md) cobre estes critérios no milestone M4 (prancheta), inclusive o cache offline e o MgInputProdutoBarras. Fechada como duplicada; nada foi implementado aqui.
---
<!-- COMMENTS:END -->

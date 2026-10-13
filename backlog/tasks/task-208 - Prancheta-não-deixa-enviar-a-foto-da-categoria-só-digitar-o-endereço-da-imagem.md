---
id: TASK-208
title: >-
  Prancheta não deixa enviar a foto da categoria, só digitar o endereço da
  imagem
status: To Do
assignee: []
created_date: '2026-10-10 19:42'
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
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
A decidir antes de implementar:
- Onde guardar: reaproveitar o domínio Mg\Imagem (tblimagem, POST v1/imagem, arquivos em /opt/www/Arquivos/Imagens) com codimagem na categoria, ou pasta própria da prancheta.
- Como fazer o cache no front: blob na própria store prancheta do Dexie, ou runtime caching do workbox (Cache API) nas URLs das imagens.
- O que fazer com as 99 existentes: subir para o backend (migração), ou só manter o caminho antigo funcionando.

Relação: TASK-64 centraliza a montagem da URL de imagem num service do Mg\Imagem. Se ela sair antes, a URL da imagem da categoria passa por esse service.
<!-- SECTION:NOTES:END -->

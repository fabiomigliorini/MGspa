---
id: TASK-64
title: Fotos de produto ainda saem do MGLara e vao sumir quando ele for desligado
status: To Do
assignee: []
created_date: '2026-09-12 15:54'
updated_date: '2026-09-24 21:59'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 64000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
As fotos de produto, marca e usuario ainda sao servidas pelo MGLara. No dia em que ele for
desligado as imagens somem do sistema inteiro, e o conserto hoje nao e um lugar so: a URL da
imagem e montada na mao em 14 pontos do backend e mais 2 do front, com DOIS enderecos
diferentes convivendo. A mesma foto sai como
`https://sistema.mgpapelaria.com.br/MGLara/public/imagens/77.jpg` num endpoint e
`https://api.mgpapelaria.com.br/imagens/77.jpg` em outro.

Origem: marcacao no codigo em api/app/Mg/Imagem/ImagemService.php:23
("// TODO: Remover isto depois que desativar o MGLara"), mais o levantamento feito em
24/09/2026 a partir de api/app/Mg/Vale/ValeModeloProdutoBarraResource.php:36-41.

## Decisoes ja tomadas

- A montagem da URL vai para UM service no dominio `Mg\Imagem`. Quem precisa de URL de imagem
  chama o service; ninguem mais concatena caminho.
- Base unica: `config(services.mglara.imagens_url)`. Ou seja, o accessor `Imagem->url`
  (que hoje aponta para o host da propria API) passa a apontar para a mesma base dos demais.
  Quando o MGLara morrer, muda-se o valor de `MGLARA_IMAGENS_URL` no .env e pronto.
- O front passa a receber a URL pronta da API. Os endpoints que hoje devolvem `codimagem`
  cru param de devolver; o negocios para de montar caminho de imagem.

## Mapa dos pontos

### Pela config `services.mglara.imagens_url` (host do MGLara)
- api/app/Mg/Vale/ValeModeloProdutoBarraResource.php:38-41 (a partir do codimagem + ".jpg")
- api/app/Mg/NotaFiscal/Resources/NotaFiscalProdutoBarraResource.php:108-112 (a partir do arquivo)
- api/app/Mg/Woo/WooProdutoService.php:481 (a partir do arquivo; o WooCommerce BAIXA essa URL,
  entao ela precisa continuar publicamente acessivel)
- api/app/Mg/Select/SelectProdutoBarraController.php:28, :136, :171 (concatenado DENTRO do SQL,
  `'...' || imagem`)

### Pelo accessor `Imagem->url`, que e `url(asset("imagens/..."))` e aponta para o host da API
(public/imagens e symlink para /opt/www/Arquivos/Imagens/)
- api/app/Mg/Imagem/Imagem.php:96-99 (a definicao)
- api/app/Mg/Produto/ProdutoResource.php:38
- api/app/Mg/Produto/ProdutoImagemResource.php:18
- api/app/Mg/Etiqueta/EtiquetaResource.php:30
- api/app/Mg/Estoque/EstoqueSaldoConferenciaService.php:327 e :333
- api/app/Mg/Marca/MarcaController.php:27 e :119
- api/app/Mg/Marca/MarcaService.php:117
- api/app/Mg/Usuario/UsuarioController.php:101
- api/app/Mg/Usuario/GrupoUsuarioController.php:91

### `url(asset(...))` escrito na mao, sem passar nem pelo model
- api/app/Mg/Marca/MarcaService.php:196 (loop em cima de um SQL cru)

### Endpoints que devolvem `codimagem` cru e empurram a montagem para o front
- api/app/Mg/Negocio/NegocioProdutoBarraResource.php:36
- api/app/Mg/Produto/ProdutoService.php:358-361 (array `imagens`, usado pelo quiosque)
- api/app/Mg/Pdv/PdvService.php:130 (sincronizacao offline do negocios)
- api/app/Mg/Pdv/PdvPranchetaService.php:42

### Front do negocios com o endereco chumbado no fonte
- negocios/src/stores/produto.js:20-26 (`urlImagem`, com o fallback `semimagem.jpg`)
- negocios/src/pages/ValeModeloFormPage.vue:24 (`SEM_IMAGEM`, a mesma string repetida)
- Consumidores do `urlImagem`: QuiosquePage, PranchetaPage, OrcamentoPage, DevolucaoPage,
  ListagemProdutos, InputBarras, OfflineLeftDrawerTabPrancheta

## Atencao ao mexer

- Trocar `codimagem` por `imagem` no payload do PDV mexe no schema do Dexie:
  negocios/src/boot/db.js:5-7 indexa `codimagem` na store `produto`. Precisa subir a versao
  do Dexie (hoje 6) e forcar ressincronizacao do offline, senao o caixa fica com base velha.
- O payload da sincronizacao offline engorda (URL inteira em vez do numero) em centenas de
  milhares de linhas de produto. Avaliar se vale mandar so o nome do arquivo.
- O `semimagem.jpg` e placeholder: o backend devolve null quando nao ha imagem, entao o front
  ainda precisa de um lugar unico para o placeholder.

## Como testar

Com tudo pronto, `grep -rn "imagens_url\|asset(\"imagens" api/app` tem que cair so no service,
e `grep -rn "urlImagem\|MGLara/public/imagens" negocios/src` tem que voltar vazio. Depois abrir
as telas: pesquisa de produto do PDV, prancheta, quiosque, vale-compra, nota fiscal, etiqueta,
conferencia de estoque, cadastro de marca e de usuario — a foto aparece em todas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Existe um unico service no dominio Mg\Imagem que monta a URL da imagem, e nenhum outro arquivo do backend concatena caminho de imagem na mao
- [ ] #2 Os 4 pontos que hoje chamam config(services.mglara.imagens_url) passam pelo service: ValeModeloProdutoBarraResource, NotaFiscalProdutoBarraResource, WooProdutoService e os 3 SQLs do SelectProdutoBarraController
- [ ] #3 O accessor Imagem->url passa a usar o service e a base unica, parando de apontar para o host da propria API (cobre ProdutoResource, ProdutoImagemResource, EtiquetaResource, EstoqueSaldoConferenciaService, MarcaController, MarcaService, UsuarioController e GrupoUsuarioController)
- [ ] #4 MarcaService::produtos() para de montar url(asset(...)) na mao no loop do SQL cru
- [ ] #5 Os endpoints que devolvem codimagem cru passam a devolver a URL pronta: NegocioProdutoBarraResource, ProdutoService::detalhe, PdvService::produto e PdvPranchetaService
- [ ] #6 O front do negocios nao tem mais endereco de imagem chumbado: produtoStore.urlImagem e o SEM_IMAGEM do ValeModeloFormPage somem e as telas usam a URL vinda da API
- [ ] #7 A versao do Dexie em negocios/src/boot/db.js foi incrementada e o offline ressincroniza, sem o caixa ficar com base velha
- [ ] #8 O workaround do MGLara em ImagemService.php:23 (model->observacoes = model->arquivo) foi removido depois do MGLara desativado
<!-- AC:END -->

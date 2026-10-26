---
id: TASK-82
title: >-
  Fotos numa pasta só, sem lixeira, sem tamanho pra listagem e sem controle de
  quem mexe
status: To Do
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-26 16:22'
labels:
  - estoque
  - api
dependencies: []
priority: low
type: feature
ordinal: 85000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
As fotos de produto, marca e usuário vivem numa pasta única com 94 mil arquivos (trava um ls), servidas por symlink público, com a URL montada à mão em 14 pontos do backend e 2 do front e dois hosts diferentes (MGLara e API). Não há lixeira usável (só inativar, e com bug), nem tamanho reduzido pra listagem (a lista carrega a foto inteira), nem controle de quem pode subir ou excluir. O upload do produto no estoque está quebrado por código (ImagemService chama ProdutoImagemService, que não existe; SlimAdapter chama read()/toJpeg(), que não existem no Intervention 4.1.2).

Task-mãe do motor de imagens. Absorve a TASK-64 (URL única num service) e a TASK-208 (foto da categoria da prancheta). Especificação completa, com as decisões, a arquitetura, os arquivos e os milestones: specs/SPEC-IMAGENS.md.

Origem: estoque/docs/BLUEPRINT_MIGRACAO_MGLARA.md — Fase C.7; levantamento e decisões com o Fábio em 25/10/2026.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 M0 infra: GD com WebP no Dockerfile, script api/database/imagem_motor.sql (uuid, tipo, extensao, largura, altura, bytes em tblimagem; codimagem em tblpranchetacategoria), disk imagem com IMAGENS_PATH, symlink api/public/imagem
- [ ] #2 M1a motor: Mg\Imagem reescrito (ImagemTipo, ImagemPreset, ImagemMotor, ImagemVinculoService, ImagemService, ImagemController, ImagemPublicaController), rotas pública e privada, comando imagem:migrar rodado em dev sobre a cópia dos 94 mil; curl -I numa URL de preset: miss gera, hit sai do nginx
- [ ] #3 M1b biblioteca no estoque (/imagem): grade de thumbs com filtros tipo, ativas, lixeira e órfãs; vínculos; Excluir, Restaurar, Esvaziar com 'há mais de N dias'; upload solto por tipo
- [ ] #4 M2 produto: MgImagemUpload (Cropper.js), MgImagem e urlImagem em @components; galeria do produto no estoque com card, Excluir (inativo), ordem e upload; principal da variação/embalagem cai pra primeira ativa
- [ ] #5 M3 marca e hierarquia (seção, família, grupo, subgrupo) com upload pelo componente; logo PNG mantém transparência
- [ ] #6 M4 prancheta: categoria com upload (sem digitar endereço), as 99 existentes migradas ou mantidas, PDV guarda as imagens offline (workbox CacheFirst em /imagem/ + aquecimento no sync), produto acrescentado pelo MgInputProdutoBarras (ex-TASK-208)
- [ ] #7 M5 consumidores: nenhum ponto do backend ou do negocios monta URL de imagem fora do service/helper (grep de imagens_url, asset("imagens, urlImagem e MGLara/public/imagens vazio); catálogo do PDV leva imagem=uuid e Dexie v12 ressincroniza; Woo recebe URL pública .jpg; Mercos lê caminhoOriginal; SlimAdapter, arquivo/observacoes e symlink public/imagens removidos (ex-TASK-64)
- [ ] #8 M6 go-live: rebuild da imagem da API, DDL, deploy, imagem:migrar em prod, deploy dos apps, location /imagem/ no api.conf do MGweb com Cache-Control e CORS, symlink velho removido
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): mandar para a lixeira já existe (POST imagem/{id}/inativo; 9.149 imagens inativas). Faltam listagem, restaurar, esvaziar e telas. O apiResource('imagem') e o DELETE imagem/{id}/inativo apontam para index/show/destroy/ativar, que não existem no ImagemController (só store, update e inativar): dão erro se chamados.

Decisões fechadas com o Fábio em 25/10/2026 (item a item; detalhe em specs/SPEC-IMAGENS.md):
1. Vínculo: as FKs existentes ficam; um ImagemVinculoService conhece a lista e responde "onde está amarrada" e "órfãs".
2. Tipo: coluna `tipo` em tblimagem, lista fixa em PHP (produto, marca, secaoproduto, familiaproduto, grupoproduto, subgrupoproduto, prancheta, usuario).
3. Arquivo: nome = uuid v4, subpastas 2+2 (ab/cd/{uuid}.{ext}).
4. Original: formato mantido (jpeg/png/webp), EXIF aplicado e removido, lado maior até 2048 px.
5. Presets fixos (thumb 160, card 400, detalhe 1024, full) e formato pela extensão da URL (.webp apps, .jpg integrações).
6. Entrega: nginx serve do cache público (symlink api/public/imagem + try_files que já existe); no miss o Laravel gera, grava e responde.
7. URL imutável (original novo = uuid novo), Cache-Control immutable 1 ano.
8. Tipo decide público/privado: produto/marca/hierarquia/prancheta públicos; usuario e futuros de pessoa privados (auth_or_signed).
9. Permissão herdada do dono, lida de tblpermissao pelo Autorizador por string (ProdutoPolicy.update, MarcaPolicy.update, …PranchetaPolicy.update); biblioteca/lixeira = ImagemPolicy.listing/update/delete.
10. Lixeira = coluna inativo: Excluir marca inativo (continua amarrada, some das telas); Restaurar limpa; Esvaziar apaga de vez, manual, com filtro "há mais de N dias".
11. Editor: MgImagemUpload em @components com Cropper.js v2 (cortar, girar, zoom, proporção); o MgSlim fica nas 5 telas atuais.
12. Migração: comando move de vez {cod}.jpg para original/ab/cd/{uuid}.jpg, sem redirect; MGLara perde as fotos.
13. Front recebe `imagem` = uuid; helper único urlImagem(uuid, preset) em @components/utils/imagem.js.

Números em 25/10/2026: 94.402 imagens, 9.149 inativas, 8.283 órfãs (848 ativas), 48.469 produtos com imagem, 162 marcas, 85 com arquivo nulo. Dev não tem os arquivos (copiar-imagens traz de prod).
<!-- SECTION:NOTES:END -->

---
id: TASK-73
title: Na tela do talhão não dá pra ver quais cargas formam o colhido
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-09-12 15:54'
updated_date: '2026-10-06 19:05'
labels:
  - agro
dependencies: []
priority: low
type: feature
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: marcacao no codigo — agro/src/pages/PlantioDetailPage.vue:340 (TODO "listar via GET v1/movimento-grao?codplantio=..."). Hoje o card "Cargas deste plantio" e um placeholder ("Em breve").

Decisao (06/10/2026): a lista vem do EXTRATO do talhao — GET v1/movimento-grao?codplantio=X&contatipo=PLANTIO — a mesma fonte do KPI "Colhido (sc)" (SafraService.php:84). Assim a lista mostra so a parte do talhao quando o romaneio e dividido entre talhoes, so romaneios finalizados, e inclui os ajustes manuais.

Destino: o eager load do movimento precisa trazer Carga.CargaPontoS para expor o rotulo do destino (CargaPontoService::rotulo).

Modelo a copiar: agro/src/components/ContratoEntregas.vue (entregas do contrato, tambem vindas do extrato).

Por que nao carga/listagem?codplantio=: o romaneio dividido aparece com o peso inteiro nos dois talhoes, aparecem romaneios ainda no patio e os ajustes manuais ficam de fora — a soma nao bateria com o Colhido.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O card "Cargas deste plantio" lista o que formou o colhido do talhão: data, placa, motorista, destino, kg e sacas da parte deste talhão; o ajuste manual aparece identificado como tal
- [ ] #2 A soma da lista é igual ao "Colhido (sc)" do topo da tela, inclusive com romaneio dividido entre dois talhões
- [ ] #3 Clicar na linha abre o romaneio (/cargas/:codcarga); o ajuste manual não tem link
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado em 06/10/2026. Na árvore, sem commit. FALTA VALIDAÇÃO na tela.

Mudança em relação à descrição: os movimentos vêm EMBUTIDOS no show do plantio (GET v1/safra/{cs}/plantio/{cp}), no padrão das Entregas do contrato, em vez de GET v1/movimento-grao. Motivos: a tela já busca o show (sem requisição nova); o movimento-grao pagina fixo em 50; e o recorte fica idêntico ao do KPI Colhido.

Arquivos:
- api/app/Mg/Fazenda/PlantioController.php: show() carrega MovimentoGraoS com o recorte do KPI (inativo null + codsafra da URL), ordem data desc, e Carga.CargaPontoS com UnidadeArmazenadora/Contrato.Pessoa/Plantio.Variedade para o rótulo do destino. index/update/hacolhido seguem sem extrato.
- api/app/Mg/Fazenda/PlantioResource.php: MovimentoGraoS mapeado (codmovimentograo, codcarga, manual, data, quantidadekg, quantidadesc, observacao, placa, motorista, destino). destino = CargaRelatorioService::rotulosDoPapel(carga, DESTINO). data formatada Y-m-d H:i:s (hora local, como MgModel::serializeDate) — o Carbon cru saía em UTC com Z.
- agro/src/components/PlantioCargas.vue (novo): card no molde do ContratoEntregas. Cabeçalho soma os kg e divide pela saca da cultura (mesma conta do KPI). Linha de carga abre /cargas/:codcarga (q-item :to, Ctrl+clique funciona); ajuste manual sem link, com a observação.
- agro/src/pages/PlantioDetailPage.vue: placeholder e TODO trocados pelo card; computed pesosaca (Safra.cultura minúsculo).

Conferido: php -l, eslint e prettier limpos. Em dev, talhão 42 (safra 2): 4 linhas, 180.135,691 kg = 3.002 sc, igual ao KPI Colhido (3.002). Talhão 135 (teste ZZ, inativo): romaneio 53048 de 989,36 kg dividido entre talhões aparece com 329,12 kg, a parte deste talhão. Data do JSON = data do banco (hora local). Screenshot headless do template real com Quasar em 1200px e 360px: linhas de carga com seta, ajuste manual roxo sem seta, destino longo quebra linha no celular.

Teste: https://sistema-dev.mgpapelaria.com.br:8088/#/safra/2/plantio/42
<!-- SECTION:NOTES:END -->

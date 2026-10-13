---
id: TASK-73
title: Na tela do talhão não dá pra ver quais cargas formam o colhido
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-12 15:54'
updated_date: '2026-10-07 13:25'
labels:
  - agro
dependencies: []
priority: low
type: feature
ordinal: 206000
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

- [x] #1 O card "Cargas deste plantio" lista o que formou o colhido do talhão: data, placa, motorista, destino, kg e sacas da parte deste talhão; o ajuste manual aparece identificado como tal
- [x] #2 A soma da lista é igual ao "Colhido (sc)" do topo da tela, inclusive com romaneio dividido entre dois talhões
- [x] #3 Clicar na linha abre o romaneio (/cargas/:codcarga); o ajuste manual não tem link
- [x] #4 A lista de plantios da safra mostra as sacas colhidas de cada talhão, por talhão e por variedade
- [x] #5 Talhão marcado como finalizado por um check (lista da safra e tela do talhão), sem informar hectares colhidos; média real = sacas colhidas / área plantada, sempre
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

07/10/2026, ajustes da validação:

- SafraDetailPage (lista Plantios por fazenda): a célula Colhido de cada linha mostra as sacas (comercial.plantios[cod].colhido, mesmo número do KPI do talhão) acima da barra de hectares. Vale nos modos Por talhão e Por variedade (mesmo template de linha). Critério #4.
- CargaDetailPage: os dois botões de voltar iam fixo para /cargas; quem vinha do card do talhão caía na listagem de romaneios. Agora usam goBack(router, { name: cargas }). utils/goBack.js passou a decidir por router.options.history.state.back (navegação interna do vue-router) em vez de window.history.length: talhão → romaneio → voltar = talhão; /cargas → romaneio → voltar = listagem; aba nova/link direto = /cargas.
  Conferido: eslint e prettier limpos.

07/10/2026, regra de colheita (pedido do usuário):

- Média real = sacas colhidas / ÁREA PLANTADA, sempre (antes: / ha colhidos, e ficava "—" sem ha informado). Vale por talhão, variedade, fazenda e safra (SafraService: plantios.realizada, fecharMedias, produtividadecolhido).
- Saiu o slider de ha colhidos. No lugar: check "Talhão finalizado?" (q-checkbox redondo) no KPI da tela do talhão (substitui o card Colheita/progresso, mesmo tamanho) e no lado direito de cada linha da lista da safra. Sem DDL: marcado grava hacolhido = área, desmarcado 0 (store.marcarFinalizado → POST .../hacolhido). PlantioResource expõe `finalizado`; PlantioService::finalizado é a regra única.
- Produção estimada (A colher / Disponível do Extrato, Expectativa da safra): finalizado = colhido real; não finalizado = previsão, ou o colhido se já passou dela. A regra de 3 saiu. A previsão (expectativasacas) só muda editando o talhão.
- Progresso da colheita / "Colheita X ha" da safra = área dos talhões finalizados. ha parcial antigo é ignorado (dev: nenhum).
- Edição do talhão (update) preserva o finalizado mesmo mudando a área.
Conferido em dev (Milho 2026): Talhão 01 finalizado, 1.587 sc / 77,3 ha = 20,53 sc/ha; safra 3.134 sc, progresso 3,7%. php -l, eslint, prettier limpos; screenshot do template real em 1200px e 360px.
<!-- SECTION:NOTES:END -->

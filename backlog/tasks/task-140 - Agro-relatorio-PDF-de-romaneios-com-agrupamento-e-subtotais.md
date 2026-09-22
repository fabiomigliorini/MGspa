---
id: TASK-140
title: 'Agro: relatorio PDF de romaneios com agrupamento e subtotais'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 12:24'
updated_date: '2026-09-22 12:41'
labels:
  - agro
dependencies:
  - TASK-136
priority: medium
type: feature
ordinal: 150000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Primeiro relatorio impresso do agro. Hoje o modulo nao tem PDF nenhum no backend (Mg\\Grao nao tem uma linha de Dompdf/Mpdf); o unico impresso e o romaneio individual, HTML + window.print client-side.

Escopo backend:
- CargaRelatorioService.php no molde de LiquidacaoTituloRelatorioService: DOIS metodos publicos, html(filtros) e pdf(filtros). O html() separado e o que permite ?html=1 no controller para ajustar milimetros sem re-renderizar PDF.
- Reusa CargaService::pesquisar(filtros, sortDoAgrupamento, null, WITH_LISTAGEM) - MESMO filtro da tela. E o que torna 'imprimir o que estou vendo' verdadeiro.
- AGRUPAMENTOS: nenhum, dia, mes, sentido, etapa, safra, cultura, unidade, plantio, contrato, pessoa, motorista, placa. Escolhido na tela.
  Cada carga entra em EXATAMENTE UM grupo, senao os totais somariam kg duplicado. Para unidade/plantio/contrato/pessoa usa o ponto do papel dominante (SAIDA -> DESTINO, senao ORIGEM), mesma regra de pontosResumo(). Carga sem aquele tipo cai em '(sem unidade)'.
- Sacas = liquido / (Safra.Cultura.pesosaca ?: 60).
- Guardas obrigatorias no html(): abort(422) se nao vier data_inicio/data_fim/codsafra (senao varre a tabela inteira), e abort(422) acima de 5000 linhas.
- Mpdf A4-L (espelha EstoqueSaldoRelatorioService, o unico paisagem existente): format A4-L, margens 8/8/16/14, header/footer 5, helvetica, tempDir storage_path('app/mpdf') com @mkdir.
- Blade api/resources/views/carga/relatorio.blade.php, sem <html>, com htmlpageheader/htmlpagefooter e {PAGENO} de {nbpg}.
  A4-L util = 281mm; layout em 276mm com table-layout fixed + colgroup (sem fixed o mPDF ignora o colgroup):
  Romaneio 15 | Data/hora 21 | Sent. 11 | Placa/carreta 26 | Motorista 32 | Origem 46 | Destino 46 | Bruto 20 | Desconto 19 | Liquido 22 | Sacas 18.
  Carreta na 2a linha da placa em cinza; etapa como span sob o romaneio so quando != FINALIZADO; cancelada com fundo #fde9e9 e numero riscado; pbt/tara fora.
  UMA tabela so (colunas alinhadas entre grupos) com tr.grupo, tr.subtotal e tr.totalgeral; o thead o mPDF repete sozinho.
- Rota GET v1/carga/relatorio + CargaController@relatorio (inline, Content-Disposition inline, ?html=1 para debug).

Escopo frontend:
- agro/src/utils/abrirPdf.js: copia literal de estoque/src/utils/abrirPdf.js (3 linhas). O alias @components ja existe no quasar.config.js do agro e services/api.js exporta { api } nomeado.
- FAB de impressao em q-page-sticky bottom-right na CargasPage + q-select 'Agrupar por' no drawer.

Criterio de aceite: o total geral do PDF bate com a barra de totais da tela.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado. Primeiro relatorio impresso do agro.

Arquivos:
- api/app/Mg/Grao/CargaRelatorioService.php (novo): html(filtros) + pdf(filtros), 13 agrupamentos, Mpdf A4-L.
- api/resources/views/carga/relatorio.blade.php (novo).
- api/app/Mg/Grao/CargaController.php: relatorio() com ?html=1 pra debug de layout.
- api/routes/api.php: GET v1/carga/relatorio.
- agro/src/utils/abrirPdf.js (novo, 3 linhas, copia do estoque).
- agro/src/stores/cargaListagem.js: imprimirRelatorio() usando params() -- os MESMOS filtros da tela.
- agro/src/components/cargas/CargasFiltrosDrawer.vue: q-select "Agrupar por" (secao Relatorio).
- agro/src/pages/CargasPage.vue: FAB de impressao.

Decisoes:
- Reusa CargaService::pesquisar/qryFiltros -- o PDF nao tem uma 2a implementacao de filtro pra divergir.
- Uma carga entra em EXATAMENTE UM grupo (nao explode por ponto), senao os kg dos subtotais somariam duplicado. Para unidade/plantio/contrato/pessoa usa o papel dominante (SAIDA -> DESTINO, senao ORIGEM), com fallback pro outro lado antes de cair em "(sem ...)".
- Guards: 422 sem periodo/safra/codcarga (senao varre a tabela inteira) e 422 acima de 5000 linhas.
- Legenda de filtros ativos no cabecalho -- sem ela a tabela impressa vira orfa e ninguem sabe de que recorte saiu.
- Colgroup em mm somando 276 dos 281 uteis do A4-L; table-layout fixed obrigatorio (sem ele o mPDF ignora o colgroup). Carreta na 2a linha da placa, etapa como span sob o romaneio, cancelada com fundo rosa e numero riscado -- foi assim que 11 colunas couberam.

Verificado no dev:
- guard sem recorte dispara certo.
- os 13 agrupamentos renderizam, com grupos == subtotais em todos.
- TOTAL GERAL do PDF = 84.306 kg, IGUAL ao totais() da tela; soma dos subtotais tambem bate.
- PDF gerado: 33KB, %PDF valido, A4 paisagem (841.89 x 595.28 pts), 1 pagina.
- Renderizado em PNG e conferido a olho: nenhuma coluna estourou a margem direita; cabecalho, legenda, grupo, subtotal, total geral e rodape {PAGENO} de {nbpg} todos no lugar.
<!-- SECTION:NOTES:END -->

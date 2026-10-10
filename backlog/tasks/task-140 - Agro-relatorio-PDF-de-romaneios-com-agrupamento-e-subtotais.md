---
id: TASK-140
title: 'Agro: relatorio PDF de romaneios com agrupamento e subtotais'
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-22 12:24'
updated_date: '2026-10-07 15:39'
labels:
  - agro
dependencies:
  - TASK-136
priority: medium
type: feature
ordinal: 4000
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

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Relatório em A4 retrato com todas as informações da listagem de cargas (Safra, Origem e Destino em colunas próprias, textos longos cortados), mantendo agrupamento e subtotais
- [x] #2 Total do PDF igual ao da tela, por tipo e sem canceladas (exceto a coluna Bruto, que no PDF mostra e soma o PBT)
<!-- AC:END -->

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

06/10/2026, a pedido do usuário: removida a exigência de período/safra/romaneio para gerar o PDF (abort 422 "Informe ao menos o período ou a safra..." em CargaRelatorioService::html). O relatório imprime qualquer recorte da tela; continua o teto de 5000 romaneios (LIMITE_LINHAS). Conferido: PDF sem período nem safra gerado em dev (93 KB, %PDF válido); a legenda só omite a linha de período.

07/10/2026, a pedido do usuário: PDF passou a espelhar a listagem de cargas da tela (CargasPage) em vez do visual do Vales Emitidos. Colunas na ordem da tela: Tipo, Data, Etapa, Safra, Placa, Motorista, Origem, Destino, Bruto, Tara, Desconto, Líquido, Sacas (saem Romaneio e carreta). Etapa com as cores do ETAPA_META; cancelada mostra 'Cancelado' em laranja no lugar da etapa; '—' cinza onde não há valor. Origem/destino/motorista quebram linha (no papel não tem tooltip).
Totais: o TOTAL GERAL único (que misturava tipos e somava canceladas) virou uma linha por tipo — Recebido/Expedido/Transferido — vinda do próprio CargaService::totais(), sem canceladas, igual à barra da tela. Subtotal de grupo também por tipo e sem canceladas.
Conferido em dev (315 romaneios, 'Todos'): total por tipo do PDF = totais() da tela; soma dos subtotais por tipo = total do tipo nos agrupamentos dia/unidade/pessoa/sentido; PDF renderizado em PNG sem coluna estourando.

07/10/2026, a pedido do usuário: relatório passou para A4 RETRATO (paisagem não agrada). As 13 colunas da tela viraram 10 sem perder campo: Tipo/Data, Etapa, Safra (coluna própria, pedido explícito), Placa/Motorista, Origem/Destino empilhados na mesma célula (2º campo em cinza menor), mais Bruto/Tara/Desconto/Líquido/Sacas. Texto longo (motorista, origem, destino, safra) cortado com reticências em PHP (mb_strimwidth — o mPDF não faz text-overflow), então toda linha tem 2 linhas de altura.
Gotcha mPDF: o <col> do colgroup sozinho NÃO segura a largura — ele estica a coluna de texto mais longo e espreme as numéricas. A largura vai também no style de cada <th>. Recorte 'Todos' agrupado por dia: 27 páginas em paisagem → 13 em retrato.

07/10/2026: Origem e Destino separados em colunas próprias (22mm cada, corte em 14 caracteres); 11 colunas em 190mm, label Desconto encurtado para 'Desc.'.

07/10/2026, a pedido do usuário: coluna 'Bruto' do PDF mostra o PBT (caminhão cheio), mantendo o nome. Subtotais e totais dessa coluna somam o PBT (decidido com o usuário), então o total geral passou a ser somado das próprias linhas (somar()) e não mais do CargaService::totais(), que não tem PBT. Conferido: qtd/desconto/líquido/sacas por tipo continuam iguais ao totais() da tela; PBT somado = sum(pbt) das ativas.

07/10/2026, a pedido do usuário: totais finais saíram da tabela de romaneios para um quadro próprio no fim (table.resumo), com títulos (Totais, Romaneios, Bruto (kg), Desconto (kg), Líquido (kg), Sacas), fonte 9pt e respiro largo; uma linha por tipo, sem canceladas; page-break-inside: avoid.
<!-- SECTION:NOTES:END -->

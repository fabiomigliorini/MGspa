---
id: TASK-176
title: Nao ha como consultar os vales compras vendidos nem imprimir a lista
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-25 16:28'
updated_date: '2026-09-26 15:27'
labels:
  - negocios
  - api
dependencies: []
priority: low
type: feature
ordinal: 190000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje o cadastro de modelos (/vale-modelo) so mostra o modelo; nao existe tela para ver os vales que foram VENDIDOS, ativos e cancelados. Origem: pedido do Fabio em 25/09/2026 durante a TASK-38.

DESENHO ACORDADO: pagina /vale-modelo/emitidos (app negocios), com filtros para procurar (vale/modelo, pessoa favorecida, situacao ativo/cancelado, periodo, etc.) e uma forma de IMPRIMIR a lista filtrada (PDF mPDF no padrao ValeModeloRelatorioService + @components/abrirPdf, com ?html=1).

DADOS: vale vendido = tblnegociovale (codvalemodelo, codpessoafavorecido, aluno, turma, valorvale, valortotal, codtitulo, inativo), cancelado = tblnegocio.codnegociostatus = 3 ou tblnegociovale.inativo preenchido (a definir na implementacao). Cada linha deve levar ao negocio (:to). Atalho a partir da listagem/formulario do modelo filtrando por ele.

ESCOPO DEFINIDO: a tela consulta somente tblnegociovale como fonte de vales vendidos, sem union com tabelas legadas. O milestone 9 da TASK-38 esta sendo executado em paralelo; depois da conversao, os vales historicos passam a aparecer nesta mesma consulta.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado endpoint paginado com filtros por vale/modelo, favorecido, situacao e periodo; relatorio mPDF com filtros aplicados e suporte `?html=1`; pagina `/vale-modelo/emitidos`, navegacao para o negocio e atalhos filtrados por modelo na listagem e no formulario.

Fonte dos vales vendidos: somente `tblnegociovale`; `tblnegocio` e consultada para situacao/data, e `tblvalemodelo`/`tblpessoa` para descricao e favorecido. Sem leitura de tabela transacional legada. Os registros convertidos pelo milestone 9 aparecerao automaticamente.

Pendente: validacao manual no navegador da listagem, filtros, atalhos, navegacao ao negocio e PDF.

Ajuste solicitado: os filtros da página de emitidos ficam na drawer esquerda, no padrão da listagem de modelos de vale.

Ajuste visual solicitado em 26/09: coluna Data inclui hora até segundos; coluna Negócio usa # com oito dígitos; Favorecido exibe tblpessoa.fantasia com o modelo na legenda; Nome exibe aluno com turma na legenda. Drawer ganhou filtro reutilizável de modelo, favorecido, faixa de valor e negócio.
<!-- SECTION:NOTES:END -->

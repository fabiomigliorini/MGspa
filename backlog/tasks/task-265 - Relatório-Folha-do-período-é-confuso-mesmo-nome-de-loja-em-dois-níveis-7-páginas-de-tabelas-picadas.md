---
id: TASK-265
title: >-
  Relatório Folha do período é confuso: mesmo nome de loja em dois níveis, 7
  páginas de tabelas picadas
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-04 19:36'
updated_date: '2026-11-16 15:19'
labels:
  - pessoas
dependencies: []
priority: medium
type: enhancement
ordinal: 287000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O PDF Relatório Folha (aba Acertos do período, pessoas /rh/:codperiodo) agrupava por forma (página por forma) → filial do cadastro → unidade de negócio, uma tabela por unidade. O mesmo nome aparecia em dois níveis ("Imperial — CNPJ" com subgrupo "Imperial"; "Subtotal Imperial" dentro da FDF), as 5 lojas Migliorini viravam 5 blocos (o Gerar Recarga separa por empresa) e o período 8 tinha 65 linhas em 31 tabelas, 7 páginas.

Decisões (04/11/2026): agrupar por empresa (filial do vínculo → tblfilial.codempresa, como BeeRecargaService::empresas); uma linha por colaborador com colunas Recarga Bee | Dinheiro | Total; sem unidade de negócio; sem Desconto Folha.

Arquivos: api/app/Mg/Rh/AcertoRelatorioFolhaPdf.php, api/resources/views/rh/acerto-relatorio-folha.blade.php.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Agrupa por empresa (Migliorini / FDF / Fazenda), não por filial
- [x] #2 Uma linha por colaborador com colunas Recarga Bee, Dinheiro e Total; acertos da mesma forma somados
- [x] #3 Unidade de negócio fora do relatório
- [ ] #4 Desconto Folha em seção própria, após quebra de página: Nome, CPF e Valor, por empresa, com total
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Teste: pessoas /rh/8 → aba Acertos → Relatório Folha. Período 8 esperado: FDF Bee 8.503,22 / Din 2.090,00; Fazenda Din 2.801,50; Migliorini Bee 11.927,59 / Din 1.571,92; geral Bee 20.430,81 / Din 6.463,42 (Bee igual ao relatório antigo). 2 páginas. Período sem acerto mostra a mensagem de vazio.

16/11/2026 — decisão do AC #4 revista: o Desconto Folha volta, em seção própria depois de uma quebra de página ("Relatório Desconto em Folha": Nome | CPF | Valor, por empresa, total por empresa e geral; valor = ABS da soma da forma F no vínculo). A primeira listagem não mudou. Período 8: Migliorini 3.060,72 + FDF 474,90 = 3.535,62 (confere com SUM(ABS(saldo)) da forma F), 3 páginas. Período sem desconto em folha não ganha a página extra.
<!-- SECTION:NOTES:END -->

---
id: TASK-265
title: >-
  Relatório Folha do período é confuso: mesmo nome de loja em dois níveis, 7
  páginas de tabelas picadas
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-04 19:36'
updated_date: '2026-11-05 21:39'
labels:
  - pessoas
dependencies: []
priority: medium
type: enhancement
ordinal: 279000
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
- [x] #4 Desconto Folha fora do relatório
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Teste: pessoas /rh/8 → aba Acertos → Relatório Folha. Período 8 esperado: FDF Bee 8.503,22 / Din 2.090,00; Fazenda Din 2.801,50; Migliorini Bee 11.927,59 / Din 1.571,92; geral Bee 20.430,81 / Din 6.463,42 (Bee igual ao relatório antigo). 2 páginas. Período sem acerto mostra a mensagem de vazio.
<!-- SECTION:NOTES:END -->

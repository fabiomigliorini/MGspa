---
id: TASK-163
title: Temporarios .tmp.pdf orfaos na pasta dos DANFEs
status: Done
assignee: []
created_date: '2026-09-23 20:37'
updated_date: '2026-09-23 20:37'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 172000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits da TASK-150 (achado B1 do revisor). Corrigido no mesmo dia.

A TASK-150 trocou o temporario de nome fixo por um unico por processo, o que resolveu a corrida — mas tirou o efeito colateral de a geracao seguinte sobrescrever o lixo da anterior. Se o Output do mPDF falhar no meio, ou o processo morrer, sobrava um {chave}.pdf.{pid}.{uniqid}.tmp.pdf para sempre. Antes era no maximo 1 orfao por nota; passou a ser um por tentativa.

Corrigido: o bloco de geracao/rename em quebraPdfDanfePaginas ficou em try/catch com unlink do temporario, como ja fazia o chamador; o retorno do rename tambem passou a ser checado. Nenhum glob/scandir le essa pasta, entao o impacto era so disco.
<!-- SECTION:DESCRIPTION:END -->

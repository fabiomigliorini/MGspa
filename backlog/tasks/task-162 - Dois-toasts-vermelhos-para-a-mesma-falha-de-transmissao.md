---
id: TASK-162
title: Dois toasts vermelhos para a mesma falha de transmissao
status: Done
assignee: []
created_date: '2026-09-23 20:36'
updated_date: '2026-09-23 20:36'
labels:
  - components
dependencies: []
priority: medium
type: bug
ordinal: 171000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits (achado M3 do revisor). Corrigido no mesmo dia.

Todo caminho de erro da transmissao mostrava DOIS toasts vermelhos: o do composable (fecharNotify, com a mensagem da SEFAZ) e o do componente ('Erro ao emitir NFe' / 'Erro ao transmitir NFe'). Valia para POST recusado, progresso 'erro', cStat recusado, teto de tempo e progresso sumido. Era pre-existente; ficou mais visivel depois da TASK-123, porque agora o segundo toast pode aparecer por cima de outra tela (o componente desmontado nao cobre o catch do emitir).

Corrigido: o composable marca o erro com  ao rejeitar, e transmitirXml faz o mesmo no erro de cStat recusado. O emitir() e o transmitirNfe() so notificam o que NAO passou pelo composable (criar XML, imprimir, abrir DANFE).

Junto: a guarda de componente desmontado passou a cobrir tambem o emit de 'criar' — antes, trocar de negocio durante o POST /criar disparava um emit que caia no negocio novo (no-op seguido de salvar desnecessario).
<!-- SECTION:DESCRIPTION:END -->

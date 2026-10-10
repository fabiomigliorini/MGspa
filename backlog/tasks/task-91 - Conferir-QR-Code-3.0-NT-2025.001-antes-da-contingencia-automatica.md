---
id: TASK-91
title: Conferir QR Code 3.0 (NT 2025.001) antes da contingencia automatica
status: Done
assignee: []
created_date: '2026-09-12 15:56'
updated_date: '2026-10-10 19:07'
labels:
  - api
dependencies: []
priority: low
type: feature
ordinal: 91000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: api/database/NFE.md — "Pontos de atenção conhecidos". Nao foi tocado. Se a SEFAZ-MT ja exigir a versao 3.00, em emissao off-line um valor divergente no QR Code faz a nota ser rejeitada na transmissao. E pre-requisito de ligar a contingencia automatica.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): a contingência automática já foi ligada (ContingenciaService, commits 50f1b1898 e d8f6fc2ff) e o off-line autoriza com o QR atual: 8.457 NFC-e tpEmis=9 em jun-jul/26, todas AUT menos 14 INU. O sped-nfe usa QR 200 para MT em produção e 300 só em homologação; quando o MT exigir o 3.00, é atualizar o vendor.
<!-- SECTION:NOTES:END -->

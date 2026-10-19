---
id: TASK-187
title: >-
  O botão de sincronizar com o servidor está fazendo o "Negócio" ser atribuído a
  você.
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-29 21:39'
updated_date: '2026-10-19 21:49'
labels:
  - negocios
dependencies: []
priority: medium
type: bug
ordinal: 200000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Botão verde ao lado do CODNEGOCIO.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Fechar venda não reaberta grava a data e o usuário de quem fechou; fechar venda reaberta mantém os dois
- [x] #2 Negócio aberto de outro PDV não sincroniza pelo PDV: o botão verde recusa e manda usar o Apropriar
- [x] #3 Servidor recusa o envio de negócio aberto de outro PDV, em vez de trocar o PDV dele
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): regra do Fábio — quando não foi reaberto, o fechar atualiza a data e o codusuario; quando foi reaberto, o fechar não mexe nesses dois. Isso já é o que o código faz (PdvNegocioService::fechar, if (!$reaberta), TASK-30 commit 65ae4d593). O sintoma da task é outro ponto: PdvNegocioService::negocioAberto (linha ~224) grava codusuario = quem enviou em todo PUT de negócio aberto não reaberto.

Esclarecimento do Fábio com o suporte (19/10/2026): o bug é que o botão verde de sincronizar, num negócio aberto de outro PDV, troca o PDV do negócio para o seu — vira um Apropriar sem confirmação, e o PDV dono perde o negócio. Não se pode sincronizar o que não é deste PDV, nem no front nem no back. Trocar o usuário para quem sincronizou está certo (o critério antigo 'não troca o usuário' saiu). Causa: PdvNegocioService::negocioAberto grava codpdv = PDV que enviou sem conferir de quem é o negócio. Fica de fora: venda reaberta (não troca o PDV, TASK-30), fechado/cancelado (negocioFechado ignora o codpdv) e negócio sem PDV (o front já o assume como deste PDV).
<!-- SECTION:NOTES:END -->

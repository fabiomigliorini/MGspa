---
id: TASK-147
title: >-
  Progresso de transmissao 'processando' orfao no Redis gruda por 1h; excecao
  pos-autorizacao vira status 'erro'
status: Done
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 16:37'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 156000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (achados U05 3/3 confirmado, U14 nao verificado). (1) NFePHPEnvioService::iniciar() (~:89) e idempotente: se o job morrer fora do try/catch (docker stop/restart manual, OOM, reboot — SIGKILL apos grace period porque queue:work e PID 1 sem handler; findOrFail fora do try), o cache fica 'processando' ate o CACHE_TTL (1h), iniciar() nao redespacha e o robo de pendentes cede a vez. Front gira ate o teto e falha com 'Tempo esgotado'; nota fica DIG/ERR por ate 1h com Emitir inerte. Fix: TTL curto (~420s = $timeout do job) enquanto status='processando' e 3600 so para terminal; ou timestamp no payload e emAndamento() ignorar 'processando' mais velho que o $timeout; implementar NFePHPEnviarJob::failed(Throwable) gravando 'erro' no cache e mover o findOrFail para dentro do try. (2) U14 saiu daqui: virou a TASK-155 (excecao depois de a SEFAZ autorizar derruba a transmissao). Esta task cobre SO o item (1).
<!-- SECTION:DESCRIPTION:END -->

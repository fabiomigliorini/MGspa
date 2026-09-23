---
id: TASK-159
title: >-
  Pior caso da transmissao passava de 20 min: consulta de recuperacao
  multiplicava o retry
status: Done
assignee: []
created_date: '2026-09-23 20:33'
updated_date: '2026-09-23 20:33'
labels:
  - api
dependencies: []
priority: high
type: bug
ordinal: 168000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits da TASK-148 (achados A1 e A3 do revisor).

A TASK-148 subiu o soaptimeout de 20 para 60 (CURLOPT_TIMEOUT = soaptimeout + 20 = 80s) e trocou a consulta de recuperacao unica por um laco com backoff de 4 tentativas. O que passou batido: consultarSemLock() tambem passa pelo chamarSefazComRetry, que faz 3 tentativas. Entao cada consulta do laco custava ate 242s e o laco inteiro ate ~968s; somado ao envio (242s), o pior caso do enviarSincrono foi de ~122s para ~1227s (20,5 min).

Isso estourava, em cascata: LOCK_TTL (300s, lock da nota expirando com o processo vivo e deixando outra operacao entrar em paralelo), $timeout do job (900s), REDIS_QUEUE_RETRY_AFTER (960s, com o failed() da TASK-147 gravando 'erro' por cima de um job ainda rodando) e o teto de 15 min do front. O TIMEOUT_SEFAZ do MgNotaFiscalAcoes (150s) tambem ficou menor que o novo pior caso de consultar/cancelar/inutilizar (242s): o axios abortava, o PHP seguia segurando o lock e o proximo clique batia em 'Outra operacao ja esta em andamento'.

Corrigido: chamarSefazComRetry ganhou parametro opcional de atrasos; as consultas de recuperacao usam tentativa unica (quem insiste e o laco, com backoff proprio) e o laco desiste no primeiro erro de comunicacao, deixando a nota para o robo. Pior caso de volta a ~340s. LOCK_TTL 300 -> 600 e TIMEOUT_SEFAZ 150s -> 290s.

ATUALIZACAO (TASK-168): o soaptimeout de 60s passou a valer so para o envio, entao o pior caso das demais operacoes voltou a ~122s e o TIMEOUT_SEFAZ voltou para 150s. O pior caso do envio ficou em ~300s (3 x 80s + 17,5s de esperas + uma consulta de 40s que encerra o laco), ainda dentro do LOCK_TTL de 600s.
<!-- SECTION:DESCRIPTION:END -->

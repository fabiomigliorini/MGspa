---
id: TASK-168
title: >-
  Limitar timeout de 60s e retry de 5xx ao envio, em vez do funil inteiro da
  SEFAZ
status: Done
assignee: []
created_date: '2026-09-23 20:54'
updated_date: '2026-09-23 20:54'
labels:
  - api
dependencies: []
priority: high
type: bug
ordinal: 177000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao de adequacao pedida pelo Fabio antes de continuar (2026-09-23). Duas inadequacoes do proprio fix da TASK-148/159, corrigidas aqui.

(1) O soaptimeout de 60 estava no instanciaTools, entao valia para TODAS as ~20 conversas com a SEFAZ. Mas o problema de 'o padrao estoura antes da resposta' e do ENVIO, que roda em job, sem ninguem esperando. Consultar/cancelar/inutilizar sao sincronos com o operador parado na frente e passaram a poder levar ate 242s — foi por isso que o TIMEOUT_SEFAZ do front precisou ir de 150s para 290s, o que era consequencia do erro, nao ganho.

(2) O retry em HTTP 5xx tambem valia para o funil inteiro, incluindo EVENTOS nao idempotentes: cancelamento, carta de correcao e eventos de MDF-e. Um 502/504 pode esconder um evento que a SEFAZ registrou; no envio isso e recuperavel (reenvio volta 204 e o enviarSincrono recupera por consulta), mas num evento o reenvio volta 573 ('duplicidade de evento'), que nao e tratado — o cancelamento fica registrado na SEFAZ e nao no nosso banco (TASK-166). Ou seja: eu ampliava essa janela justamente no cenario mais provavel, SEFAZ sobrecarregada respondendo 5xx.

Corrigido: instanciaTools recebe o timeout como parametro (padrao 20, so o enviarSincrono passa 60); ehErroTransitorioSefaz recebe a operacao e so aceita 5xx quando ela e enviaLote. Erros de transporte (7/28/35/52/56) seguem valendo para tudo, como sempre valeram. TIMEOUT_SEFAZ do front volta para 150s.

A TASK-166 (tratar 573) continua valendo por si: a janela existe desde sempre pelo codigo 28.
<!-- SECTION:DESCRIPTION:END -->

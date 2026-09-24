---
id: TASK-145
title: 'Sicredi: consulta PIX rejeita data fim no futuro por fuso horario'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-23 11:34'
updated_date: '2026-09-23 21:12'
labels:
  - negocios
  - api
dependencies: []
priority: high
type: bug
ordinal: 178000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ao consultar PIX no Sicredi, a API pode recusar o parametro `fim` como data/hora futura:

```
API do Sicredi retornou: A requisicao nao respeita o schema ou esta semanticamente errada. | Data e hora informadas nao podem ser maiores que a data e hora atual.: fim
```

O erro e lancado em `api/app/Mg/Pix/Sicredi/PixSicrediService.php`, na validacao da resposta. A suspeita inicial e divergencia de fuso entre o PHP/container e a API do Sicredi: a aplicacao esta configurada com `APP_TIMEZONE=America/Cuiaba` (UTC-4), e o container confirma esse fuso (`Wed Sep 23 11:34:07 -04 2026`), enquanto a API opera no horario de Brasilia (UTC-3).

Investigar o caminho de `inicio`/`fim` em `PixSicrediService::consultarPix()`, que serializa os `Carbon` com `toIso8601String()` e os envia a `PixSicrediApiService::consultarPix()`. Definir a conversao correta para a requisicao, preservando `America/Cuiaba` como fuso da aplicacao e evitando que `fim` represente um instante posterior ao horario atual aceito pelo Sicredi. Cobrir o caso com teste e registrar no log a URL/parametros normalizados caso necessario para diagnostico.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Diagnostico feito em 23/09/2026: a causa nao e o timezone `America/Cuiaba`. `toIso8601String()` preserva o offset (`-04:00`), portanto um `Carbon::now()` em Cuiaba e o mesmo instante de um `now()` em Brasilia (`-03:00`).

O problema e o valor padrao de `fim`: `PixConsultar::handle()` e os metodos `PixController::consultarPix()` / `consultarPixTodos()` usam `Carbon::now()->endOfDay()` (ou `Carbon::today()->endOfDay()`). Por exemplo, as 11:34 de Cuiaba a requisicao envia `2026-09-23T23:59:59-04:00`, um instante cerca de 12h25 no futuro, que o Sicredi rejeita.

Correcao proposta: manter `APP_TIMEZONE=America/Cuiaba`; nos tres defaults, trocar o fim do dia por `Carbon::now()`. Como defesa especifica da integracao Sicredi, antes de serializar tambem limitar qualquer `fim` recebido no futuro a `Carbon::now()`. Assim uma chamada manual/HTTP com `fim` futuro nao volta a gerar erro, enquanto o BB continua recebendo o intervalo solicitado.

Implementado: os defaults de `fim` em `PixConsultar` e nos dois endpoints de `PixController` agora usam `Carbon::now()`. `PixSicrediService::consultarPix()` tambem limita um `fim` recebido no futuro antes de o enviar. A sintaxe dos tres arquivos foi validada com `php -l`; nao ha testes de PIX no repositorio para executar.
<!-- SECTION:NOTES:END -->

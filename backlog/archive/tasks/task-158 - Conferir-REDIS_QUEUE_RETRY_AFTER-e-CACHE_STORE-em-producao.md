---
id: TASK-158
title: Conferir REDIS_QUEUE_RETRY_AFTER e CACHE_STORE em producao
status: To Do
assignee: []
created_date: '2026-09-23 20:19'
updated_date: '2026-09-23 21:01'
labels:
  - api
dependencies: []
priority: high
type: chore
ordinal: 167000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: hipotese A da TASK-135, nunca confirmada, e pre-condicao das TASK-147/148/149.

O config/queue.php usa retry_after = env('REDIS_QUEUE_RETRY_AFTER', 90). Em dev o .env ja traz 960, mas o valor de PRODUCAO nunca foi conferido. Se la estiver o default de 90s, a fila devolve qualquer job que passe de 90s e, com $tries = 1, um segundo worker o marca como failed antes do handle: MaxAttemptsExceededException. Foi exatamente o que gerou as 33 falhas de NFePHPResolverJob em tbljobsfailedspa (23 em 01/08/2026, 10 em 27/07/2026).

Isso anula o trabalho das tasks recentes: NFePHPEnviarJob tem $timeout 900 e NFePHPResolverJob 900, ambos maiores que 90. Com a SEFAZ lenta (ate ~4 min de envio depois da TASK-148) o job seria morto no meio, a nota ficaria sem resolucao e o cupom nao sairia.

A TASK-149 tambem depende de CACHE_STORE=redis: o ShouldBeUnique do NFePHPResolverJob usa lock de cache. Em dev esta redis; confirmar em producao (com 'file' ou 'array' o lock nao funciona entre processos).

Comandos para rodar em PRODUCAO (nao tenho acesso):

    grep -E 'REDIS_QUEUE_RETRY_AFTER|CACHE_STORE|QUEUE_CONNECTION' /caminho/da/api/.env
    docker exec <container-api> php artisan tinker --execute="echo config('queue.connections.redis.retry_after'), ' ', config('cache.default');"
    docker exec mgdb psql -U mgsis -d mgsis -c "select count(*), max(failed_at) from tbljobsfailedspa where payload::text ilike '%NFePHP%'"

Se retry_after for menor que 960: subir para 960 no .env de producao e reiniciar os workers (php artisan queue:restart). Regra: retry_after PRECISA ser maior que o maior $timeout de job (hoje 900).
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:01
---
Dobrada na TASK-123 (regra 3 do CLAUDE.md): virou o critério de aceite #5 da task-mãe, e o detalhe técnico desta descrição está preservado nas notas da TASK-123. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->

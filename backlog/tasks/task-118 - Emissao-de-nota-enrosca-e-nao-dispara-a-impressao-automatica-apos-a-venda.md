---
id: TASK-118
title: Emissao de nota enrosca e nao dispara a impressao automatica apos a venda
status: To Do
assignee: []
created_date: '2026-09-18 21:26'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 110000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
SINTOMA (relatado pelo Fabio em 18/09/2026): depois de fechar a venda a nota 'fica enroscada' e a impressao automatica nao sai. O caixa precisa mandar imprimir na mao.

A DETERMINAR ANTES DE CORRIGIR (o relato ainda e generico):
- E no PDV (negocios, NFC-e) ou na tela de notas (NF-e)?
- 'Enroscada' e (a) fica processando / nao autoriza, ou (b) autoriza e nao imprime? Sao causas diferentes, ver hipoteses abaixo.
- Acontece sempre ou as vezes? Em qual filial / PDV?
- Numero ou chave de uma nota que travou (ajuda a achar o job e o log da SEFAZ).

HIPOTESE A — a nota nao resolve (job morto pela fila)
Evidencia no banco: tbljobsfailedspa tem 33 falhas de NFePHPResolverJob, todas MaxAttemptsExceededException (23 em 01/08/2026 e 10 em 27/07/2026). Esse exception, com $tries = 1, significa exatamente que o retry_after da fila devolveu o job enquanto ele AINDA RODAVA e um segundo worker o marcou como failed. A nota fica sem resolucao e nao ha o que imprimir.
NFePHPResolverJob declara $timeout = 900 e NFePHPEnviarJob $timeout = 420, mas o proprio docblock avisa que isso e DECLARATIVO: pcntl nao esta instalado no container, entao o timeout nunca e imposto por sinal. Quem protege e o $tries = 1.
O config/queue.php usa retry_after = env(REDIS_QUEUE_RETRY_AFTER, 90) — ou seja, 90s de default. O .env de dev NAO define essa variavel e esta em QUEUE_CONNECTION=sync, entao o valor de PRODUCAO nao da pra conferir desta maquina (esta instalacao e so dev). PRIMEIRA COISA A FAZER: ver o REDIS_QUEUE_RETRY_AFTER do .env de producao. Se estiver abaixo de 900, o Resolver e morto toda vez que a SEFAZ demora — e o resolver() encadeia enviarSincrono + criar + enviarSincrono + consultar, entao demorar e normal.
Mesma familia da TASK-92 (ReprocessarPeriodoJob, $timeout = 1800 contra o mesmo retry_after). Vale resolver as duas juntas e, de quebra, decidir se instala pcntl para o timeout deixar de ser decorativo.

HIPOTESE B — autoriza mas ninguem manda imprimir
NFePHPService::imprimir() NAO e chamado em lugar nenhum depois da autorizacao: os unicos callers sao os endpoints nfe-php/{id}/imprimir (api.php:793) e nota-fiscal/{codnotafiscal}/imprimir (api.php:711). Ou seja, a impressao automatica depende do FRONT chamar a rota depois que a nota autoriza — e em negocios/src nao ha nenhuma chamada a essas rotas (so o tooltip 'Nova NFe' em IndexPage.vue:579). Confirmar como a impressao era disparada antes.
Alem disso imprimir() lanca 'Impressora nao informada!' quando o parametro vem vazio E o usuario criador da nota esta sem impressoratermica — conferir esse campo no usuario do caixa que reclamou.
A impressao em si sai por Ably (canal printing) via exec(curl) em NFePHPService.php:1268, com URL assinada de 10 minutos: se o job demorar, o link pode expirar antes de imprimir.
<!-- SECTION:DESCRIPTION:END -->

---
id: TASK-135
title: Emissao de nota enrosca e nao dispara a impressao automatica apos a venda
status: Done
assignee: []
created_date: '2026-09-18 21:26'
updated_date: '2026-09-23 20:19'
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

## RESPONDIDO pela investigacao da TASK-123 (2026-09-23)

Esta task e o mesmo bug da TASK-123, aberta antes com o relato generico. As perguntas
"a determinar" foram respondidas:

- **Onde**: PDV (negocios, NFC-e). Tambem afeta notas/contas por usarem o mesmo componente.
- **"Enroscada" e (a) ou (b)**: os DOIS, por causas diferentes, e o operador ve a mesma coisa
  (sem cupom). (a) nota nao resolve = TASK-148 (SEFAZ lenta: timeout de 40s estourava antes da
  resposta, retry caia em 204 e a consulta de recuperacao voltava 217) e TASK-147 (progresso
  orfao travando a nota por 1h). (b) autoriza e nao imprime = TASK-123 (Promise do emitir()
  nunca resolvia quando o operador trocava de negocio) e TASK-155 (excecao depois do save da
  autorizacao derruba a transmissao com a nota ja AUT).
- **Sempre ou as vezes**: as vezes — so quando a SEFAZ esta lenta, porque e isso que abre a
  janela de segundos/minutos em que tudo acima acontece.

Hipotese B do texto original esta DESCARTADA na parte "ninguem manda imprimir": quem dispara e
o front, em components/MgNotaFiscalAcoes.vue (funcao imprimir(), POST /v1/nota-fiscal/{id}/imprimir),
no passo 3 do emitir(). O problema era o passo 3 nunca ser alcancado.

Hipotese A continua valida como RISCO DE PRODUCAO e virou TASK-158: em dev o
REDIS_QUEUE_RETRY_AFTER ja esta 960, mas o valor de producao nunca foi conferido. Se la
estiver o default de 90s, todo job que passa de 90s e devolvido pela fila e morto com
MaxAttemptsExceededException ($tries = 1) — foi o que gerou as 33 falhas de NFePHPResolverJob
citadas acima, e anularia os $timeout ajustados nas TASK-147/148/149.

Fechada como duplicata: o trabalho esta nas TASK-123, 147, 148, 149, 150, 155, 156, 157 e 158.
<!-- SECTION:DESCRIPTION:END -->

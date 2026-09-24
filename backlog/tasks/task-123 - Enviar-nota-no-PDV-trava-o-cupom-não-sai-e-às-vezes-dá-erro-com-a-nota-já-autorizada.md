---
id: TASK-123
title: >-
  Enviar nota no PDV trava: o cupom não sai e às vezes dá erro com a nota já
  autorizada
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-21 15:32'
updated_date: '2026-09-23 22:04'
labels:
  - components
dependencies: []
priority: high
type: bug
ordinal: 179000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
## Sintoma (PDV / negocios)

- Toast "Transmitindo NFe / Transmitindo para a SEFAZ..." fica girando para sempre (sem virar verde nem vermelho, sem botao de fechar; so some com F5).
- O operador troca de negocio, volta, e a nota ja esta Autorizada, mas o toast segue girando.
- O cupom NAO imprime nem o DANFE abre automaticamente: o fluxo `emitir()` fica pendurado. Operador reimprime na mao.
- Usuarios/suporte: acontece quando a SEFAZ esta LENTA. Com SEFAZ rapida (~1,5s) nao acontece.

## Causa raiz (confirmada, 2026-09-23 — varredura com 10 lentes + verificacao adversarial 3/3 unanime)

**Desmontar o `MgNotaFiscalAcoes` no meio da transmissao deixa o composable num estado sem saida.**

1. `negocios/src/layouts/OfflineLayout.vue:24` usa `<router-view :key="$route.fullPath" />`: qualquer troca de `/offline/:uuid` destroi a IndexPage inteira (drawer esquerdo `:to` em `OfflineLeftDrawerTabNegocios.vue:94/140`, F2 em `IndexPage.vue:104-116`, Duplicar em `:129`). O F2 tambem derruba antes o `v-if codnegociostatus == 2 || 3` (`IndexPage.vue:515`) porque `carregarPrimeiroVazio` troca `this.negocio` antes do `router.push`. Tudo isso esta liberado durante a emissao porque `IndexPage.vue:203` chama `abrirDocumentoSeFechado()` sem `await`.
2. `components/useNotaFiscalTransmissao.js:216` `onUnmounted(pararPolling)`; `pararPolling` (:47-56) so faz `clearTimeout` + `transmitindo=false`. NAO chama `fecharNotify`, NAO resolve/rejeita a Promise de `iniciarTransmissao()` (:177-198). Se havia GET em voo que volta 'processando', a linha :159 nao reagenda. Estado absorvente: timer parado, `notif` aberto, `resolver` pendente.
3. O toast e criado com `timeout: 0` e sem `actions` (:61-68). No Quasar (`Notify.js:304`) so ha timer de dismiss com `timeout > 0`; sem actions nao ha botao (:217); a lista de notificacoes e global de modulo (:19), sobrevive ao componente. A unica alca de fechar e a closure em `notif` da instancia morta.
4. `MgNotaFiscalAcoes.vue:226` `await iniciarTransmissao()` nunca retorna → `:263` pendurado → `imprimir()`/`abrirDanfe()` (:266-270) nunca rodam, `finally` nao roda. `ListagemNotas.vue:99 await comp.emitir()` fica pendente sem excecao (console limpo).
5. Ao voltar: `carregarPeloUuid` → `recarregarDaApi` traz a nota AUT do servidor (por isso "ja esta autorizada"). O novo `MgNotaFiscalAcoes` e `compact` (`ListagemNotas.vue:211`) e `:568-570` so chama `checarEmAndamento()` fora do compact — e mesmo que chamasse, `checarEmAndamento` nao seta `resolver`, entao nunca terminaria em impressao.
6. Por que so com SEFAZ lenta: a janela e entre o 202 do POST e o GET terminal. Com ~1,5s o operador nao tem tempo de clicar; com 40s por tentativa de cURL x 3 tentativas + consulta de recuperacao (ate ~245s), tem.

Hipotese "erro de JS trava o script": NAO confirmada. Nenhum verificador achou caminho de excecao engolida; o travamento e uma Promise que nunca resolve, sem erro no console. Regressao vem de b482b2e0d (2026-08-17), quando a transmissao passou de POST sincrono (Promise sobrevivia ao unmount) para polling amarrado ao `onUnmounted`.

## Agravantes confirmados (mesmo composable, entram no mesmo fix)

- U02 — se o desmonte pega o POST /transmitir em voo, `iniciarPolling()` (:162-166) religa `transmitindo` depois do unmount e a cadeia COMPLETA numa instancia morta: imprime e abre DANFE com o operador em outro negocio; o `emit('action-completed')` cai no vazio (lista nao atualiza).
- U04 — ao desistir por 5 falhas seguidas (:144-151; ~90s de API fora ou 401), `emitir()` nao reconsulta a nota: mesmo AUT no servidor, nao imprime nem abre DANFE nem atualiza o card. O toast vermelho 'Sem conexao' aparece uma vez (o `startsWith('Sem conex')` em `MgNotaFiscalAcoes.vue:273` so evita duplicar).
- U07 — teto `MAX_POLLS=120` (~5min54s, :111-118) rejeita com 'Tempo esgotado' em vermelho; o job tem `$timeout=420s` + espera de fila, entao o teto pode estourar com o job ainda vivo.
- U08 — guard `if (timer) return` em `iniciarPolling` (:163) nao protege durante o GET em voo (`timer=null` em :109): segunda cadeia de polling, `polls` dobra. So alcancavel pelo F9 do app notas (`NotaFiscalViewPage.vue:844`), nao no PDV.
- U06 — nao existe caminho de retomada que termine em impressao (compact pula `checarEmAndamento`; `checarEmAndamento` e `transmitirNfe` nao chamam imprimir/abrirDanfe).

## Caso "so fecha e fica aguardando" (sem navegar) — investigado 2026-09-23

Relato: trava tambem quando o operador so fecha e espera. Conferido na mao tudo que mexe no `sNegocio.negocio` ou remonta o card durante a espera:
- `abrirDocumentoSeFechado()` so toca o som e chama `romaneioOuNota()`; nada navega sozinho.
- `recarregarDaApi()` (substitui o negocio inteiro) so e chamado pelos polls de PIX/PagarMe/Saurus, que vivem dentro dos dialogs (`useConsultaAutomatica`, `onUnmounted(parar)`) fechados por `fecharDialogs()` antes do `fechar()`; e pelo botao manual do DetalheNegocio.
- `BroadcastChannel` (`negocio.js:33-44`): so recarrega em OUTRA aba que esteja no mesmo uuid, do Dexie, com as mesmas keys.
- `v-for :key="nota.codnotafiscal"` + `<q-card-actions v-if="nota.emitida">`: os dois Resources devolvem `codnotafiscal` integer (cast no model) e `emitida`; troca de objeto em `onAcao` nao remonta.
- `executar()` grava 'erro' ANTES de relancar (rethrow proposital p/ tbljobsfailedspa): excecao no job NAO deixa o cache orfao. Os jobs falhos de dev (17-18/08) sao DNS de homologacao, todos com 'erro' gravado.
- Dev nao tem SEFAZ lenta (enviaLote max 3,3s em 30 dias); a evidencia esta so em producao.

Conclusao: NAO existe caminho que deixe o spinner para sempre sem desmontar o componente. O que existe sem navegacao, e casa com "SEFAZ lenta + espera", e o spinner ficar MINUTOS e terminar em vermelho sem cupom, com a nota autorizada depois pelo robo:
- Backend (TASK-148): cURL 40s x 3 tentativas + consulta de recuperacao cedo demais → job devolve `sucesso=false` (204→217) embora a SEFAZ tenha autorizado. Front: vermelho '204/217', 'Erro ao emitir', sem cupom; robo autoriza em ate 10 min.
- Fila saturada / worker morto (TASK-147/149): 'Na fila...' ou 'processando' por minutos → teto de 120 polls (~6 min) → vermelho 'Tempo esgotado' (U07).
Em ambos, se o operador se cansar e trocar de negocio no meio, cai no caso principal (spinner eterno). Ou seja: os dois relatos sao a mesma janela, com desfechos diferentes conforme o operador espera ou nao.

Como confirmar em PRODUCAO (rodar no psql de prod; sem acesso daqui):

    select c.codnotafiscal, c.operacao, c.tentativa, c.httpcode, c.cstat, left(c.xmotivo,50) xmotivo,
           c.duracaoms, c.sucesso, c.criacao
      from tblsefazcomunicacao c
     where c.codnotafiscal in (
             select codnotafiscal from tblsefazcomunicacao
              where criacao > now() - interval '30 days'
                and operacao = 'enviaLote'
                and (duracaoms > 20000 or sucesso = false or cstat in ('204','217')))
       and c.criacao > now() - interval '30 days'
     order by c.codnotafiscal, c.criacao;

Se aparecer o padrao "enviaLote ~40000ms sucesso=f → enviaLote 204 → consultaChave 217 → (10 min depois) consultaChave 100", e o U09 (TASK-148) acontecendo. Cruzar `codnotafiscal` com o horario dos relatos do suporte.

Detalhe novo (Fabio, 2026-09-23): no caso sem navegar, o card LA EM CIMA chega a mostrar a nota como Autorizada enquanto o notify segue cinza, e nada imprime. Pelo codigo, o card so vira AUT via `onAcao` (emit 'transmitir'/'consultar'), `recarregarDaApi` (polls PIX/PagarMe/Saurus, botao manual) ou BroadcastChannel; e no caminho do emit 'transmitir' o toast JA teria virado verde antes do card. Logo card e toast vieram de caminhos diferentes: a cadeia do composable morreu em silencio e o card foi atualizado por outra fonte. Ainda conferido e descartado: `codnotafiscal` integer em todos os payloads (NegocioResource/NotaFiscalResource/DetailResource, cast no model) — a `:key` nao muda; InputBarras nao navega ao bipar em negocio fechado; interceptors do axios nunca pendem (401 rejeita e abre login; timeout 15s).

Unico ponto SILENCIOSO que produz exatamente "cinza eterno + Promise pendente + console limpo" sem desmontar: excecao dentro de `finalizar()` (useNotaFiscalTransmissao.js:89-95), que roda dentro do `try` de `verificar()`: `pararPolling()` ja zerou `transmitindo`, a excecao cai no `catch` como "erro de rede" (sem log), nao reagenda, `resolver` nunca e chamado. Lido o updater do Quasar 2.19.3 (Notify.js:69-350) sem achar throw, mas nao da para provar o negativo em runtime. Endurecer e barato e entra no fix.

## Implementado (2026-09-23) — COMMITADO em f9875207d, refinado ate a TASK-168

Nao esta mais na arvore de trabalho: o fix entrou no commit f9875207d e foi ajustado
depois pelas TASK-159 a 163 e pela TASK-168. Os criterios de aceite seguem desmarcados
porque a validacao em producao (com SEFAZ lenta de verdade) ainda nao foi feita.

Decisao (Fabio): a transmissao SEMPRE vai ate o fim, independente da tela; o que muda quando o card da nota ja saiu da tela e o que se faz com o resultado — nao abrir o DANFE em cima de outro negocio. Cupom na termica imprime mesmo assim (e fisico, e do cliente que pagou).

`components/useNotaFiscalTransmissao.js`
- Sem `onUnmounted`: o polling segue ate o estado terminal; `codnotafiscal` capturado no inicio.
- `encerrar()` chama `resolver`/`rejeitar` ANTES de mexer no notify; todo `notif(...)` em try/catch com fallback de dismiss.
- Toast em andamento com botao X (handler zera `notif`); se o operador fechou, o resultado sai num toast novo.
- Teto por TEMPO (15 min) em vez de 120 polls; ao estourar, ou quando o cache de progresso sumiu (`status null`), consulta `GET /v1/nota-fiscal/{id}`: se AUT, resolve com sucesso.
- Erro de rede/API nao desiste mais: caption "Sem conexao para acompanhar, tentando de novo..." e, a cada 5 falhas seguidas, consulta a nota (AUT => sucesso).
- Guard de `iniciarPolling` por flag `pollingAtivo` (cobre o GET em voo); segunda `iniciarTransmissao` na mesma instancia rejeita em vez de sobrescrever a Promise.
- `console.warn/error` com prefixo `[transmissao NFe #cod]` em todo caminho anormal.

`components/MgNotaFiscalAcoes.vue`
- `desmontado` via `onUnmounted`; se o card saiu da tela: nao emite `action-completed` do transmitir (nao mexe na lista de outro negocio) e nao abre o DANFE. Imprimir continua.
- Removido o silenciamento `startsWith('Sem conex')` (mensagem nao existe mais).

Vale para notas/contas tambem (mesmo componente); no notas o F5 no detalhe continua retomando via `checarEmAndamento`.

## Como testar (dev, PDV negocios-dev:9900)

Forcar SEFAZ lenta: em `api/app/Mg/NFePHP/NFePHPEnvioService.php`, antes de `$res = NFePHPService::enviarSincrono($nf);` (~:125) por um `sleep(60);` temporario e `docker restart mgspa-api-worker` (o queue:work nao recarrega codigo). Tirar depois.

A. Fecha e espera: fechar venda com cartao/PIX -> toast cinza (com X) ~60s -> verde "NFe transmitida" -> cupom na termica -> DANFE abre. Card Autorizada.
B. Fecha e troca de negocio em ~5s (clique na lista / F2): toast continua cinza -> ao autorizar vira verde, cupom imprime, DANFE NAO abre; voltar ao negocio -> card Autorizada com botao DANFE.
C. Progresso quebrado: `throw new \Exception('x')` temporario no inicio de `NotaFiscalController::progressoTransmissao` -> toast "Sem conexao para acompanhar, tentando de novo..." -> em ~15s (5 falhas) consulta a nota; quando AUT -> verde + cupom. Console mostra `[transmissao NFe #...]`.
D. X no toast durante a transmissao: toast some, transmissao segue, no fim aparece toast verde novo e o cupom sai.
E. Sem sleep: NFC-e normal continua rapida (rampa 0/400/800ms), sem regressao.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Transmissão sempre termina em verde ou vermelho — nunca fica girando pra sempre — e o cupom sai
- [ ] #2 Exceção depois de a SEFAZ autorizar não derruba a transmissão: nota autorizada sai com cupom
- [ ] #3 Voltar pra uma nota que ficou transmitindo termina em impressão do cupom
- [ ] #4 Clicar em Transmitir enquanto o robô de pendentes mexe na nota não dá erro vermelho
- [ ] #5 REDIS_QUEUE_RETRY_AFTER e CACHE_STORE conferidos em produção (sem isso as correções não valem)
- [ ] #6 Trocar de negócio e voltar não abre uma segunda transmissão da mesma nota
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Detalhe técnico das tasks consolidadas (2026-09-23)

As tasks abaixo eram arquivos separados e foram dobradas nos critérios de aceite desta
task, conforme a regra 3 do CLAUDE.md. O texto original de cada uma está preservado aqui.

### TASK-155 — Excecao depois de a SEFAZ autorizar derruba a transmissao: nota fica AUT e o cupom nao sai

Origem: varredura da TASK-123 (achado U14). Ficou de fora da TASK-147, que foi fechada so com o item do progresso orfao — esta task cobre o item (2) que sobrou.

NFePHPService::vincularProtocoloAutorizacao (~:527) grava a autorizacao no banco logo no inicio ($nf->nfeautorizacao = nProt; $nf->save(), que dispara o observer e deixa a nota AUT) e SO DEPOIS faz o trabalho acessorio:
  - file_get_contents(pathNFeAssinada) — arquivo pode ter sumido do disco
  - Complements::toAuthorize() — pode lancar
  - file_put_contents(pathNFeAutorizada) — disco cheio, permissao, NFS fora
  - NFePHPMailJob::dispatch() — Redis fora

Qualquer excecao dali sobe por processarProtocolo -> enviarSincrono -> NFePHPEnvioService::executar(), cujo catch grava status 'erro' e relanca. Resultado: a nota esta autorizada na SEFAZ E no banco, mas o front recebe 'erro', nao imprime o cupom nem abre o DANFE. O card aparece Autorizado e o operador fica sem cupom — e sem poder reemitir (ja tem chave e status AUT). Acontece SEM trocar de negocio, o que casa com parte do relato do suporte.

Fix proposto (confirmar): depois do $nf->save() a autorizacao e fato consumado; o que vem depois e recuperavel (o botao Consultar e o robo revinculam o protocolo e regeram o XML). Envolver o trecho pos-save em try/catch, logar como erro alto (Log::error, para alguem olhar) e devolver true mesmo assim, em vez de derrubar a transmissao inteira. Avaliar se o XML autorizado ausente precisa de alerta proprio — ele e o documento fiscal e nao pode ficar faltando em silencio.

### TASK-156 — Retomar transmissao em andamento nunca termina em impressao do cupom

Origem: varredura da TASK-123 (achado U06, 3/3 verificadores). Nao foi resolvido pela TASK-123, que cuidou do caso de o componente ser desmontado durante a transmissao.

Nao existe caminho de RETOMADA que termine imprimindo o cupom / abrindo o DANFE:
  1. useNotaFiscalTransmissao::checarEmAndamento() reabre o toast e volta a pollar, mas NAO seta resolver/rejeitar: quando o estado terminal chega, o toast fecha em verde e para por ai — sem emit('action-completed') (o card nao atualiza) e sem imprimir/abrirDanfe.
  2. MgNotaFiscalAcoes so chama checarEmAndamento() quando !props.compact (~:568); o PDV monta o componente com compact, entao ali nao ha retomada nenhuma. O comentario explica o porque (uma listagem com 20 notas DIG/ERR viraria 20 GETs).
  3. O botao Transmitir (transmitirNfe) se anexa ao job mas tambem nao chama o passo 3 do emitir() (imprimir + abrirDanfe).

Efeito: se o operador der F5 (ou fechar a aba e voltar) no meio de uma transmissao lenta, a nota autoriza e o cupom nao sai sozinho — ele precisa reimprimir pelo botao DANFE.

Fix proposto (confirmar): extrair o passo 3 do emitir() (MgNotaFiscalAcoes ~:266-270) para uma funcao e chama-la tambem no fim de transmitirNfe() e de um checarEmAndamento() que passe a devolver a Promise terminal (setando resolver) e a emitir action-completed; no PDV, rodar checarEmAndamento() no onMounted tambem em compact, limitado as notas DIG/ERR que ja tem chave (e 1 GET por nota em transmissao, nao por linha da lista).

### TASK-157 — Usuario ainda pode esbarrar no lock da nota segurado pelo robo de pendentes

Origem: varredura da TASK-123 (achado U13). A TASK-149 reduziu a chance (dedup por nota + robo na fila 'low'), mas nao fechou o caso: o NFePHPResolverJob cede a vez quando ja ha operacao em andamento (NFePHPResolverJob ~:69), porem o inverso nao existe — se o robo pegou o lock primeiro e esta numa conversa lenta com a SEFAZ, o clique do operador em Transmitir/Consultar bate em NFePHPService::lock (~:62) e recebe 'Outra operacao ja esta em andamento. Tente novamente.' em vermelho. Com APP_DEBUG=false o Handler (~:1073) troca a mensagem por 'Server Error' 500, que nao diz nada ao operador.

Ideias (confirmar): fazer o lock esperar um pouco em vez de falhar na hora (Cache::lock()->block(n)); ou devolver 409 com mensagem propria, preservada mesmo com APP_DEBUG=false, e o front tratar com texto amigavel ('A nota esta sendo processada, aguarde'); ou o robo nao pegar o lock de nota emitida nos ultimos minutos.

### TASK-158 — Conferir REDIS_QUEUE_RETRY_AFTER e CACHE_STORE em producao

Origem: hipotese A da TASK-135, nunca confirmada, e pre-condicao das TASK-147/148/149.

O config/queue.php usa retry_after = env('REDIS_QUEUE_RETRY_AFTER', 90). Em dev o .env ja traz 960, mas o valor de PRODUCAO nunca foi conferido. Se la estiver o default de 90s, a fila devolve qualquer job que passe de 90s e, com $tries = 1, um segundo worker o marca como failed antes do handle: MaxAttemptsExceededException. Foi exatamente o que gerou as 33 falhas de NFePHPResolverJob em tbljobsfailedspa (23 em 01/08/2026, 10 em 27/07/2026).

Isso anula o trabalho das tasks recentes: NFePHPEnviarJob tem $timeout 900 e NFePHPResolverJob 900, ambos maiores que 90. Com a SEFAZ lenta (ate ~4 min de envio depois da TASK-148) o job seria morto no meio, a nota ficaria sem resolucao e o cupom nao sairia.

A TASK-149 tambem depende de CACHE_STORE=redis: o ShouldBeUnique do NFePHPResolverJob usa lock de cache. Em dev esta redis; confirmar em producao (com 'file' ou 'array' o lock nao funciona entre processos).

Comandos para rodar em PRODUCAO (nao tenho acesso):

    grep -E 'REDIS_QUEUE_RETRY_AFTER|CACHE_STORE|QUEUE_CONNECTION' /caminho/da/api/.env
    docker exec <container-api> php artisan tinker --execute="echo config('queue.connections.redis.retry_after'), ' ', config('cache.default');"
    docker exec mgdb psql -U mgsis -d mgsis -c "select count(*), max(failed_at) from tbljobsfailedspa where payload::text ilike '%NFePHP%'"

Se retry_after for menor que 960: subir para 960 no .env de producao e reiniciar os workers (php artisan queue:restart). Regra: retry_after PRECISA ser maior que o maior $timeout de job (hoje 900).

### TASK-167 — Duas instancias do componente podem acompanhar a mesma transmissao e mostrar dois toasts

Origem: revisao dos commits da TASK-123 (achado M5 do revisor).

Sem o onUnmounted, o estado do acompanhamento (pollingAtivo, notif, cod) continua sendo por instancia do componente. Cenario: o operador emite, troca de negocio (a cadeia orfa segue ate o estado terminal), volta ao negocio e clica Transmitir. A nova instancia abre a propria cadeia sobre o mesmo codnotafiscal; como o iniciar() do backend e idempotente e devolve o progresso em curso, as duas acompanham a mesma nota. Resultado: dois toasts cinza simultaneos e dois toasts de resultado (group: false nao agrupa).

Nao gera cupom duplicado (so o emitir imprime, e depois do criarXml a nota tem chave, entao o botao Emitir da lugar ao Transmitir, que nao imprime) nem transmissao duplicada (backend idempotente). E incomodo visual.

Fix possivel: mover o registro do acompanhamento para um Map em nivel de modulo, chaveado por codnotafiscal, para a segunda instancia se anexar a cadeia existente em vez de abrir outra. Casa com a TASK-156 (retomada), que precisa do mesmo registro para terminar em impressao.

## Irmãs já corrigidas deste mesmo problema (histórico, ficam como estão)

Done: TASK-135 (relato original), 146, 147, 148, 149, 150, 151, 152, 153, 159, 160, 161, 162,
163 e **168** (esta última entrou em 23/09 20:54, depois da consolidação, vinda de outra sessão:
limitou o timeout de 60s e o retry de 5xx só ao envio, em vez do funil inteiro da SEFAZ).

Separadas por serem outra funcionalidade: TASK-164 (política 'Nunca emitir' por cliente),
TASK-165 (MDF-e e DFe no app notas) e TASK-166 (cancelamento com cStat 573).
<!-- SECTION:NOTES:END -->

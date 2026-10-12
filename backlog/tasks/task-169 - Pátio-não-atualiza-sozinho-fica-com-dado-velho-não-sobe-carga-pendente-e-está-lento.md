---
id: TASK-169
title: >-
  Pátio não atualiza sozinho: fica com dado velho, não sobe carga pendente e
  está lento
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-23 21:03'
updated_date: '2026-09-29 21:05'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 178000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
SINTOMA: a balança fica com a tela do pátio aberta o dia inteiro. Nesse uso, três coisas
acontecem: a tela mostra cadastro velho sem avisar, a carga gravada offline não sobe até
alguém clicar Sincronizar, e abrir o pátio fica lento porque o sync baixa a safra inteira.

São três defeitos do MESMO mecanismo — a store de sincronização do pátio — e por isso estão
numa task só. Detalhe técnico de cada um nas notas.

Consolida as antigas TASK-106, TASK-131 e TASK-132 (arquivadas).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Falha na sincronização avisa o operador e a tela continua mostrando os cadastros já baixados
- [x] #2 Carga gravada offline sobe sozinha quando a internet volta, sem alguém clicar Sincronizar
- [x] #3 Abrir o pátio não baixa a safra inteira: o sync respeita o dia filtrado
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Detalhe técnico das tasks consolidadas (2026-09-23)

### TASK-106 — sync engole erro e deixa cadastros velhos sem aviso

Encontrado na TASK-105, 16/09/2026. CargaPage.vue e IndexPage.vue chamam store.sincronizar().catch(() => {}). sincronizacao.sincronizar() relanca todo erro que nao seja ERR_NETWORK (500, 404, erro de Dexie como chave invalida). Como carga.sincronizar() faz await sincronizacao.sincronizar() ANTES de carregarReferencias(), qualquer falha no meio do pull aborta o recarregamento: a store fica com os cadastros do mount (possivelmente vazios), o ultimaSincronizacao nunca e gravado (entao falha de novo a cada abertura) e o operador nao ve nada — nem toast, nem icone. Nao foi a causa do SILO sumido (era o TTL), mas produz exatamente o mesmo sintoma e e indistinguivel dele pela tela. Sugestao: notificar o erro (notifyError) e recarregar as referencias do Dexie mesmo quando o pull falha (o que ja foi gravado continua valido).

### TASK-131 — sem sincronização automática: carga pendente só sobe em ação manual

A sincronizacao so roda em dois momentos: onMounted da CargaPage e o botao Sincronizar. Nao ha setInterval, nao ha listener de window 'online' (o unico listener online/offline do app esta no MapaTalhoes, so para as tiles do mapa) e nao ha retry ao voltar a rede. Numa balanca que fica com a tela aberta o dia inteiro, se a internet cair e voltar as cargas gravadas no Dexie continuam pendentes ate alguem recarregar a pagina ou clicar em Sincronizar - e a store de sincronizacao ja marca online=false quando da ERR_NETWORK, entao da pra religar sozinho. Propor: listener de 'online' + heartbeat (ex.: a cada 60s) chamando sincronizar() sem force, respeitando o TTL do pull pesado.

### TASK-132 — sem dia filtrado o pull baixa a safra inteira, página por página

dataFiltro nasce null (o filtro de dia do CargaLeftDrawer comeca vazio) e puxarCargasDoDia chama puxarCargas(codsafra, null), que cai em puxarPaginasCarga({codsafra}) - varrendo TODAS as paginas de TODAS as cargas da safra a cada ciclo de sync (50 por pagina, MgModel::$perPage). Numa safra com 3.000 romaneios sao 60 requisicoes por sync, e a lista de finalizadas exibida e cortada em 30 (LIMITE_FINALIZADAS_SEM_DATA) - ou seja, quase tudo que foi baixado nem aparece. Opcoes: default do filtro = hoje; ou pull sem data limitado as ultimas N (sort -data e parar na primeira pagina); ou usar as ETAPAS_ABERTAS tambem no caso sem data.

29/09/2026 — implementado (aguardando teste na balanca):
AC1: sincronizacao.sincronizar() nao relanca mais; falha que nao e de rede grava 'erro' e avisa UMA vez (toast so na transicao ok->erro); carga.sincronizar() recarrega referencias/cargas do Dexie num finally. Telas nao engolem mais com .catch(() => {}). Estado sempre visivel num icone no header (MainLayout): cloud_done / cloud_off / sync_problem com tooltip e hora do ultimo ciclo; clique = sincronizar forcado.
AC2: composables/useSincronizacaoAutomatica.js montado no MainLayout: evento online -> sincroniza na hora; a cada 60 s; ao voltar a aba visivel. Timeout (ECONNABORTED) agora conta como rede (ehFalhaDeRede) e a carga fica pendente em vez de ganhar syncerro. Chamadas do sync com skipNotify (boot/axios.js honra) para o ciclo offline nao disparar 'Erro de conexao' todo minuto.
AC3: sem dia, puxarCargas baixa so a 1a pagina de v1/carga?sort=-data (50) + as etapas abertas; removidos definirData (sem chamadores desde a TASK-136) e o parametro codsafras.

29/09/2026 — validado com Chrome headless (DevTools Protocol) contra o agro do dev:
- AC3: abrir o patio = 1 GET v1/carga?sort=-data&page=1 + 1 GET por etapa aberta (PBT, TARA, CLASSIFICACAO, FISCAL), sem page=2+. As 'duplicadas' do DevTools sao o preflight OPTIONS do CORS.
- AC2: offline, carga salva fica pendente (sincronizado 0, sem syncerro), 70 s sem nenhum toast, icone cloud_off; ao voltar a rede subiu sozinha em ~1 s com codcarga.
- AC1: 500 simulado no saldos-unidades -> 1 toast so em 2 ciclos, icone sync_problem, safras/culturas/cargas continuam na tela; ao normalizar o erro some e o icone volta a cloud_done.
- F5 offline: no 'quasar dev' (porta 8088) nao existe service worker, entao recarregar sem rede sempre cai na pagina de erro do Chrome. Na build PWA (quasar build -m pwa, a mesma da PROD) servida em localhost, offline + F5 abriu o patio com as cargas do aparelho e o icone cloud_off.
Cargas de teste (21497, 21498) apagadas do banco dev; tokens de teste revogados.
<!-- SECTION:NOTES:END -->

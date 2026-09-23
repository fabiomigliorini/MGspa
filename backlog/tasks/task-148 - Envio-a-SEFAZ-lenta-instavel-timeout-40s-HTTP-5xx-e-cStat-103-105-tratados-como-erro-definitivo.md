---
id: TASK-148
title: >-
  Envio a SEFAZ lenta/instavel: timeout 40s, HTTP 5xx e cStat 103/105 tratados
  como erro definitivo
status: Done
assignee: []
created_date: '2026-09-23 15:48'
updated_date: '2026-09-23 16:36'
labels:
  - api
dependencies: []
priority: medium
type: bug
ordinal: 157000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (U09 1/1 verificador, sev alta; U10 2/2 confirmado; U11 nao verificado). E o lado backend do relato 'acontece quando a SEFAZ esta lenta': a nota fica nao autorizada e o robo de pendentes autoriza 10 min depois. (U09) SoapBase soaptimeout=20 + SoapCurl CONNECTTIMEOUT 20 / TIMEOUT 40 (SoapCurl.php ~:84-85): com SEFAZ lenta a 1a tentativa estoura em 40s, o retry (chamarSefazComRetry, NFePHPService.php ~:127, atrasos [500,1500]) cai em 204 Duplicidade porque a SEFAZ ja processou, a consulta de recuperacao 0,5s depois (enviarSincrono ~:419-443) volta 217 (replicacao) ou tambem estoura e e engolida (~:440-442) → job encerra 'concluido' sucesso=false, cupom nao sai. Fix: NFePHPConfigService::instanciaTools chamar $soap->timeout(60+) antes do loadSoapClass; na recuperacao trocar o usleep(500ms) unico por laco com backoff (2s, 5s, 10s) enquanto vier 217. (U10) NFePHPService::ehErroTransitorioSefaz (~:202) nao inclui 502/503/504 (nem o 89 = 500 remapeado em SoapCurl.php ~:144): sem retry e sem consulta de recuperacao, grava 'erro' embora a SEFAZ possa ter autorizado (504/502). Fix: incluir [502,503,504,89] na lista; o reenvio e seguro porque nota ja autorizada volta 204 e cai na recuperacao. (U11, verificar) SEFAZ sobrecarregada respondendo o envio sincrono com cStat 103/105 (lote recebido/em processamento, sem protNFe): enviarSincrono (~:402) trata como rejeicao raiz, descarta o recibo e nao consulta.
<!-- SECTION:DESCRIPTION:END -->

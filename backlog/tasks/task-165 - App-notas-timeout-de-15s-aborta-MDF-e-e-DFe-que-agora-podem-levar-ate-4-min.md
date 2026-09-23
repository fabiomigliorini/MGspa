---
id: TASK-165
title: 'App notas: timeout de 15s aborta MDF-e e DFe que agora podem levar ate 4 min'
status: To Do
assignee: []
created_date: '2026-09-23 20:38'
labels:
  - notas
dependencies: []
priority: medium
type: bug
ordinal: 174000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits da TASK-148 (nota lateral do revisor).

notas/src/services/api.js usa o timeout padrao de 15s e nem mdfeService.js nem dfeDistribuicaoService.js sobrescrevem. MDF-e (enviar/cancelar/encerrar/consultar) e DFe (consultar-sefaz) sao chamadas sincronas a SEFAZ: ja estouravam no browser aos 15s com o backend rodando ate 122s, e depois da TASK-148 o backend vai ate ~242s.

Efeito: o operador ve erro de timeout, o PHP continua rodando e segurando o lock, e a nova tentativa bate em 'Outra operacao ja esta em andamento'. Mesmo padrao que o MgNotaFiscalAcoes resolve com TIMEOUT_SEFAZ (hoje 290s, ajustado na TASK-159).

Fix: sobrescrever o timeout por request nessas chamadas, como o MgNotaFiscalAcoes faz. Teto do php-fpm (request_terminate_timeout) e 300s.

Junto: a rota legada sincrona GET nfe-php/{id}/enviar-sincrono (api/routes/api.php:793) pode agora passar dos 300s do request_terminate_timeout e ser morta no meio da conversa com a SEFAZ, deixando o guard do lock sem __destruct (sobra o TTL, hoje 600s). Avaliar se essa rota ainda e usada — a TASK-21 ja fala em limpar endpoints de nota-fiscal nao utilizados.
<!-- SECTION:DESCRIPTION:END -->

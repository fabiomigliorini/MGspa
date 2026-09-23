---
id: TASK-165
title: 'App notas: MDF-e e consulta de DFe cortam no meio por timeout'
status: To Do
assignee: []
created_date: '2026-09-23 20:38'
updated_date: '2026-09-23 21:02'
labels:
  - notas
dependencies: []
priority: medium
type: bug
ordinal: 174000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits da TASK-148 (nota lateral do revisor); justificativa ajustada depois da TASK-168.

notas/src/services/api.js usa o timeout padrao de 15s e nem mdfeService.js nem dfeDistribuicaoService.js sobrescrevem. MDF-e (enviar/cancelar/encerrar/consultar) e DFe (consultar-sefaz) sao chamadas sincronas a SEFAZ: estouram no browser aos 15s com o backend rodando ate ~122s (3 tentativas de 40s). NOTA: a TASK-168 devolveu o soaptimeout dessas operacoes para 20s — so o envio de NFe usa 60s — entao o pior caso do MDF-e/DFe NAO piorou; o problema e pre-existente e continua valendo.

Efeito: o operador ve erro de timeout, o PHP continua rodando e segurando o lock, e a nova tentativa bate em 'Outra operacao ja esta em andamento'. Mesmo padrao que o MgNotaFiscalAcoes resolve com TIMEOUT_SEFAZ (150s).

Fix: sobrescrever o timeout por request nessas chamadas, como o MgNotaFiscalAcoes faz. Teto do php-fpm (request_terminate_timeout) e 300s.

Junto: a rota legada sincrona GET nfe-php/{id}/enviar-sincrono (api/routes/api.php:793) pode agora passar dos 300s do request_terminate_timeout e ser morta no meio da conversa com a SEFAZ, deixando o guard do lock sem __destruct (sobra o TTL, hoje 600s). Avaliar se essa rota ainda e usada — a TASK-21 ja fala em limpar endpoints de nota-fiscal nao utilizados.
<!-- SECTION:DESCRIPTION:END -->

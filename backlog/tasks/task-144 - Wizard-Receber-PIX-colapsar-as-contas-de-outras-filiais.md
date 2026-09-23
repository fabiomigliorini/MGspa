---
id: TASK-144
title: 'Wizard Receber PIX: lista de portadores longa demais'
status: To Do
assignee:
  - '@fabio'
created_date: '2026-09-22 20:30'
updated_date: '2026-09-23 13:55'
labels:
  - negocios
dependencies: []
priority: medium
type: enhancement
ordinal: 154000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na etapa QR Code do PIX a lista traz todos os portadores, de todas as filiais. A 1a tentativa (22/09, commit 0932885d) deixava visiveis so o Sicredi da empresa mae e o BB da filial do negocio, com uma linha 'Selecione portador de outra filial' abrindo a etapa 'outras'. RECUSADA na validacao junto com a do cartao (TASK-142): esconder opcao nao foi aceito. FormaPix.vue foi revertido para o estado de b52e2fa5 - volta a listar tudo, com as contas da filial do negocio primeiro e os cabecalhos 'Da filial' / 'Outras filiais' (TASK-124).

Se a lista voltar a incomodar, a saida NAO e esconder: seguir o padrao da TASK-142 (destacar as contas do dia a dia no topo, com tecla baixa, e as demais abaixo de um cabecalho, todas visiveis). Nao reabrir a sub-etapa.
<!-- SECTION:DESCRIPTION:END -->

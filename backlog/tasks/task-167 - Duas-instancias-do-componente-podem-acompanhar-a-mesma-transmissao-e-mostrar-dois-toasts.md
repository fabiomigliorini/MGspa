---
id: TASK-167
title: >-
  Duas instancias do componente podem acompanhar a mesma transmissao e mostrar
  dois toasts
status: To Do
assignee: []
created_date: '2026-09-23 20:38'
labels:
  - components
dependencies: []
priority: low
type: bug
ordinal: 176000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits da TASK-123 (achado M5 do revisor).

Sem o onUnmounted, o estado do acompanhamento (pollingAtivo, notif, cod) continua sendo por instancia do componente. Cenario: o operador emite, troca de negocio (a cadeia orfa segue ate o estado terminal), volta ao negocio e clica Transmitir. A nova instancia abre a propria cadeia sobre o mesmo codnotafiscal; como o iniciar() do backend e idempotente e devolve o progresso em curso, as duas acompanham a mesma nota. Resultado: dois toasts cinza simultaneos e dois toasts de resultado (group: false nao agrupa).

Nao gera cupom duplicado (so o emitir imprime, e depois do criarXml a nota tem chave, entao o botao Emitir da lugar ao Transmitir, que nao imprime) nem transmissao duplicada (backend idempotente). E incomodo visual.

Fix possivel: mover o registro do acompanhamento para um Map em nivel de modulo, chaveado por codnotafiscal, para a segunda instancia se anexar a cadeia existente em vez de abrir outra. Casa com a TASK-156 (retomada), que precisa do mesmo registro para terminar em impressao.
<!-- SECTION:DESCRIPTION:END -->

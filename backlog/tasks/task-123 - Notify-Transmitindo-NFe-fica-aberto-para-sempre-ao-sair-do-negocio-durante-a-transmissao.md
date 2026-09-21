---
id: TASK-123
title: >-
  Notify 'Transmitindo NFe' fica aberto para sempre ao sair do negocio durante a
  transmissao
status: To Do
assignee: []
created_date: '2026-09-21 15:32'
labels:
  - components
dependencies: []
priority: high
type: bug
ordinal: 115000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Visto no PDV (negocios): o toast 'Transmitindo NFe / Transmitindo para a SEFAZ...' as vezes fica preso na tela. A gente navega para outros negocios, volta no negocio em questao e a nota ja esta Autorizada, mas o toast continua girando.

Suspeita (nao confirmada): em components/useNotaFiscalTransmissao.js o Notify e criado com timeout: 0 e o onUnmounted chama so pararPolling(), que para o timer mas nunca fecha o Notify (fecharNotify nao e chamado) nem resolve/rejeita a Promise de iniciarTransmissao(). Ao trocar de negocio o MgNotaFiscalAcoes desmonta no meio da transmissao e o toast fica orfao, sem ninguem para atualiza-lo. Ao voltar, checarEmAndamento() ja nao ve status 'processando' e nao reabre nada.

Segundo sintoma (o mais grave): com isso o fluxo do PDV fica "travado" - depois da transmissao nao abre o dialog do cupom nem imprime automaticamente, como era esperado. Casa com a suspeita acima: o ListagemNotas.vue faz `await comp.emitir()` (criar -> transmitir -> DANFE), e se a Promise de iniciarTransmissao() nunca resolve, o passo seguinte (abrir/imprimir o cupom) nunca roda.

Prioridade High: problema de usabilidade recorrente para os operadores do caixa, que ficam sem o cupom e precisam reimprimir na mao.

Planejar o fix antes de implementar (ex.: fechar/dispensar o Notify no desmonte, ou manter o acompanhamento vivo fora do componente). Afeta todos os apps que usam MgNotaFiscalAcoes.
<!-- SECTION:DESCRIPTION:END -->

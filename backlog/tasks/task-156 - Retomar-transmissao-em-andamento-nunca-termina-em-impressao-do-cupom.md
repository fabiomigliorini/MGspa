---
id: TASK-156
title: Retomar transmissao em andamento nunca termina em impressao do cupom
status: To Do
assignee: []
created_date: '2026-09-23 20:18'
labels:
  - components
dependencies: []
priority: medium
type: bug
ordinal: 165000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (achado U06, 3/3 verificadores). Nao foi resolvido pela TASK-123, que cuidou do caso de o componente ser desmontado durante a transmissao.

Nao existe caminho de RETOMADA que termine imprimindo o cupom / abrindo o DANFE:
  1. useNotaFiscalTransmissao::checarEmAndamento() reabre o toast e volta a pollar, mas NAO seta resolver/rejeitar: quando o estado terminal chega, o toast fecha em verde e para por ai — sem emit('action-completed') (o card nao atualiza) e sem imprimir/abrirDanfe.
  2. MgNotaFiscalAcoes so chama checarEmAndamento() quando !props.compact (~:568); o PDV monta o componente com compact, entao ali nao ha retomada nenhuma. O comentario explica o porque (uma listagem com 20 notas DIG/ERR viraria 20 GETs).
  3. O botao Transmitir (transmitirNfe) se anexa ao job mas tambem nao chama o passo 3 do emitir() (imprimir + abrirDanfe).

Efeito: se o operador der F5 (ou fechar a aba e voltar) no meio de uma transmissao lenta, a nota autoriza e o cupom nao sai sozinho — ele precisa reimprimir pelo botao DANFE.

Fix proposto (confirmar): extrair o passo 3 do emitir() (MgNotaFiscalAcoes ~:266-270) para uma funcao e chama-la tambem no fim de transmitirNfe() e de um checarEmAndamento() que passe a devolver a Promise terminal (setando resolver) e a emitir action-completed; no PDV, rodar checarEmAndamento() no onMounted tambem em compact, limitado as notas DIG/ERR que ja tem chave (e 1 GET por nota em transmissao, nao por linha da lista).
<!-- SECTION:DESCRIPTION:END -->

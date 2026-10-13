---
id: TASK-224
title: Venda paga por PIX ou cartão não fecha sozinha e o cupom não sai
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-13 22:53'
updated_date: '2026-10-13 22:53'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 235000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato do Fábio (13/10/2026), depois do go-live de 12/10: na cobrança PIX com o dialog aberto, a consulta automática recebe CONCLUIDA, o dialog fecha, mas a venda não fecha nem emite o cupom. Só fecha ao abrir o dialog de novo e clicar Consultar (ou F3). O mesmo acontece no cartão (Stone/PagarMe e SafraPay/Saurus).

Causa: desde c12254f04 (TASK-188 M6.1) os stores da cobrança (components/stores/pixStore.js, pagarMeStore.js, saurusStore.js) avisam a venda pelo avisar('cobrancaAtualizada') de components/cobranca/eventos.js, que dispara os ouvintes sem esperar. O await do atualizarXxx termina antes do recarregarDaApi; o dialog emite cobrancaConcluida -> pagamentoAdicionado e o IndexPage vê o saldo antigo (> 0) e não chama fechar(). Antes (55043d39c) o store do negócio esperava o recarregarDaApi direto. O backend estava certo: a resposta da consulta já traz o codpagamento, e venda de PDV não fecha no servidor de propósito (NegocioService::fecharSePago).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Pagamento PIX confirmado com o dialog aberto fecha a venda e emite o cupom sozinho, quando quita o saldo
- [ ] #2 Pagamento no cartão (Stone/PagarMe e SafraPay) confirmado com o dialog aberto fecha a venda e emite o cupom sozinho, quando quita o saldo
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Correção: components/cobranca/eventos.js — avisar() devolve Promise.all dos ouvintes; pixStore.atualizarPixCobNegocio, pagarMeStore.atualizarPagarMePedido e saurusStore.atualizarSaurusPedido fazem await avisar('cobrancaAtualizada'). Volta a ordem de antes do go-live: consulta -> recarrega o negócio -> o dialog vê pago -> pagamentoAdicionado -> IndexPage vê saldo 0 e fecha. Sem mudança no backend.

Teste: no PDV, venda pequena, Receber -> PIX QR Code, pagar com o dialog aberto: ao confirmar, o dialog fecha, a venda fecha e o cupom abre sozinho. Repetir com Stone/PagarMe e SafraPay. Pagamento parcial: o dialog fecha, a venda segue aberta e o saldo pisca.
<!-- SECTION:NOTES:END -->

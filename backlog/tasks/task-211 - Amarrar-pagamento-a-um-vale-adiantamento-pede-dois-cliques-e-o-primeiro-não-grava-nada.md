---
id: TASK-211
title: >-
  Amarrar pagamento a um vale/adiantamento pede dois cliques e o primeiro não
  grava nada
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 20:19'
updated_date: '2026-10-10 20:19'
labels:
  - components
dependencies: []
priority: low
type: bug
ordinal: 223000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Em Pagamentos não resolvidos, o botão de lançar como vale / adiantamento abre o diálogo com o pagamento já escolhido. O botão Amarrar só põe o pagamento na tela (campos ficam cinza, aparece a linha do PIX com o X) e troca para Gravar: quem cancela depois do Amarrar acha que amarrou e nada foi gravado. Os dois passos (Receber/Pagar e depois Gravar) só fazem sentido no lançamento novo, em que o dinheiro é cobrado no meio (QR do PIX, maquineta).

Causa: components/MgAdiantamentoDialog.vue, cobrar() no modo amarrar só chamava sBaixa.adicionar() com o pagamento existente, sem chamar o servidor; quem gravava era o botão Gravar.

Teste: negócios ou contas > Pagamentos não resolvidos > numa entrada (PIX), Lançar como vale / adiantamento. O diálogo mostra 'Amarrando o pagamento PIX #… (livre R$ …)'; Amarrar grava direto e fecha; o pagamento some (ou diminui o livre) da lista. Com erro do servidor, os campos voltam a ser editáveis e o Amarrar continua. O lançamento novo (Vale / Adiantamento sem pagamento) continua Receber/Pagar → wizard → Gravar.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Amarrar grava o título e a amarração num clique só
- [x] #2 O pagamento que está sendo amarrado aparece ao abrir o diálogo, sem o X de trocar
- [x] #3 Erro ao gravar devolve o formulário editável, com o botão Amarrar
<!-- AC:END -->

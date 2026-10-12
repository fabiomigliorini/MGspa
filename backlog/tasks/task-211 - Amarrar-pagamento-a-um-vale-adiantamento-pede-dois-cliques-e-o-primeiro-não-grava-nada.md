---
id: TASK-211
title: >-
  Vale/adiantamento e Receber/Pagar títulos pedem dois cliques para gravar:
  confirma a forma e ainda tem o Gravar
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 20:19'
updated_date: '2026-10-10 21:11'
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
- [x] #4 Lançamento novo grava ao confirmar a forma no wizard, sem o passo do Gravar; PIX e cartão integrado gravam quando o banco/maquineta confirma
- [x] #5 Erro ao gravar o lançamento novo volta o formulário para editar; só o PIX/cartão integrado já confirmado fica com o Gravar para tentar de novo
- [x] #6 Receber/Pagar títulos (contas e Receber Título do PDV) grava ao confirmar a forma no wizard, sem parar no diálogo com o Gravar; erro volta a escolher a forma
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
10/10/2026: o lançamento manual também pedia o Gravar depois de confirmar no wizard. Vinha do 77c47309b (TASK-188), que trocou o 'grava sozinho quando fecha o valor' pelo passo 'a forma aparece → Gravar'. Volta a gravar sozinho: um watch na forma da baixa grava quando ela chega (wizard, confirmação da integrada ou o pagamento do Amarrar). O X de trocar a forma sai: a forma só fica na tela enquanto grava. Receber/Pagar títulos (MgBaixaTitulos) tinha o mesmo passo: depois do wizard parava no diálogo com a forma e o Gravar. Agora o mesmo watch na forma grava quando ela vem do wizard (ou da integrada confirmada); encontro de contas e amarrar pagamento, que não passam pelo wizard, seguem no Gravar. Erro ao gravar tira a forma (menos a integrada já confirmada) e o X de trocar a forma sai.

Teste: contas > Receber títulos (ou PDV > Receber Título) > selecionar títulos > FAB > Receber > Dinheiro > confirmar: grava e fecha o diálogo.
<!-- SECTION:NOTES:END -->

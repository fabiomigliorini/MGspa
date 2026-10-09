---
id: TASK-188
title: >-
  O pagamento é guardado de três jeitos (venda, liquidação, transferência) e
  juros, multa e desconto ficam em linhas separadas
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-30 02:24'
updated_date: '2026-10-08 21:55'
labels:
  - contas
  - negocios
  - api
dependencies:
  - TASK-186
documentation:
  - backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md
  - backlog/docs/doc-4 - Refatoração-das-telas-do-dinheiro-portador-e-período.md
priority: high
type: chore
ordinal: 201000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Fundação do fechamento de caixa (TASK-39): um jeito só de guardar como o dinheiro se moveu. Hoje a forma de pagamento da venda, a liquidação de títulos e a transferência entre portadores são três registros diferentes, e juros, multa e desconto de uma baixa ficam em linhas separadas da baixa.

Desenho, decisões e o registro da execução de cada etapa: doc-3 (Plano do fechamento de caixa por milestones). Telas do caixa e da maquineta: doc-4.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Na baixa de título, juros, multa e desconto ficam na mesma linha do principal, e o histórico antigo aparece convertido com os mesmos saldos e totais
- [x] #2 Cada portador tem um tipo (espécie, banco, adquirente, cartão da empresa, outros) e cada PDV de caixa aponta para a sua gaveta
- [x] #3 As maquinetas ficam num cadastro só no contas, e o PDV escolhe a maquineta da lista em vez de digitar o serial
- [x] #4 A venda grava pagamentos e parcelas no formato novo, com nota fiscal, DIMP, romaneio e Totais de Caixa iguais aos de antes
- [x] #5 A liquidação virou pagamento: encontro de contas, acerto de RH, boleto BB e o histórico continuam funcionando
- [x] #6 Lançar no período por um botão só, que abre a lista do que fazer, cada opção no seu wizard
- [x] #7 O período do caixa unifica com o anterior pendente mesmo com diferença de contagem
- [x] #8 No PDV, receber funciona em todas as formas; na venda a prazo dá para ajustar o vencimento e o valor de cada parcela, e a venda para o fechamento mensal vence no último dia útil do mês seguinte
- [x] #9 Receber e pagar título no contas e no PDV pelo mesmo wizard (cartão, PIX, transferência, cofre, cheque, compensação), com uma listagem só de pagamentos nos dois apps
- [x] #10 No PDV o caixa recebe notinha e o gerente paga vale ou crédito do cliente, em dinheiro ou desfazendo no cartão ou no PIX
- [x] #11 Cheque só com o nome do emitente, sem CPF/CNPJ, é aceito na venda e no recebimento de título
- [x] #12 Vale e adiantamento lançados no PDV e no contas, e qualquer título de crédito do cliente paga uma compra no PDV
- [x] #13 A lista de tipos de título tem só os 14 que se usam, a receber e a pagar separados
- [x] #14 O caixa do PDV abre, conta, faz sangria e reforço, lança o borderô da maquineta de parceiro e imprime o borderô; quem fecha é o gerente, no contas
- [x] #15 Venda e recebimento em dinheiro caem no caixa aberto do PDV (sem caixa aberto, o Dinheiro fica bloqueado com o motivo) e cancelar tira do caixa
- [ ] #16 Só Caixa da filial, Gerente ou Administrador recebe em dinheiro (não feito: hoje basta o caixa estar aberto)
- [x] #17 O gerente confere o cartão de cada maquineta com o borderô na tela da maquineta e seus períodos
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Registro da execução no doc-3, seção "Registro da execução da TASK-188".
<!-- SECTION:NOTES:END -->

---
id: TASK-188
title: >-
  O pagamento é guardado de três jeitos (venda, liquidação, transferência) e
  juros, multa e desconto ficam em linhas separadas
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-30 02:24'
updated_date: '2026-10-10 01:22'
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
- [ ] #18 Uma baixa de título (e um vale/adiantamento) = um pagamento: o wizard vem com o valor travado (só o dinheiro calcula troco); o pagamento guarda só o dinheiro e juros, multa e desconto ficam nos movimentos dos títulos
- [ ] #19 Baixa trava os títulos e reconfere o saldo: título repetido ou duas baixas ao mesmo tempo não passam; valor com mais de 2 casas é recusado
- [ ] #20 Encontro de contas (títulos que se anulam) e compensação gravam no portador Encontro de Contas (antigo Programação Pagamentos), também no PDV; permissão pelo papel nesse portador
- [ ] #21 Forma 'Já recebido' no wizard (só online): amarra um pagamento sem amarração que tenha saldo livre; o título baixa na data do pagamento
- [ ] #22 PDV: Receber Título vem com a maquininha e a conta PIX padrão do PDV
- [ ] #23 Desamarrar (todos os títulos ou um) estorna as baixas: os títulos reabrem e o pagamento continua, sem amarração, em Pagamentos não resolvidos
- [ ] #24 Cancelar só o pagamento manual já desamarrado (sai do razão, cancela o cheque a repassar); integrado (PIX, maquineta, Stone/SafraPay) e boleto nunca se cancelam
- [ ] #25 Lápis do pagamento: pessoa e observação; a data só do manual; meio e portador não mudam (o servidor recusa)
- [ ] #26 Detalhe do pagamento mostra as amarrações com o histórico (título desamarrado riscado), o saldo, o amarrado e o livre
- [ ] #27 Cancelar a venda cancela só o pagamento manual; PIX e cartão integrado ficam efetivados e saem da venda (órfãos, em não resolvidos), com a mudança na auditoria
- [ ] #28 'Já recebido' na venda do PDV amarra o pagamento sem amarração inteiro na venda aberta, se couber no que falta; maior que a venda é recusado com a orientação de amarrar o excedente como adiantamento
- [ ] #29 Tela Pagamentos não resolvidos (menu do contas e botão em Pagamentos do PDV): lista o que não bate (pago − devolvido ≠ amarrado), só dos portadores em que o usuário tem papel
- [ ] #30 Não resolvido: amarrar a títulos (abre o Receber Título com o pagamento), lançar como vale/adiantamento, 'já lançado' (fica o integrado, o digitado é cancelado como indevido e as amarrações passam) e devolver PIX/cartão
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Registro da execução no doc-3, seção "Registro da execução da TASK-188".
<!-- SECTION:NOTES:END -->

---
id: TASK-194
title: >-
  Datas da venda e do estoque misturam quando o fato aconteceu com quando foi
  digitado
status: To Do
assignee: []
created_date: '2026-10-02 20:54'
updated_date: '2026-10-19 23:19'
labels:
  - negocios
  - estoque
  - api
dependencies: []
priority: medium
type: chore
ordinal: 207000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Padrão adotado no M10 do fechamento de caixa (doc-3, decidido com o Fábio em 02/10/2026): a coluna `transacao` guarda a data e hora do FATO GERADOR (o PIX de ontem lançado hoje tem transação de ontem; o entregador que volta hoje com os cartões passados ontem: transação de ontem) e `criacao` guarda quando foi digitado. O M10 renomeia pagamento, cheque, extrato bancário e bonificação; o razão nasce com `transacao`; título e movimento de título já usam.

Fica de fora do M10, por ser usado em muitos lugares: `tblnegocio.lancamento` — API (dezenas de arquivos), vwnegocio/vwnegocio_listagem, MGsis (NFe de Terceiros), MG Lara (Totais de Caixa), Dexie do PDV (ordenação e sync do negócio). Também: o pagamento da venda nasce com a hora em que o PDV sincronizou, não com a do negócio; e no PDV o recebimento fora da venda (Receber título, entrega paga na volta) não deixa informar a data do fato — só o contas tem o campo.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Venda grava a data e hora do fato em transacao (tblnegocio.lancamento renomeado em API, views, MGsis, MG Lara e PDV)
- [ ] #2 Levantamento no estoque e no resto do sistema de toda data de fato gerador com outro nome, e renomeadas para transacao
- [ ] #3 Pagamento da venda fica com a mesma transacao do negocio, nao a hora do sync
- [x] #4 No PDV, receber titulo e entrega paga na volta deixam informar a data e hora em que o pagamento aconteceu
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): #4 feito pela TASK-204 (783933f7b): o Receber Título do PDV tem data com hora (PagamentoTituloService.php:136), e a entrega paga na volta é um título de Entrega recebido ali. #3 só vale na venda reaberta (TASK-30, 65ae4d593, PdvNegocioPagamentoService.php:140-143); na venda normal a transacao do pagamento continua sendo a hora do sync (PagamentoService.php:151-152).

Revisão com o Fábio (19/10/2026) — arquivada: o comportamento que importa já acontece. PIX ou cartão passado ontem, venda esquecida aberta e fechada hoje: o pagamento fica com a data de ontem e o negócio com a de hoje.

- Negócio: o servidor grava lancamento = hora do Fechar (api/app/Mg/Pdv/PdvNegocioService.php:498).
- PIX QR da venda, Stone e Safrapay: o pagamento nasce já efetivado quando o banco ou a maquininha confirma, com transacao = hora da confirmação (PixService.php:315, PagarMeService.php:367, SaurusService.php:361, caindo em PagamentoService.php:151). O Fechar não mexe no que já está efetivado (PagamentoService.php:190).
- Cartão lançado à mão: o PDV manda o negócio ao servidor a cada alteração (negocios/src/stores/negocio.js:971), e o servidor grava transacao = hora em que recebeu o pagamento. O Fechar só põe a efetivação na data do fechamento (PdvNegocioService.php:530); a transacao fica a de ontem.

Onde a data do pagamento vira a de hoje (ficam sem fazer, assim como o rename de #1 e #2):
- PDV offline ontem: o cartão à mão só chega hoje e a transacao é a hora do sync (#3).
- Confirmação atrasada no PIX ou na maquininha: a transacao é a hora em que o servidor processou, não o horário do PIX (pix.horario) nem o da transação da Stone (PagarMePagamento.transacao). Normalmente são segundos.

Levantamento só por leitura de código, sem teste.
<!-- SECTION:NOTES:END -->

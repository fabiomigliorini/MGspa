---
id: TASK-194
title: >-
  Datas da venda e do estoque misturam quando o fato aconteceu com quando foi
  digitado
status: To Do
assignee: []
created_date: '2026-10-02 20:54'
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
- [ ] #4 No PDV, receber titulo e entrega paga na volta deixam informar a data e hora em que o pagamento aconteceu
<!-- AC:END -->

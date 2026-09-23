---
id: TASK-164
title: 'Confirmar: cliente com ''Nunca emitir'' agora recebe NFC-e em venda no cartao'
status: To Do
assignee: []
created_date: '2026-09-23 20:38'
labels:
  - negocios
dependencies: []
priority: high
type: spike
ordinal: 173000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: revisao dos commits da TASK-151 (achado B3 do revisor). PRECISA DE DECISAO DO FABIO antes de ir para producao.

A TASK-151 corrigiu o await no db.pessoa.get do romaneioOuNotaVenda (IndexPage.vue ~:419). Antes, p era uma Promise, p.notafiscal era sempre undefined e TODO cliente caia no default. Com a correcao, os cases passaram a executar de fato — inclusive o case 9, 'Nunca Emitir', que vai para romaneioOuNotaVendaPadrao(65) e, quando o pagamento e cartao/PIX/boleto, EMITE NFC-e.

Ou seja: cliente cadastrado como 'Nunca emitir' pagando no cartao passa a receber cupom. Fiscalmente defensavel (pagamento eletronico pede documento) e o comportamento esta escrito no codigo, mas nunca rodou em producao — o bug do await escondia isso desde sempre. Confirmar com quem definiu a regra se e isso mesmo; se nao for, o case 9 deve ir direto para romaneio, sem passar pelo padrao.

Vale conferir quantas pessoas tem notafiscal = 9 e = 1 no cadastro antes de subir.
<!-- SECTION:DESCRIPTION:END -->

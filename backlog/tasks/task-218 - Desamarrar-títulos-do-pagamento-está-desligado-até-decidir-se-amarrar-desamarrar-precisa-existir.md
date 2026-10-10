---
id: TASK-218
title: >-
  Desamarrar títulos do pagamento está desligado até decidir se
  amarrar/desamarrar precisa existir
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-10 21:30'
updated_date: '2026-10-10 21:31'
labels:
  - contas
  - negocios
dependencies: []
priority: low
type: enhancement
ordinal: 230000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (10/10/2026): desligar por enquanto o Desamarrar no detalhe do pagamento (contas e PDV), na tela e no servidor, e avaliar se o conceito de amarrar/desamarrar precisa existir, refatorando conforme a decisão.

Hoje (conceito de 09/10/2026, memória project_pagamento_fato_amarracao): o pagamento é o fato do dinheiro; a amarração é o vínculo dele com títulos (tblmovimentotitulo), venda (codnegocio) ou vale/adiantamento. Desamarrar estornava as baixas e deixava o pagamento solto em Pagamentos não resolvidos, para amarrar de novo ou cancelar (só o manual).

O que foi desligado:
- api/app/Mg/Pagamento/PagamentoTituloService.php desamarrar(): recusa com 422 logo na entrada (as rotas POST v1/pagamento/{id}/desamarrar e v1/pdv/pagamento/{id}/desamarrar, e os aliases /estornar, passam por ali). O resto do método ficou para a decisão.
- PagamentoDetalheResource: desamarravel sempre false.
- components/MgPagamentoDetalhe.vue: saíram os botões Desamarrar todos os títulos e Desamarrar este título; pagamentoListaStore perdeu a action desamarrar.

Efeito colateral enquanto estiver desligado: cancelar pagamento manual exige não ter título amarrado, então pagamento manual digitado errado que baixou título não tem como ser desfeito pela tela.

Perguntas para avaliar: quem usa desamarrar e para quê; se desfazer a baixa errada deve ser pelo título, pelo cancelar do pagamento ou por outro caminho; se Pagamentos não resolvidos e o saldo amarrado/livre continuam fazendo sentido.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Desamarrar desligado no detalhe do pagamento (contas e PDV): a tela não oferece e o servidor recusa com mensagem
- [ ] #2 Decidido se amarrar/desamarrar continua existindo e como se desfaz uma baixa feita no pagamento errado
- [ ] #3 Refatorado conforme a decisão (religar, trocar por outro caminho ou remover o conceito)
<!-- AC:END -->

---
id: TASK-214
title: >-
  Título de vale/adiantamento deixa alterar o valor e fica diferente do
  pagamento
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 20:53'
updated_date: '2026-10-10 20:54'
labels:
  - contas
dependencies: []
priority: high
type: bug
ordinal: 226000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No contas, o título que nasceu de um pagamento (vale colaborador, adiantamento a fornecedor/cliente) deixa editar o valor. Exemplo: título #00650456 (Adiantamento Fornecedor), implantado em 100,00 pelo Pagamento #80248399, foi editado para 150,00 e o sistema lançou um Ajuste de 50,00 sem dinheiro ter andado: o título passa a dizer 150 e o pagamento/razão 100. High porque o saldo fica errado sem ninguém ver.

Causa: a trava de edição (Titulo::geradoAutomaticamente() no backend, `automatico` em contas/src/pages/titulo/Detalhe.vue) só olha venda (codnegocioparcela), agrupamento e maquineta (CaixaItemAcerto); o título cuja implantação está ligada a um pagamento (TituloService::implantar com $pagamento) ficou de fora. Não dá para só incluir o caso em geradoAutomaticamente(): a mesma regra bloqueia o estorno, e o estorno do vale tem que continuar (devolve o dinheiro e cancela o pagamento).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Título que nasceu de um pagamento não deixa alterar o valor, nem pela tela nem pela API
- [x] #2 Número, emissão e transação desse título também ficam travados (são do pagamento)
- [x] #3 Estornar o vale/adiantamento continua disponível
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Titulo::nasceuDePagamento() (implantação com codpagamento). TituloService::atualizar ignora valor, número, emissão e transação quando travado; vencimento original segue só a regra de gerado automaticamente. TituloDetalheResource expõe nasceudepagamento; contas/src/pages/titulo/Detalhe.vue usa `travado` nesses quatro campos. geradoAutomaticamente() não mudou, então o estorno do vale continua. Validado em transação desfeita: #650456 mantém 150/datas/número e aceita vencimento; título comum #10077446 continua aceitando valor. O #650456 do banco de dev segue com 150 (ajuste de 50 lançado antes da trava).
<!-- SECTION:NOTES:END -->

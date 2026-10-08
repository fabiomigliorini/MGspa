---
id: TASK-204
title: >-
  Lançamento feito com a data errada não tem como ir para o dia certo no caixa,
  no banco e na maquininha
status: To Do
assignee: []
created_date: '2026-10-08 21:59'
labels:
  - contas
dependencies: []
priority: high
type: feature
ordinal: 216000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sintoma: o caixa de ontem fica pendente por uma diferença que tem explicação (vale à colaboradora, pagamento a fornecedor ou título esquecido, crédito de cliente, cartão manual que o entregador trouxe depois), mas o lançamento só sai com a data de hoje. No PDV a data é sempre agora; no contas a tela de Pagamentos recusa a gaveta do PDV e grava a data sem hora (cai no período que contém 00:00). Na maquineta o cartão fica no período pelo vínculo, não pela data. O caixa de ontem não fecha e trava os seguintes (fecha-se do mais antigo para o mais novo), e o extrato não bate com o papel. Workaround hoje: ajuste cruzado (ajuste no período de ontem e contrário no de hoje), o que fere "ajuste não é vale".

Regra decidida com o Fábio (08/10/2026): a data (com hora) manda no período, em qualquer portador (banco, espécie, maquineta); alterar a data num lugar altera todos.

Notas técnicas:
- Contas: MgAdiantamentoDialog e a baixa de títulos mandam só a data (startOfDay em TituloAdiantamentoService::lancar e PagamentoTituloService::atualizar); PagamentoTituloService::portadorDaForma recusa gaveta.
- PDV: transacao = Carbon::now(); o pagamento grava codportadorperiodo (sessão), que PortadorMovimentoService::periodo usa antes da data.
- Maquineta: MaquinetaLoteService::vincular põe no lote corrente; o "mover" de PagamentoCorrecaoService::corrigir muda o lote sem mudar a data. O cancelamento já tem data (tblpagamento.cancelamento) e lote (codmaquinetalotecancelamento) próprios.
- Movimento do portador (item, borderô, ajuste, transferência): PortadorLancamentoService::dataNoPeriodo.
- Rastro: PagamentoCorrecaoService::registrar (antes/depois + justificativa) para pagamento; movimento do portador ainda não tem onde registrar.
- Fecha-se em ordem (caixa, banco), então período mais novo que o destino nunca está fechado.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Cada linha do extrato do portador (caixa, banco) e do período da maquineta tem botão de alterar a data com hora; a linha vai para o período daquela data
- [ ] #2 Período fechado (caixa/banco) ou conferido (maquineta) não recebe nem perde lançamento: recusa dizendo qual reabrir
- [ ] #3 Venda (dinheiro, cartão ou PIX): alterar a data muda o pagamento e o extrato; negócio e NFC-e ficam com a data original e a linha mostra que a data difere da venda
- [ ] #4 Baixa de título: a data com hora é informada na tela da baixa e pode ser alterada no extrato; alterar num lugar muda extrato, pagamento e liquidação do título, em qualquer portador
- [ ] #5 Vale/adiantamento: igual à baixa, e a emissão do título acompanha; vencimento não muda
- [ ] #6 PDV: baixa e vale ganham data com hora, limitada aos períodos não fechados da gaveta
- [ ] #7 Sangria/reforço: uma data só para a transferência; muda as duas pontas juntas
- [ ] #8 Item do caixa, borderô de parceiro e ajuste: alterar a data leva a linha (e a foto do borderô) para o período da data; saldo do item recalculado
- [ ] #9 Cartão e PIX automáticos e boleto pelo retorno do banco também podem ter a data alterada, inclusive no pagamento
- [ ] #10 Pagamento de acerto do RH também tem a data alterável no extrato
- [ ] #11 Maquineta: a data manda no período; o mover para outro período sem data deixa de existir
- [ ] #12 Cancelamento de cartão tem data própria editável; o período do cancelamento segue essa data, sem mexer na venda
- [ ] #13 Alterar a data pede justificativa e grava o antes/depois, mostrado na linha; só o gestor do portador (no PDV, quem já lança ali)
- [ ] #14 Mudar de mês é permitido, com aviso de que pode afetar DIMP e relatórios já apurados
- [ ] #15 Juros, multa, desconto e total não mudam ao alterar a data
- [ ] #16 O caixa pendente não fecha sozinho depois da alteração: fecha pelo botão Fechar
<!-- AC:END -->

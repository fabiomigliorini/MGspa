---
id: TASK-30
title: Venda fechada com pagamento errado não tem como ser corrigida pelo gerente
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-23 22:35'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 118000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje a venda fechada com pagamento errado só sai cancelando a venda inteira. O gerente (Gerente da filial, Financeiro, Admin) reabre o negócio no PDV; qualquer um edita e dá F3; o fechamento mantém data (lancamento), caixa (codusuario), PDV (codpdv) e, no dinheiro, o portador onde ele entrou.

Decisões do Fábio (09-10/10/2026), plano completo em ~/.claude/plans/precisamos-pensar-em-alguma-wise-hamming.md:
- Sem status novo: reaberta = status 1 com tblnegocio.reabertura preenchida; auditoria tipo Negócio reaberto, sem justificativa.
- Tudo muda menos a nota; integrado não muda; manual efetivado não se apaga: vira C (parcela com título fica inativa) e pode ser reativado (pagamento volta a P).
- A sincronização só grava o estado da venda; o F3 é idempotente e é o único que mexe em razão, caixa, lote, vale, cheque, títulos e crédito de vale (cria o que falta, desfaz o cancelado, não duplica). Período fechado / lote conferido: 422 no F3.
- Pagamento manual novo na reaberta nasce com a data da venda; dinheiro no portador do dinheiro da venda (fixo no wizard).
- F3 faz as mesmas validações; o que já foi validado no 1º fechamento não se revalida.
- Cada Cancelar/Reativar na reaberta vai para a auditoria.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Gerente reabre o negócio fechado no PDV (status 1 + reabertura, auditoria Negócio reaberto); caixa não reabre
- [x] #2 Na reaberta qualquer usuário edita e dá F3 em qualquer PDV, e o F3 mantém lancamento, codusuario e codpdv
- [x] #3 Pagamento manual efetivado e parcela com título são cancelados/inativados e reativados na reaberta; integrado fica travado; cada troca vai para a auditoria
- [x] #4 F3 idempotente: efetiva o novo, desfaz o cancelado (razão, caixa, lote, vale, cheque, título, crédito de vale) e não duplica nada; reabrir e fechar sem mexer não muda nada
- [x] #5 Pagamento manual novo na reaberta nasce com a data da venda; dinheiro no portador do dinheiro da venda
- [x] #6 Segundo fechamento não duplica ocorrência, não registra boleto de novo e não conta o vale nem o limite de crédito duas vezes
- [x] #7 Cancelar a venda reaberta desfaz também o que foi cancelado e ainda não passou pelo F3
- [x] #8 Views legadas (tblnegocioformapagamento) e leitores de parcela ignoram pagamento C e parcela inativa
- [x] #9 Listagem do PDV filtra os Reabertos
- [x] #10 Depois do F3 a venda fechada continua mostrando riscado o pagamento cancelado e a parcela tirada (só a venda aberta comum esconde)
- [x] #11 Duplicar a venda reaberta gera um negócio aberto comum, não reaberto
- [x] #12 Gerente cancela o pagamento integrado (cartão SafraPay/Stone, PIX) na janela da cobrança e no detalhe do pagamento, em qualquer tela, com justificativa: sai da venda e do lote, a cobrança se solta da venda e a consulta não traz de volta; venda fechada recusa pedindo para reabrir
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (10/10/2026):
- DDL: api/database/negocio_reabertura.sql (tblnegocio.reabertura, tblnegocioparcela.inativo, views legadas tblnegocioformapagamento e vwnegocioformapagamentototais sem pagamento C e sem parcela inativa). Roda depois do pagamento.sql.
- Reabrir: POST v1/pdv/negocio/{cod}/reabrir -> PdvNegocioReaberturaService::reabrir (ConferenciaAutorizador; status 1 + reabertura; auditoria 15).
- Sincronização da reaberta: PdvNegocioPagamentoService::importar com ConferenciaService::semVincular; o manual troca de estado (E/P->C com indevido, C->P; auditorias 16/17); parcela com título troca inativo (18/19). Rascunho que nunca foi fato continua sendo apagado.
- F3 (PdvNegocioService::fechar): mantém lancamento/codusuario/codpdv; desfazerCancelados (razão, lote, baixa do vale, cheque, título da parcela inativa; só com efeito vivo); pagamento novo com a data da venda (transacao = lancamento), dinheiro no portador do dinheiro da venda, cartão no lote da data (daData); validações só no que é novo; gerarTitulos/baixarVales/reconferirSaldos/cheque/créditos de vale idempotentes; ocorrências sem duplicar; boleto só o não registrado.
- Cancelar a reaberta desfaz também o C ainda sem F3 e estorna título de parcela inativa.
- PDV: botão Reabrir (lock_open) para Gerente/Financeiro/Admin; reaberta editável em qualquer PDV; Cancelar/Reativar no detalhe do pagamento e do prazo; cancelado riscado na lista; filtro Reaberto na listagem; Apropriar some.

Testado em dev pelo tinker (transação desfeita no fim): F3 sem mexer = no-op; dinheiro->cartão; reativar; crediário/PIX chave -> dinheiro e volta (título novo com sufixo); vale usado cancelado/reativado; vale vendido sem reemissão; cancelar a reaberta com C pendente.

Fechada mostra o cancelado (10/10/2026): PdvNegocioPagamentoService::mostrarCancelado — pagamento C e parcela inativa só ficam escondidos na venda aberta comum; na reaberta, fechada e cancelada voltam riscados. No PDV, a decisão de emitir nota (romaneioOuNotaVendaPadrao) e o cartão do contra-vale passam a usar pagamentosAtivos/parcelasAtivas, para o cancelado não contar.

Duplicar a reaberta (10/10/2026): o duplicar() do PDV (negocios/src/stores/negocio.js) copiava o negócio inteiro com a reabertura, e a cópia aparecia como reaberta. Só no aparelho — o servidor não aceita reabertura no PUT (fora do fillable) —, mas o PDV tratava a cópia como reaberta (PIX/cartão no portador e na data da venda original). Agora a cópia nasce com reabertura = null. Cópia feita antes da correção se acerta com o botão de recarregar do servidor no detalhe do negócio.

Reaberta (23/10/2026), relato do Fábio: o gerente estornou na maquininha o débito SafraPay de R$ 608,60 da venda #04651600 (fechada, NFC-e autorizada) e não havia como registrar. Nenhum caminho cancelava o integrado da venda: a janela da cobrança só tinha Consultar; PagamentoTituloService::cancelar recusava 'Pagamento de venda: cancele pelo negócio'; na reaberta o integrado ficava travado (AC #3). A decisão do Fábio de 16/10 ('o automático também, mas só Administrador ou Gerente') substitui o 'integrado fica travado' do AC #3. Além disso, a consulta da Stone ressuscitava o pagamento cancelado (PagarMeService::vincularPagamento volta C->P->E com o pedido ainda na venda).

Correção:
- api/app/Mg/Pagamento/PagamentoTituloAutorizador.php motivoBloqueioEstorno: pagamento de venda = ConferenciaAutorizador::pode (Gerente da filial, Financeiro, Admin), o mesmo do Reabrir.
- api/app/Mg/Pagamento/PagamentoTituloService.php cancelar: o integrado da venda vai para PdvNegocioPagamentoService::cancelarIntegrado; o manual da venda continua recusando ('reabra a venda e cancele no PDV').
- api/app/Mg/Pdv/PdvNegocioPagamentoService.php cancelarIntegrado: venda aberta (reaberta ou não), senão 'Reabra a venda #N'; exigirAberta (lote conferido/sessão fechada/conferido recusam); indevido + PagamentoService::cancelar (sai do razão e do lote; PIX volta a Sem uso); solta a cobrança (codnegocio nulo em PixCob/PagarMePedido/SaurusPedido), para a consulta não trazer de volta; auditoria tipo 23 'Pagamento integrado cancelado' com a justificativa; recalcularTotal.
- api/app/Mg/Pagamento/PagamentoDetalheResource.php: cancelavel também no integrado da venda.
- components/cobranca/cancelarPagamento.js (novo) + SaurusPedidoDialog, PagarMePedidoDialog, PixCobDialog: botão 'Cancelar pagamento' (vermelho) sempre que a cobrança tem pagamento ativo, em todas as telas que abrem a janela (PDV, Pagamentos do Contas e do PDV, baixa de títulos). Pede o motivo, chama v1/pagamento/{id}/cancelar e avisa cobrancaAtualizada (a venda na tela recarrega).
- components/MgPagamentoDetalhe.vue: mensagem do Cancelar para o pagamento de venda.

Teste (dev):
1. Venda fechada paga com SafraPay; como gerente, clicar no cartão -> Cancelar pagamento: recusa pedindo para reabrir. Reabrir, Cancelar pagamento com motivo: linha riscada; psql: tblpagamento estado C, indevido true, codmaquinetalote nulo; tblsauruspedido.codnegocio nulo; tblauditoria tipo 23. Lançar dinheiro e F3: fecha com a mesma data/caixa/PDV.
2. Stone na venda: mesmo roteiro; consultar o pedido depois: o pagamento não volta.
3. PIX na venda: cancelar; o PIX aparece em Sem uso na tela PIX do Contas.
4. Detalhe do pagamento (Contas e PDV -> Pagamentos): cartão integrado de venda mostra Cancelar (block) e funciona igual.
5. Baixa de títulos com cartão (Contas): na janela da cobrança paga, Cancelar pagamento estorna as baixas.
6. Caixa sem grupo: botão aparece, servidor recusa 'só Financeiro, Administrador ou Gerente da filial'.
7. Lote da maquininha conferido: recusa com a mensagem do lote, sem mudar nada.
8. Manual da venda pelo detalhe: recusa 'reabra a venda e cancele no PDV'.

Validado pelo Fábio em 23/10/2026 ('funcionou'). doc-3 atualizado: o integrado se cancela (baixa: TASK-218; venda: TASK-30).
<!-- SECTION:NOTES:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-10-10 16:26
---
Testes de 10/10/2026 (dev). Pela API, simulando o PDV: reabrir/F3 sem mexer (no-op); troca de pagamento em outro PDV (mantém data, caixa e PDV); Apropriar recusa; PDV com cópia velha recebe 'reaberto pelo gerente'; filtro Reaberto; parcela PIX chave tirada e reativada (título estornado e novo com sufixo); item tirado (ocorrência uma vez só); prazo maior que o total recusa; caixa sem permissão 403; sessão de caixa fechada e lote conferido recusam no F3 sem gravar nada; PIX integrado travado mesmo forçando C; preço fora do cadastro não duplica; crédito bloqueado não barra o F3 sem mudança e barra parcela nova; título acompanha a troca de cliente; natureza sem financeiro recusa. Pela tela (Chrome headless): Reabrir, Cancelar/Reativar pagamento e prazo, wizard em dinheiro na reaberta com F3 automático, caixa não vê Reabrir. Regressão: venda comum, excluir rascunho, cancelar fechada, crediário, indevido da conferência, Já recebido de outro PDV. Corrigido: mensagem de sessão de caixa fechada passa a dizer o caixa e o período. Vendas de teste criadas no dev: 4549125 a 4549129.
---

created: 2026-10-10 16:27
---
Conferido e validado pelo Claude (10/10/2026): o Fábio dispensou o teste manual por falta de tempo. Os critérios foram marcados pelo que está implementado e pela bateria registrada no comentário anterior (API simulando o PDV, telas no Chrome headless e regressão dos fluxos comuns), toda passando. Pontos que ficaram para o Fábio decidir: aviso vermelho 'Não existe nenhum produto para gerar Nota' ao refechar venda de cartão/PIX que já tem nota (não duplica nota); filtro Reaberto da listagem vem com o PDV atual e a reaberta mantém o PDV original.
---
<!-- COMMENTS:END -->

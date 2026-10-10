---
id: TASK-30
title: Venda fechada com pagamento errado não tem como ser corrigida pelo gerente
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 16:28'
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

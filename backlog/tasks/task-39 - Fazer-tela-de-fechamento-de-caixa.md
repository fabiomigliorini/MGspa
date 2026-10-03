---
id: TASK-39
title: Fazer tela de fechamento de caixa
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-03 18:39'
labels:
  - negocios
  - contas
  - api
dependencies:
  - TASK-186
documentation:
  - backlog/docs/doc-4 - Refatoração-das-telas-do-dinheiro-portador-e-período.md
priority: high
type: feature
ordinal: 59000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje o fechamento de caixa é feito à mão no formulário "Movimento do Caixa" (contagem de moedas/cédulas/chips/ingressos, vendas à vista, recebimentos, cartões, PIX, sangrias, diferença). O sistema só tem o protótipo "Totais de Caixa" do MG Lara, que lista totais e não fecha nada.

Desenho: portador como razão único do dinheiro (tblportadormovimento), gaveta = portador em espécie com PDV apontando, períodos por portador (sessão da gaveta / corte do financeiro), transferências em dois passos, itens de parceiros virando título de repasse. Plano completo, decisões e roteiro de cada milestone em backlog/docs/doc-3.

Execução por milestones, um por conversa, validado na tela antes do seguinte. Esta task cobre M10 a M13 (razão, transferências, períodos e itens do caixa). A fundação (M1 a M8.1) está na TASK-188 e a sessão da gaveta foi feita no M9 (critérios M9.x na TASK-188).

Consolida: TASK-33 (codportador na manutenção de PDV), TASK-34 (não movimentar dinheiro sem portador no PDV), TASK-48 (dinheiro só para Caixa/Gerente/Administrador) e TASK-84 (destino do Caixa Totais: negocios /caixa + contas Caixas).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 M11 - Sangria e suprimento entre caixas, cofre, troco, financeiro e banco, com confirmacao de quem recebe e cancelamento com justificativa
- [ ] #2 M11 - Nao transfere de ou para caixa fechado; caixa nao fecha com transferencia chegando pendente
- [ ] #3 M11 - Tela Caixas no contas com saldos dos portadores em especie e transferencias
- [ ] #4 M12 - Financeiro fecha periodo de cofre e banco pela data de corte; fechado e imutavel; reabre e fecha em ordem (em cadeia)
- [ ] #5 M13 - Chips, ingressos e maquinetas de parceiros contados no caixa e virando titulo de repasse ao parceiro (Duplicata a Pagar) no fechamento
- [ ] #6 M10.1 Dinheiro, PIX, deposito, transferencia e boleto feitos depois do go-live aparecem no razao da gaveta, cofre, banco ou conta onde cairam: entrada positiva, saida negativa, na data da transacao
- [ ] #7 M10.2 Cancelar, estornar ou corrigir um pagamento (valor, portador, meio ou data, no contas ou na conferencia do gerente) acerta o razao junto; com o caixa daquele dinheiro ja conferido, so reabrindo
- [ ] #8 M10.3 O detalhe do pagamento, no contas e no PDV, mostra os lancamentos do razao, com a sessao do caixa ou o periodo em que cairam, e os desfeitos riscados
- [ ] #9 M10.4 O saldo de cada caixa, cofre e banco e o saldo inicial do periodo mais o que caiu ate hoje
- [ ] #10 M10.5 Pagamento, cheque, extrato bancario e bonificacao guardam a data e hora em que o fato aconteceu, separada de quando foi digitado (o PIX de ontem lancado hoje fica com a data de ontem)
- [ ] #11 R1 - Uma tela Portadores no contas mostra todos os portadores por filial, com o saldo da especie e a situacao de cada caixa, e substitui Saldos e Cadastros > Portadores (criar, editar, inativar e importar OFX nela)
- [ ] #12 R2 - Ao abrir um portador, os periodos aparecem em abas Ano > Mes > Periodo, e o endereco da pagina leva direto ao periodo escolhido
- [ ] #13 R3 - O periodo mostra saldo inicial, entradas e saidas por origem (vendas, titulos, transferencias, avulsos, itens) e saldo final, com a lista de lancamentos e o saldo corrente linha a linha
- [ ] #14 R4 - Transferir, lancar avulso, item do caixa, abrir, fechar e reabrir o caixa ou o periodo feitos na propria tela do periodo, que se atualiza na hora
- [ ] #15 R5 - Venda em dinheiro, sangria, confirmacao, cancelamento, recebimento no banco e fechamento com corte conferidos na tela do periodo (roteiro Valida do doc-4)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Planejamento do M10 (02/10/2026, com o Fábio; detalhe no doc-3, seção M10): o razão lança dinheiro, PIX, depósito, transferência e boleto, uma linha por lado; chamado explicitamente pelo PagamentoService e pela edição/correção; só do go-live (CONFERENCIA_INICIO) em diante; corrente de cofre/banco/adquirente nasce no 1º lançamento com início no go-live e saldo 0; coluna transacao = quando cai; na gaveta o razão trava na conferência do gerente; card Razão no detalhe do pagamento. Padronização transacao = fato gerador: pagamento, cheque, extrato e bonificação no M10; negócio e o resto do sistema em task própria. Critérios #1 a #6 antigos (gaveta) movidos para a TASK-188 como M9.1 a M9.6.

M10 executado em dev em 02/10/2026, na árvore, sem commit (ACs M10.x desmarcados até a validação). DDL api/database/razao.sql rodado 2x (idempotente): lancamento → transacao em pagamento, cheque, extrato e bonificação; tblportadormovimento recriada; tblportadortransferencia apagada. Backend: PortadorMovimentoService::sincronizar (chamado em PagamentoService criar/contrario/efetivar/cancelar, PagamentoTituloService pagamentoDaForma/daBaixa/atualizar, PagamentoCorrecaoService corrigir/incluir), PortadorPeriodoService (corrente, imutavel, saldo), razao[] no detalhe; rename em toda a API, @components, contas e pessoas. Frontend: card Razão no MgPagamentoDetalhe. Conferido com rollback no tinker (detalhe no doc-3, seção M10 'Como ficou no código'). Não aberto no navegador.

M10 commitado sem validação a pedido do Fábio (02/10/2026): ele valida amanhã, junto com M11 e M12. ACs M10.x seguem desmarcados até a validação.

M11 em andamento (02/10/2026): transferências entre portadores (decisões 13, 22 e 23), executado na mesma conversa que o M12.

M11 executado em dev (02/10/2026; sem DDL): PagamentoService::{transferir, confirmar, cancelarTransferencia, pendentes} com TransferenciaAutorizador (decisões 22 e 23), razão lançando a transferência a confirmar, sessão da gaveta pegando o lado gaveta e somando as transferências (documento X), fechar o caixa recusa com transferência chegando; rotas v1/pdv/caixa/transferencia, v1/pagamento/transferencia, v1/portador/caixas, v1/portador/{id}/saldo; negocios DialogTransferir + card na tela do Caixa; contas Movimento → Caixas (Portadores, Transferências); Confirmar/Cancelar no MgPagamentoDetalhe. Conferido no tinker com rollback (serviço e controllers). Detalhe e 'Dúvidas para o Fábio (M11)' no doc-3, seção M11. ACs M11 desmarcados até a validação.

M12 em andamento (02/10/2026): períodos no contas (fechar com corte, reabrir em ordem, lançamento avulso), sem a sessão da gaveta (já é do M9).

M12 executado em dev (02/10/2026, na árvore, sem commit; sem DDL; sessão da gaveta fora, é do M9): PortadorPeriodoService::{doMomento (o razão de não-gaveta cai no período da data), fechar com corte (o que fica depois vai para o corrente novo, saldos propagados), reabrir só o fechado mais novo, lancar avulso T/F/R/A — implantação = Ajuste na data do go-live}; PortadorPeriodoController v1/portador-periodo (Financeiro/Admin); contas Caixas → aba Períodos. Conferido no tinker com rollback (cofre com setembro/outubro, corte, 422s, reabrir e fechar na ordem, implantação no banco) e M11 de novo. Detalhe e 'Dúvidas para o Fábio (M12)' no doc-3, seção M12. ACs M10.x a M12 desmarcados até a validação.

M12 commitado sem validação a pedido do Fábio (03/10/2026): ele valida M10, M11 e M12 juntos pelo roteiro único do doc-3 (seção M12, 'Valida M10 + M11 + M12'). ACs M10.x a M12 seguem desmarcados até a validação.

M13 em andamento (03/10/2026): itens do caixa e repasse ao parceiro (decisões 21 e 27), mais a pendência da gaveta deixada pelo M10 — ajuste de caixa na abertura/fechamento da sessão e lançamento avulso no PDV (TASK-188 M9.5, #37).

M13 executado em dev (03/10/2026, na árvore, sem commit). Redesenhado com o Fábio antes de codar: uma tela só do caixa (@components/MgCaixaSessao) no PDV e no contas, contagem por quantidade de cédula/moeda (jsonb), saldo inicial = envelope, um ajuste na abertura e um no fechamento (sempre o mesmo registro), fechar = conferência (a etapa às cegas do M9 saiu; o caixa ou o gerente fecha), sangria pela própria tela. DDL api/database/caixa_item.sql rodado 2x (tblcaixaitem com 6 seeds, tblcaixaitemlancamento, tblpagamento.codcaixaitemlancamento, tblportadorperiodo ajuste/contagem). Itens: pagamento entrada − saída na gaveta (mesmo registro), títulos de repasse no fechamento (200 a pagar, 100 se negativo; pessoa obriga conta), reabrir estorna (422 se agrupado). Avulso T/F/R/A na gaveta (exclui só quem lançou, caixa aberto) — cobre o M9.5 da TASK-188 (#37). Cadastros → Itens do Caixa e Caixas → aba Itens no contas; origem 'Item do caixa' na listagem. Conferido no tinker e pela camada HTTP com rollback; não aberto no navegador. Detalhe, roteiro 'Valida (M13)' e 'Dúvidas para o Fábio (M13)' no doc-3, seção M13. AC #5 desmarcado até a validação.

M13 commitado sem validação a pedido do Fábio (03/10/2026, e8ab52048): ele valida pelo roteiro 'Valida (M13)' do doc-3. AC #5 (e o M9.5 da TASK-188) seguem desmarcados até a validação.

Refatoração portador e período (doc-4) executada em dev em 03/10/2026, na árvore, sem commit: DDL portador_saldo.sql (2x), saldo gravado no período e no portador (PortadorPeriodoService::recalcular via sincronizar), painel /portador e tela /portador/{cod}/{codperiodo} no contas, periodoStore em @components com os dialogs genéricos, rotas de movimento devolvendo os períodos afetados, v1/portador/caixas e /saldo removidos (tela Caixas quebra até o redesenho), cancelar avulso fora da gaveta, mensagem única 'Gaveta não aberta'. Decisões da execução e 'Como ficou' no doc-4. ACs R1–R5 desmarcados até a validação pelo roteiro Valida do doc-4.

Ajustes de tela na mesma conversa (cabeçalho por tipo no painel, badge Aberto, primeiro período sem F5, avulso com data preenchida/radio/combo, lançamentos em q-timeline com link para o outro lado da transferência). Commitado em 03/10/2026 sem validação, a pedido do Fábio: ele valida pelo roteiro Valida do doc-4; ACs R1–R5 seguem desmarcados. Em aberto: Abrir caixa com contagem em cofre/troco/Caixa Financeiro (só implantação ou diário).
<!-- SECTION:NOTES:END -->

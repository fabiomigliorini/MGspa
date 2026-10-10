---
id: TASK-204
title: >-
  Lançamento feito com a data errada não tem como ir para o dia certo no caixa,
  no banco e na maquininha
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-08 21:59'
updated_date: '2026-10-10 01:28'
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
- Rastro: tblauditoria (AuditoriaService::registrar), para pagamento e movimento do portador.
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
- [ ] #17 Receber ou Pagar Títulos (contas e PDV): data, pessoa e observação num diálogo aberto pelo FAB, à vista mesmo com a lista rolada
- [ ] #18 Toda correção de lançamento (data alterada, correção da conferência) fica registrada num lugar só, a auditoria
- [ ] #19 O selo 'corrigido' na conferência e na maquininha aparece só quando o valor ou o meio foi corrigido
- [ ] #20 A auditoria antiga da replicação entre bases sai: tabelas, funções e o usuário de banco
- [ ] #21 Baixa e vale: data no futuro (além de 5 min do relógio) ou antes do início do razão são recusadas, com a mesma tolerância do alterar data
- [ ] #22 Campo de data com hora: digitar só o dia é recusado (informe a hora); o calendário mantém a hora
- [ ] #23 Hora do extrato e do lápis do pagamento vai sem fuso (hora de Cuiabá): qualquer aparelho mostra e grava a mesma hora
- [ ] #24 Devolução (cancelamento no cartão/devolução de PIX) não fica antes do pagamento original, nem o original depois da devolução
- [ ] #25 Editar início/fim do período da maquineta leva junto os cartões cuja hora cai na faixa nova (só períodos não conferidos)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (08/10/2026, aguardando teste do Fábio; nada commitado):

Servidor
- LancamentoDataService (Mg\Portador): alterarPagamento (transação, sessão da gaveta, período da maquineta, razão via sincronizar, movimentos dos títulos com recálculo da liquidação; título que nasceu com o pagamento leva emissão e transação), alterarCancelamento (data e período do cancelamento do cartão), alterarMovimento (ajuste, item, borderô, transferência nas duas pontas), dataInformada (data com hora ao lançar).
- Rotas: POST v1/portador-movimento/{id}/data e POST v1/maquineta-lote/{id}/pagamento/{codpagamento}/data. Removidos o "mover" da correção (PagamentoCorrecaoService) e a rota GET v1/maquineta/{cod}/lote.
- Baixa de título e vale/adiantamento (contas e PDV) usam a data com hora informada; sem data, agora. Lápis do recebimento: data com hora; mudou, vai pelo alterarPagamento com justificativa.
- Maquineta: o cartão entra no período da data se ele não estiver conferido; senão, no aberto (o cartão que a API confirma depois não trava).
- Auditoria única (09/10/2026): tblauditoria genérica (tabela + codigo + tipo; antes/depois só com os campos relevantes que mudaram; justificativa pode ficar vazia) no lugar de tblpagamentocorrecao e tblportadormovimentocorrecao. Tipos: 1 data alterada, 2 data do cancelamento alterada, 3 corrigido na conferência, 4 registro indevido, 5 incluído na conferência (6–13 vêm com a TASK-205: item excluído, quantidade alterada, preço diferente do cadastro, vale excluído, pagamento apagado, parcela apagada, negócio cancelado, estornado; a ocorrência amarra as auditorias N:N em tblocorrenciaauditoria, e as correções 1–5 em PDV monitorado também viram ocorrência). Selo "corrigido" conta só 3 e 4. O reprocessamento do boleto procura o tipo 1, não o JSON.
- A replicação entre bases de 2011 saiu: tblauditoria antiga, tblauditoriatransmissao, tblauditoriaexcecao, tblbaseremota, geraauditoria(), criatriggers_geraauditoria(boolean) e o usuário mgsis_replicacao (a tblcobrancahistoricotitulo passa para o mgsis). O mgsis_yii fica (o MGsis usa).
- Corrigido junto: o nome de quem alterou não aparecia no "era dd/mm" (o accessor usuariocriacao do MgModel engolia a relação).
- DDL do go-live: api/database/auditoria.sql, depois do conferencia.sql (rodado 2x no dev). portador_movimento_correcao.sql e a seção da tblpagamentocorrecao no conferencia.sql saíram.

Telas
- Extrato do portador (contas e caixa do PDV): botão de calendário na linha, dialog AlterarDataDialog (data com hora, justificativa, aviso de troca de mês); linha mostra "era dd/mm hh:mm: justificativa · usuário"; venda com pagamento noutro dia mostra o selo "venda de dd/mm".
- Período da maquineta: calendário na venda, no estorno e no cancelamento (data do cancelamento); "Corrigir" não move mais de período.
- Baixa de títulos e Vale/Adiantamento: data com hora no contas e no PDV (antes o PDV não tinha data).
- Detalhe do pagamento (lápis): data com hora, justificativa e aviso de troca de mês quando a data muda.

Escolhas feitas sem perguntar (revisar no teste)
- Permissão: gestor de qualquer um dos portadores envolvidos (na transferência, origem ou destino); cartão: quem confere a maquineta (Gerente da filial/Financeiro); no PDV, a gaveta dele. O lápis do recebimento também exige isso para mudar a data.
- Data informada até 5 minutos à frente vira agora (relógio do cliente); mais que isso, recusa.
- O contas continua recusando a gaveta do PDV na baixa/vale (o PDV informa a data, decisão 4).
- A data não pode ir para antes do início do razão (go-live).

Roteiro de teste
1. Caixa pendente de ontem: no PDV, lançar o vale com a data/hora de ontem dentro do período pendente → cai no período de ontem; no contas, Fechar o período de ontem → fecha se a diferença zerou.
2. Lançar hoje e corrigir: no extrato do caixa de hoje, calendário na linha do vale (ou venda, título, item, borderô, ajuste, sangria) → data de ontem → a linha some de hoje, aparece em ontem com "era ... : justificativa"; a sangria muda também no cofre.
3. Período fechado: tentar levar para um período fechado → recusa pedindo para reabrir.
4. Título: alterar a data de um recebimento → a liquidação do título muda junto (tela do título).
5. Maquineta: calendário num cartão → vai para o período da data; num cancelamento de outro período → muda só a data do cancelamento.
6. Troca de mês: escolher data noutro mês → aviso laranja no dialog.
7. Lápis do recebimento: mudar a hora → pede justificativa; salva e o razão mostra a data nova.

Revisão (08/10/2026, depois do commit 783933f7b; testado no dev em transação desfeita)
- Calendário zerava a hora ao escolher o dia (voltava a cair no período das 00:00): MgInputData ganhou default-time="keep" (troca o dia, mantém a hora), usado nos campos novos.
- Permissão: gestor de CADA portador que muda (antes bastava um lado: o gestor do caixa mexia na ponta do cofre). O PDV só altera o que é só da gaveta dele. O botão da linha segue a mesma regra.
- Lápis do recebimento volta à permissão dele (a de editar o pagamento; o encontro de contas, sem portador, só o admin conseguia); a data vai primeiro, depois o portador novo.
- Cobrança integrada (PIX QR, Stone, SafraPay) na baixa e no vale: o título usa a data do pagamento.
- Saída de item só vai para período onde o item está no caixa (a mesma regra do lançamento).
- Vale: a data nova não passa do vencimento nem de baixa já feita no título.
- Cheque recebido acompanha a data do pagamento.
- Boleto reprocessado (retorno/API BB) mantém a data alterada à mão.
- Trava da maquineta ao alterar a data (corrida com o Conferir); botão de data não aparece no estorno cancelado (o servidor recusa).

Riscos que ficam (não tratados)
- Mover uma ENTRADA de item para outro período não confere as saídas que dependiam dela.
- O número do vale gerado pela data de emissão (AAAA-MM-DD) não muda junto.
- O codpdv do request não é validado pelo dispositivo (padrão que já existia em sangria e cancelamento); com a regra por portador, o alcance fica na gaveta do próprio PDV.
<!-- SECTION:NOTES:END -->

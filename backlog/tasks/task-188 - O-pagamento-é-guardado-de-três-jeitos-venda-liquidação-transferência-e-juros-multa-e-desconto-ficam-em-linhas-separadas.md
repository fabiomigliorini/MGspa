---
id: TASK-188
title: >-
  O pagamento é guardado de três jeitos (venda, liquidação, transferência) e
  juros, multa e desconto ficam em linhas separadas
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-30 02:24'
updated_date: '2026-09-30 14:45'
labels:
  - contas
  - negocios
  - api
dependencies:
  - TASK-186
documentation:
  - backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md
priority: high
type: chore
ordinal: 201000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Fundação do fechamento de caixa (TASK-39). Milestones M1 a M6 do plano doc-3 (Plano do fechamento de caixa por milestones), um por conversa, cada um validado na tela antes do seguinte.

Hoje "como o dinheiro se moveu" está guardado de três jeitos: a forma de pagamento da venda, a liquidação de títulos e a transferência entre portadores. Juros, multa e desconto de uma baixa ficam em linhas de movimento separadas da baixa. Não existe tipo de portador, os PDVs não apontam para portador e as maquinetas estão espalhadas entre as tabelas das operadoras e um serial digitado.

Decisões (resumo; detalhe no doc-3, decisões 1 a 17):
- Duas peças genéricas: o pagamento (tblpagamento: origem, destino, meio, estado, principal/juros/multa/desconto/total) e o razão do portador (TASK-39).
- Pagamento absorve a transferência e a forma de pagamento da venda (histórico copiado com os mesmos códigos; view temporária com o nome antigo para MG Lara e MGsis).
- O prazo da venda vira parcelas do negócio (tblnegocioparcela), que viram títulos ao fechar.
- A liquidação some: o movimento de título aponta para o pagamento; encontro de contas = pagamento de total zero, meio compensação.
- Movimento de título numa linha por título e pagamento: principal (efeito no saldo, com sinal), juros, multa, desconto (positivos) e total (dinheiro que andou).
- Meios, condições de prazo e motivos em lista fixa no código; cadastro único de maquinetas; tipo de portador E/B/A/C/O; PDV aponta para o portador.
- Estados pendente/efetivado/cancelado; cancelamento parcial e devolução = pagamento contrário apontando para o original.

Milestones:
- M1: movimento de título numa linha (principal, juros, multa, desconto, total), histórico convertido.
- M2: tipo de portador, gavetas, trocos, adquirentes, PDV → portador.
- M3: cadastro único de maquinetas.
- M4: pagamento e parcelas no lugar da forma de pagamento da venda (PDV não muda).
- M5: wizard de cobrança desacoplado; prazo com vencimento ajustável.
- M6: pagamento no lugar da liquidação; telas de recebimentos e pagamentos.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 M1.1 Na baixa de um título, juros, multa e desconto ficam na mesma linha do movimento, junto com o principal e o total pago
- [x] #2 M1.2 Estornar uma baixa gera uma linha só de estorno, desfazendo principal, juros, multa e desconto juntos
- [x] #3 M1.3 O histórico antigo aparece convertido, com o saldo de cada título e os totais de juros, multa e desconto por mês iguais aos de antes
- [x] #4 M1.4 Baixa de boleto BB, acerto de RH, agrupamento e venda a prazo e vale no PDV gravam o movimento no formato novo
- [x] #5 M1.5 Títulos, Liquidações, Agrupamentos, recibos e relatórios mostram principal, juros, multa, desconto e total
- [x] #6 M2.1 Cada portador tem um tipo (espécie, banco, adquirente, cartão da empresa, outros) e a lista de portadores filtra por ele
- [x] #7 M2.2 Existem Stone, SafraPay e o troco de cada loja; a gaveta de cada PDV de caixa é cadastrada no contas como portador em espécie
- [x] #8 M2.3 Cada PDV de caixa aponta para a sua gaveta, e só aceita portador em espécie da mesma filial
- [ ] #9 M3.1 As maquinetas das duas operadoras e as manuais ficam num cadastro só no contas
- [ ] #10 M3.2 No PDV o cartão manual escolhe a maquineta da lista da filial em vez de digitar o serial, e o pagamento fica gravado com ela
- [ ] #11 M4.1 A venda grava pagamentos e parcelas no formato novo, com o histórico copiado e os mesmos totais por negócio
- [ ] #12 M4.2 Cada parcela a prazo vira título ao fechar a venda
- [ ] #13 M4.3 NF-e e NFC-e, DIMP, romaneio e conferência do PDV saem iguais em todas as formas de pagamento
- [ ] #14 M4.4 Totais de Caixa do MG Lara e NFe de Terceiros do MGsis continuam funcionando
- [ ] #15 M5.1 Receber no PDV (F6 a F9) funciona como antes em todas as formas, com o wizard separado da venda
- [ ] #16 M5.2 Na venda a prazo dá para ajustar vencimento e valor de cada parcela antes de fechar
- [ ] #17 M5.3 A venda para o fechamento mensal vence no último dia útil do mês seguinte
- [ ] #18 M6.1 No contas, Liquidações vira Recebimentos e Pagamentos, com filtros de portador, meio, pessoa e período, e não baixa título em gaveta
- [ ] #19 M6.2 Encontro de contas sem dinheiro continua possível e estornar desfaz o pagamento inteiro
- [ ] #20 M6.3 Acerto de RH e baixa de boleto BB geram pagamento
- [ ] #21 M6.4 Histórico das liquidações copiado, com totais por portador e mês iguais, e o Totais de Caixa do MG Lara funcionando
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
M1 implementado em dev em 29/09/2026, aguardando validação (ACs M1.x desmarcados até o OK).

DDL: api/database/movimento_titulo_colunas.sql (idempotente, rodado 2x em dev; cópia de antes em mgdb-mgdb-1:/tmp/m1_antes_movimentotitulo.dump). valor → principal; juros, multa, desconto (CHECK >= 0) e total. Histórico: juros/multa/desconto somados na linha de baixa do mesmo título no mesmo grupo (liquidação, retorno Bradesco, agrupamento ou boleto BB pela API), baixa e estorno separados; 52.608 grupos, 79.645 linhas incorporadas, 22 ficaram (liquidação de 2013 com 3 estornos e grupos de sinal trocado). Conferência dentro do script e refeita contra a cópia: saldo igual nos 777.624 títulos, juros/multa/desconto iguais nos 185 meses, total de cada liquidação igual. Tipos 400/401/500/940/941/950 inativados.

Backend: MovimentoTituloService::lancar(titulo, tipo, principal, [juros, multa, desconto, total], vinculos, unicoPor) grava uma linha (sem total, a baixa calcula); estornar desfaz a linha inteira; saldo soma principal; total da liquidação soma total. MovimentoTituloHelper::liquidar recebe juros/multa/desconto (adicionarMultaJurosDesconto saiu). Ajustados: liquidação, agrupamento (e o e-mail dele), título (implantação/ajuste/estorno), retorno Bradesco só adaptado à coluna nova (abandonado: boleto é pela API do BB), boleto BB (ajuste 200 do "outro" + uma linha de liquidação), vale do PDV, acerto de RH, resources de título/liquidação/agrupamento/RH, recibos, relatório de liquidações e PDF do agrupamento.

Frontend contas: detalhe do título (principal e, na baixa, juros/multa/desconto/total), detalhe da liquidação e do agrupamento (total pago, com principal/juros/multa/desconto). negocios e pessoas sem mudança (leem valor/saldo do título e a chave valor do resource do acerto).

MGsis: MovimentoTitulo.php e Titulo.php passam a principal (NFe de Terceiros); testado em dev com rollback. Sobem junto.

Testado em dev com rollback (tinker): liquidação receber+pagar com juros/multa/desconto e estorno; agrupamento e estorno; boleto BB reprocessado; retorno Bradesco 06 e 40; vale do PDV; inativar/reativar acerto de RH; recibos e relatório.

Deploy: script + código do MGspa + MGsis na mesma janela, API parada.

M2 em andamento (29/09/2026). Decisões na conferência: gavetas NÃO são criadas pelo script (alocacao='C' dá 120 PDVs ativos, não 17) — o portador espécie de cada caixa é criado no contas e vinculado em negocios → Config → PDV; cartões de débito da empresa (202021, 202022, 202040, 202041, 202043, 202045) = B; 202028 e 202031 (sem codbanco) = C; Troco de 101 a 105.

M2 implementado em dev em 29/09/2026, aguardando validação (ACs M2.x desmarcados até o OK). DDL api/database/portador_tipo.sql (idempotente, rodado 2x em dev): tblportador.tipo + CHECK + índice; seed A 6, B 39, C 10, E 12, O 9; Caixa Arquitetura → filial 501; Stone (202055) e SafraPay (202056) tipo A; Troco Deposito/Botanico/Centro/Imperial/Andre Maggi. Backend: Portador::TIPO_*, ehGaveta(); tipo nos requests, filtro, select e resource (+ gaveta); PdvService::update recusa (422) portador que não é espécie ou é de outra filial; PdvResource com portador. Frontend: contas Portadores (tipo no form, filtro, badge tipo/Gaveta); negocios Config → PDV (portador espécie da filial no editar, listagem mostra o portador); MgSelectPortador com tipos e agrupar/codfilial.

M1 validado pelo Fábio em 30/09/2026 (contas, negocios, pessoas, MGsis). Boleto Bradesco fora da validação: abandonado, boleto só pela API do BB.

M2 validado pelo Fábio em 30/09/2026 (contas → Portadores; negocios → Config → PDV). Critério M2.2 ajustado à decisão da conferência: a gaveta de cada PDV de caixa é cadastrada no contas, não criada pelo script.
<!-- SECTION:NOTES:END -->

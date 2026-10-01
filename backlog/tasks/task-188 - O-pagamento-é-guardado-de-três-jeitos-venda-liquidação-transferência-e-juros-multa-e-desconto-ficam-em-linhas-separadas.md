---
id: TASK-188
title: >-
  O pagamento é guardado de três jeitos (venda, liquidação, transferência) e
  juros, multa e desconto ficam em linhas separadas
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-30 02:24'
updated_date: '2026-10-01 19:19'
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
- [x] #9 M3.1 As maquinetas das duas operadoras e as manuais ficam num cadastro só no contas
- [x] #10 M3.2 No PDV o cartão manual escolhe a maquineta da lista da filial em vez de digitar o serial, e o pagamento fica gravado com ela
- [x] #11 M4.1 A venda grava pagamentos e parcelas no formato novo, com o histórico copiado e os mesmos totais por negócio
- [x] #12 M4.2 Cada parcela a prazo vira título ao fechar a venda
- [x] #13 M4.3 NF-e e NFC-e, DIMP, romaneio e conferência do PDV saem iguais em todas as formas de pagamento
- [x] #14 M4.4 Totais de Caixa do MG Lara e NFe de Terceiros do MGsis continuam funcionando
- [ ] #15 M5.1 Receber no PDV (F6 a F9) funciona como antes em todas as formas, com o wizard separado da venda
- [ ] #16 M5.2 Na venda a prazo dá para ajustar vencimento e valor de cada parcela antes de fechar
- [ ] #17 M5.3 A venda para o fechamento mensal vence no último dia útil do mês seguinte
- [ ] #18 M6.1 No contas, Liquidações vira Recebimentos e Pagamentos, com filtros de portador, meio, pessoa e período, e não baixa título em gaveta
- [ ] #19 M6.2 Encontro de contas sem dinheiro continua possível e estornar desfaz o pagamento inteiro
- [ ] #20 M6.3 Acerto de RH e baixa de boleto BB geram pagamento
- [ ] #21 M6.4 Histórico das liquidações copiado, com totais por portador e mês iguais, e o Totais de Caixa do MG Lara funcionando
- [x] #22 M3.3 Cadastrar, parear de novo, editar e inativar maquineta SafraPay e Stone integrada é feito no contas, e a tela Saurus/S2Pay sai do negocios
- [x] #23 M3.4 Maquineta criada por serial digitado errado pode ser juntada na certa, levando os pagamentos
- [ ] #24 M6.1.1 O wizard de cobranca, as formas e as integracoes (PIX, PagarMe, Saurus) existem numa copia so, em @components, usados por negocios e contas
- [ ] #25 M6.1.2 No contas, Receber ou Pagar Titulos abre o mesmo wizard do PDV: cartao com bandeira, autorizacao, parcelas e maquineta; transferencia, dinheiro do cofre, cheque, compensacao; gaveta nunca
- [ ] #26 M6.1.3 Uma listagem de pagamentos so, com todos os pagamentos (venda, titulo, transferencia, avulso) e filtros; no negocios travada no PDV atual
- [ ] #27 M6.1.4 O dialog e a listagem parciais do contas e a listagem de liquidacoes do negocios sao apagados
- [ ] #28 M6.1.5 No PDV o caixa recebe notinha e entrega paga na volta em dinheiro, PIX QR, cheque e cartao, sem trocar de app
- [ ] #29 M6.1.6 No PDV o Gerente paga vale/credito do cliente em dinheiro ou registrando cancelamento no cartao ou devolucao de PIX, apontando para o pagamento original
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

M3 em andamento (30/09/2026): cadastro único de maquinetas.

M3 decidido item a item em 30/09/2026 (detalhe no doc-3, seção M3 'O que mudou'): serial casa por serial+filial senão vira manual; carga com todos os POS/pinpads (inativos inativos); Saurus = pinpad, substituído inativo; manual continua pedindo parceiro e pula maquineta se única, obrigatória; maquineta de site compartilhada (coluna nova) para Brasil Card/Le Card/MultVale na 101; Gerente só na própria filial; PDV antigo resolve serial ou cria manual; histórico sem aparelho em 'Histórico Stone/SafraPay/Cielo Lio' inativas por filial (pessoa Cielo criada); juntar maquinetas; S2Pay e POS PagarMe passam para contas → Maquinetas. DDL api/database/maquineta.sql rodado em dev.

M3 implementado em dev em 30/09/2026, aguardando validação (ACs M3.x desmarcados até o OK). DDL api/database/maquineta.sql rodado 2x em dev (idempotente). Backend Mg/Maquineta + select; contas → Cadastros → Maquinetas (filtros, cadastro manual/Stone integrada/SafraPay por QR, parear de novo, juntar, inativar); @components/MgSelectMaquineta; negocios FormaCartao pela lista (recentes primeiro, pula se única), tela Saurus/S2Pay removida. Testado com rollback: service, endpoints pelo kernel HTTP (inclusive Gerente de outra filial = 403), vincular PagarMe/Saurus, serial do PDV antigo. Não testado: QR/pareamento Saurus (chama a API real, precisa de pinpad).

Pareamento SafraPay por QR fica para validar no go-live (decisão do Fábio, 30/09/2026): dev sem SAURUS_S2PAY_* no api/.env e PDVs Saurus do dev compartilham ids com produção. Falha da API Saurus agora volta 502 com mensagem; dialog de parear não trava mais no carregando.

M3 validado pelo Fábio em 30/09/2026 (contas → Maquinetas, negocios Receber → Cartão). Pareamento SafraPay por QR confere no go-live. M3.2 fica aberto: o parceiro do cartão manual ainda vem da lista fixa (cartoes-manuais.json) e uma maquineta de adquirente nova (ex.: Cielo) não aparece no PDV — ajuste em seguida, fora do commit do M3.

M3.2, ajuste em dev (30/09/2026, fora do commit 78cd48526, aguardando validação): parceiros do cartão manual = fixos + toda adquirente com maquineta ativa na filial (Cielo etc., padrão da Stone, sem logo); fixo sem maquineta na filial fica desabilitado; Receber → Cartão sincroniza o estoque local a cada abertura (online). MaquinetaService::paraPdv devolve o nome da adquirente.

M3.2 validado pelo Fábio em 30/09/2026 (Cielo no passo parceiro).

M4 em andamento (30/09/2026): pagamento e parcelas no lugar da forma de pagamento da venda. Levantamento antes de codar.

M4 decidido item a item com o Fábio (30/09/2026): (1) NFe de Terceiros do MGsis grava parcela/codnegocioparcela — NfeTerceiro.php e Titulo.php do MGsis ajustados, sobem junto; (2) vale gerado na devolução = parcela condição nova V (título pelo tipo da natureza, como compra); (3) tblpagamento.codtitulo = vale consumido; (4) total = o que ficou (principal+juros+multa−desconto), troco à parte, Σ total = total da venda, NF-e vPag = total+troco; (5) sem coluna de forma antiga: codformapagamento deduzido de meio/condição/integração (view e formato antigo); (6) parcela do histórico = título (valor e vencimento), sem título = uma parcela com o valor da forma; (7) cartão do histórico sem tipo = meio 99 outros; (8) último dia útil = seg a sáb sem feriado (tblferiado); (9) histórico corrigido: formas de valor zero não são copiadas, os 4 cartões negativos viram pagamento contrário (origem = portador da adquirente, codpagamentoorigem = cartão de mesma autorização), CHECK principal > 0.

M4 implementado em dev em 30/09/2026, aguardando validação (ACs M4.x desmarcados até o OK). DDL api/database/pagamento.sql (seção 1) rodado em dev e de novo sem efeito (idempotente): 4.732.518 pagamentos, 620.204 parcelas, 270 formas de valor zero não copiadas, 4 contrários; reapontados 611.378 títulos, 3.174 movimentos, 0 cheques. Conferência fora do script contra a cópia de antes (banco m4antes no container): 298 negócios com total diferente, todos explicados (174 parcela = título alterado, 117 troco em dobro, 7 forma incoerente); 67 negócios só com forma zero. DIMP de jul/2026 igual por tPag (dinheiro −3 formas de troco total, líquido igual). vwnegocioformapagamentototais redefinida (vwnegocio/vwnegocio_listagem dependem). Backend: Mg/Pagamento (model, service, resource), NegocioParcela + service + resource, NegocioFormaPagamentoService = tradutor do formato antigo (sai no M5); sync, fechar, cancelar, NF-e, DIMP, romaneio, vale, cheque, conferência, listagem, títulos, PIX/PagarMe/Saurus/Lio/Mercos, NFe de terceiros, devolução. contas: Detalhe do título. MGsis: NfeTerceiro.php, Titulo.php, _grid_titulos.php, NegocioParcela.php (sobem junto). Testado com rollback (tinker): todas as formas, dividido, negativo, sync repetido/troca de forma, cancelar, NF-e/NFC-e, integrações. Não testado: fechar boleto (registra no BB), importação de NFe de Terceiros (consulta a SEFAZ; uma tentativa em dev esbarrou em '656 Consumo Indevido', limite de consultas/hora) e a tela do MGsis.

M4 commitado sem validação a pedido do Fábio (30/09/2026): valida junto com o M5. ACs M4.x seguem desmarcados até a validação.

M5 em andamento (30/09/2026): wizard de cobrança desacoplado e prazo com vencimento ajustável. Conferência do banco e do código antes de codar.

M5 implementado em dev em 30/09/2026 (ACs M5.x desmarcados até a validação, junto com M4 e M6). DDL api/database/cobranca_documento.sql (tblsauruspedido.codnegocio nulo), rodado 2x em dev. PDV novo se identifica com o cabeçalho X-Pagamento-Formato: 2 (sync e NegocioResource no formato novo via Mg/Pdv/PdvNegocioPagamentoService; cobranças devolvem a cobrança). negocios: store cobranca.js, ReceberDialog e Forma*.vue sem negocioStore (emitem pagamento/parcelas/cobranca), FormaPrazo com editor de vencimento e valor, juros editável no crédito, desconto (tecla −) só no dinheiro, Dexie v8 migrando os negócios do aparelho, stores pix/pagar-me/saurus avisam por cobrancaAtualizada, filtro da listagem por meio/condição, CODFORMAPAGAMENTO_* fora do .env de dev. Backend: desconto do pagamento sai do total do negócio (confereTotais, recalcularTotal) e é rateado nos itens na NF-e; fechar recusa parcela vencida; títulos numerados por condição. Conferido: lint, php -l, 20 cenários por serviço com rollback (todas as formas, Σ total + Σ parcelas = total), NFC-e com desconto, e no navegador dinheiro com desconto + crediário 2x editado fechando sozinho (negócios de teste cancelados). Não testado: PIX QR e cartão integrado de ponta a ponta com banco/maquineta reais (simulados no serviço); migração Dexie com negócio antigo de verdade no aparelho.

Dúvidas para o Fábio (M5):
1. Desconto por forma: qual percentual sugerir no dinheiro (hoje 0%, o operador digita com a tecla −)? E no PIX: o QR não tem onde guardar o desconto (tblpixcob) e PIX por chave é parcela — quer desconto no PIX? Precisa de coluna nova no tblpixcob.
2. O desconto do pagamento é rateado nos itens só na nota fiscal (o item não separa desconto digitado de desconto do pagamento sem coluna nova). Está bom assim ou prefere uma coluna valordescontopagamento no item, como o valorjuros?
3. Quando tirar o formato antigo (tradutor NegocioFormaPagamentoService, uuidforma, cabeçalho)? Proposta: algumas semanas depois do go-live, numa limpeza própria.

M6 em andamento (30/09/2026): pagamento no lugar da liquidação. Conferência do banco e do código antes de codar.

M6 implementado em dev em 30/09/2026, na árvore, sem commit (ACs M6.x desmarcados até a validação de M4+M5+M6). DDL api/database/pagamento_liquidacao.sql (script próprio, depois do pagamento.sql), rodado 2x em dev: 175.766 liquidações → pagamentos (código novo, codliquidacaotituloantigo), 436.875 movimentos reapontados, dinheiro por portador e mês igual (conferência dentro do script); view tblliquidacaotitulo conferida contra o dump (debito/credito iguais, menos 8 liquidações antigas com débito negativo); 229 encontros mistos com juros/multa/desconto zerados no pagamento; pseudoportadores inativados. Backend: Mg/Pagamento/PagamentoTituloService::{receber, pagar, estornar, daBaixa}, autorizador, controller (rotas v1/pagamento), resources, relatório e recibos; movimento perdeu codliquidacaotitulo; boleto BB e retorno Bradesco criam pagamento por baixa; acerto de RH cria pagamento por evento (inativar cancela, reativar cria outro); v1/pdv/pagamento no lugar de v1/pdv/liquidacao. contas: Recebimentos e Pagamentos (lista, detalhe, receber/pagar sem gaveta, estorno com justificativa, recibos, relatório). negocios: listagem de pagamentos com título (filtros corrigidos). pessoas: link do pagamento no evento do acerto. MGsis: MovimentoTitulo.php declara codliquidacaotitulo como propriedade (sobe junto). Conferido: php -l, lint contas/negocios/pessoas, simulação por API com rollback (receber em banco com juros/multa/desconto, recibo PDF, estorno e estorno repetido, pagar fornecedor com PIX, encontro de contas e estorno, sem portador 422, gaveta 422, cofre em dinheiro, listagem/filtros/código antigo/detalhe histórico, relatório HTML e PDF, listagem do PDV, acerto B/D/F + inativar/reativar, boleto BB reprocessado), M5 refeito depois do M6 sem regressão, e as telas do contas abertas no navegador sem erro. Não testado: tela do MGsis (console sem conexão), Totais de Caixa do MG Lara na tela (só a consulta dele contra a view).

Dúvidas para o Fábio (M6):
1. Acerto de RH sem portador: o evento não diz de qual conta saiu (B) nem de qual caixa (D). Fica sem origem até o M10/M11, ou o acerto passa a pedir o portador?
2. Forma F do acerto: usei o meio folha (92); o doc diz 'F folha = compensação'. Folha ou compensação (91)?
3. Meio do histórico para portador adquirente (Asaas, Mercos Pay, Cielo, Mercado Pago) = transferência e cartão da empresa = crédito; 'outros' (Carteira, Cobrador Externo, Brad Expresso, Pagfacil) = outros; Programação Pagamentos = compensação. Confere?
4. Programação Pagamentos (202016) foi inativado com os pseudoportadores, mas o RH (recarga Bee) cita ele como portador de título a pagar. Reativo?
5. Baixas antigas de boleto BB pela API (5,6 mil, sem liquidação) e eventos de acerto que existirem em produção no go-live ficaram sem pagamento. Cria no script?
6. A liquidação podia ser editada (pessoa, portador, data); o pagamento não. Tudo bem só estornar e lançar de novo?

M4 validado pelo Fábio em 01/10/2026.

Respostas do Fábio às dúvidas de M5/M6 (01/10/2026):
1. Desconto por forma: fica o do dinheiro (tecla −); a regra completa (forma de pagamento + categoria de cliente) vai para a TASK-190 (criada a pedido, High).
2. Desconto do pagamento rateado pelo PDV na coluna valordesconto que já existe nos itens/vales (sai o rateio feito na NF-e).
3. Formato antigo do PDV (tradutor, uuidforma, cabeçalho) sai junto no go-live.
4. Acerto de RH passa a pedir o portador (B banco, D caixa/cofre), gravado como origem do pagamento.
5. Forma F do acerto = meio folha (92).
6. Meio do histórico das liquidações confere.
7. Recarga Bee não usa Programação Pagamentos: o título nasce sem portador; quem diz o portador é o pagamento, quando o financeiro paga.
8. pagamento_liquidacao.sql cria também os pagamentos das baixas antigas de boleto BB e dos eventos de acerto existentes.
9. Pagamento de título volta a ser editável como a liquidação (pessoa, portador, data).

Respostas aplicadas em dev em 01/10/2026 (vão no commit do M6, junto com a correção do M5):
- Desconto do pagamento rateado pelo PDV no valordesconto dos itens/vales (fatia em valordescontopagamento, devolvida pelo NegocioResource com a mesma conta); saiu o rateio da NF-e e o ajuste de total no servidor.
- Formato antigo do PDV removido: NegocioFormaPagamentoService apagado, cabeçalho X-Pagamento-Formato fora, uuidforma derrubada no cobranca_documento.sql (view tblnegocioformapagamento agrupa por condição). Achado e corrigido: o romaneio ainda lia o formato antigo (500 no romaneio).
- Acerto de RH: B é Recarga Bee (não banco) → compensação sem portador (resposta do Fábio); D pede caixa/cofre (espécie), origem/destino pelo sinal do saldo; F folha. Reativar usa o mesmo portador.
- Recarga Bee sem portador: título nasce sem portador.
- pagamento_liquidacao.sql seção 5: 70.600 baixas de boleto (BB + Bradesco) e 16 eventos de acerto ganharam pagamento em dev; a view do MG Lara não mostra boleto nem acerto.
- Edição do pagamento de título (PUT v1/pagamento/{id}: pessoa, portador, meio, data, observação), exceção à regra de imutabilidade.
Conferido: php -l, lint negocios/contas/pessoas, simulação M5 (todas as formas, fatia do desconto devolvida) e M6 (inclui edição, edição para gaveta = 422, acerto D sem portador = 422, B/D/F), romaneio renderizado, smoke no navegador (desconto rateado no item: desconto 5, fatia 5, total 295; crediário 2x fechando).

M6.1 em andamento (01/10/2026): wizard, formas e integrações em @components; contas usa o wizard; listagem única de pagamentos; receber notinha e pagar vale do cliente no PDV (absorve o M7). Levantamento antes de codar.

M6.1 decidido com o Fábio (01/10/2026), depois do levantamento:
1. PIX QR e cartão integrado em recebimento de título: na confirmação o servidor cria o pagamento efetivado sem título; a tela manda o codpagamento ao finalizar (finaliza sozinha quando zera o saldo). Tela fechada no meio = pagamento avulso sem título na listagem. Sem DDL.
2. Contas, saída: banco, dinheiro de cofre/troco/Caixa Financeiro, cartão da empresa, compensação e cheque (emitido pela empresa).
3. Listagem do negocios por v1/pdv/pagamento (autorizada pelo dispositivo, PDV forçado no servidor).
4. Rotas de criar cobrança integrada com PDV opcional, para o contas ter PIX QR e cartão integrado.
Divergências do plano relatadas e mantidas: dialogs PIX/PagarMe/Saurus, useConsultaAutomatica, LogoPagamento, utils de pagamento e logos também vão para @components; @components sem moment/mitt (qrcode com alias no contas e no negocios); store compartilhado de PIX com id pixCob (o contas já tem 'pix'); filtros e estado da listagem também em @components; vários pagamentos para os mesmos títulos distribuídos por vencimento no backend.

M6.1 implementado em dev em 01/10/2026, na árvore, sem commit (ACs M6.1.x desmarcados até a validação). Detalhe no doc-3, seção M6.1 'O que mudou'.
@components: MgCobrancaDialog + cobranca/ (Forma* incl. FormaPortador e FormaEstorno novos, ListaOpcoes, ListaFiltravel, LogoPagamento, dialogs PIX/PagarMe/Saurus, pagamento.js, juros.js, eventos.js, logos), stores cobrancaStore/pixStore (id pixCob)/pagarMeStore/saurusStore/baixaTitulosStore/pagamentoListaStore, MgPagamentoLista/Filtros/Detalhe, MgSelectPdv.
negocios: venda pelo wizard de @components; Receber Título / Pagar Vale (F11, stores/pagamento.js); menu Pagamentos (/pagamento, travada no PDV); apagados ReceberDialog, receber/*, stores cobranca/pix/pagar-me/saurus/liquidacao, LiquidacaoListagem (page/layout/drawer).
contas: Receber ou Pagar Títulos = seleção de títulos + wizard (sem gaveta); menu Pagamentos com a listagem única; detalhe com edição e recibos; pagamentoStore apagado; qrcode no package.json; ícones MDI.
Backend: PagamentoTituloService::baixar (várias formas, distribuição por vencimento), PagamentoListaService + resources (listagem única), PagamentoController (era PagamentoTituloController), CobrancaService/CobrancaController (cobrança sem PDV), PdvPagamentoService/Controller, PIX/PagarMe/Saurus criam pagamento sem negócio na confirmação, codpagamento nos resources das cobranças, v1/select/pdv, recibo térmico.
Conferido: php -l, eslint negocios/contas e da cópia de @components (só o MgSelectCargo, que já falhava), prettier, quasar build dos dois apps; serviço com rollback (contas banco/dinheiro/cartão manual dividido, compensação, cheque recebido e emitido, cartão da empresa, PDV dinheiro, vale em dinheiro, devolução parcial no cartão acima do original = 422, cobrança integrada amarrada, estorno com cheque, listagem/filtros/documento); navegador (Chrome headless): contas listagem, detalhe, baixa em Banco e dividida cartão manual 2x + dinheiro do cofre; PDV listagem travada, F11 com notinhas, dinheiro parcial + Finalizar parcial (distribuiu por vencimento) e vale pago em dinheiro da gaveta. Dados de teste estornados/cancelados e gaveta do PDV 508 restaurada.
Não testado: PIX QR e cartão integrado de ponta a ponta (banco/maquineta reais); recibo na térmica (dev sem impressora); cheque no navegador.
Achado (não corrigido, fora do escopo): cheque com nome do emitente e sem CPF/CNPJ grava emitente com CNPJ vazio e o banco recusa — deve afetar o fechamento da venda em cheque sem CPF/CNPJ (PdvNegocioChequeService::gerar → ChequeService::sincronizarEmitentes). No recebimento de título só mando o emitente com CPF/CNPJ.

M6.1 commitado sem validação a pedido do Fábio (01/10/2026): ele valida depois. ACs M6.1.x seguem desmarcados até a validação.

M8 em andamento (01/10/2026): vale colaborador e adiantamentos no PDV. Conferência do código do M6.1 antes de codar.

M8 decidido com o Fábio (01/10/2026), depois do levantamento: (1) conta contábil com padrão por tipo, editável no dialog (2 → 42 Despesa Colaboradores, 120 → 1 Compra Mercadoria, 220 → 2 Venda Mercadoria); (2) vencimento em campo, padrão +30 dias; (3) estorno pela listagem de pagamentos: TituloService::estornar leva total/codpagamento ao estorno e cancela o pagamento (vale também para o contas), e o estorno de pagamento cuja linha é a implantação chama ele — sem rota nova; (4) sem atalho de teclado, só botão no PDV.

M8 implementado em dev em 01/10/2026, na árvore, sem commit (detalhe no doc-3, seção M8 'O que mudou'). Backend: TituloService::criar/implantar com pagamento (implantação com total = valor e codpagamento), TituloService::estornar leva total/codpagamento ao estorno e cancela o pagamento (abort 422 no lugar das exceções), PagamentoTituloService::estornar desfaz o título quando a linha é a implantação; Mg/Pdv/PdvTituloService::lancar + PdvTituloController + PdvTituloStoreRequest (POST v1/pdv/titulo; um título por forma; vale e adto fornecedor só dinheiro da gaveta; adto cliente pelas formas do Receber título; Admin ou Caixa/Gerente da filial); recibo térmico com o tipo, observação e assinatura. negocios: LancarTituloDialog + store pagamento.js (reusa baixaTitulosStore e MgCobrancaDialog sem mudar @components), botão Vale / Adiantamento no PDV. Conferido: php -l, eslint/prettier; serviço com rollback (vale, adto fornecedor, adto cliente dinheiro com troco + cartão manual, PIX integrado amarrado, recusas: vale em cartão, tipo 230, vencido, PIX reusado, PDV sem gaveta; estorno pela listagem, repetido, estorno do título no contas cancelando o pagamento, título movimentado = 422; recibos PDF); navegador (Chrome headless, PDV 508 com gaveta temporária): vale R$ 50 e adto cliente R$ 100 com troco 20 pelo wizard, estornados pela rota da listagem do PDV, gaveta do PDV 508 restaurada. Não testado: PIX QR e cartão integrado de ponta a ponta (banco/maquineta reais), impressão na térmica (dev sem impressora). ACs do M8 não lançados: a task 'Receber no balcão' (dona dos ACs M8/M9 pelo doc-3) não existe.

M8 commitado sem validação a pedido do Fábio (01/10/2026): ele valida depois.
<!-- SECTION:NOTES:END -->

---
id: TASK-39
title: Fazer tela de fechamento de caixa
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-06 23:30'
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
- [ ] #3 M12 - Financeiro fecha periodo de cofre e banco pela data de corte; fechado e imutavel; reabre e fecha em ordem (em cadeia)
- [x] #4 Itens do caixa (chips, ingressos) contados no portador em espécie junto com as cédulas (como cédula, preço × quantidade): só a entrada ou a saída sem venda é lançada; vender não lança nada; cadastro de itens dinâmico (os outros tipos de item vêm um por um)
- [ ] #5 M10.1 Dinheiro, PIX, deposito, transferencia e boleto feitos depois do go-live aparecem no razao da gaveta, cofre, banco ou conta onde cairam: entrada positiva, saida negativa, na data da transacao
- [ ] #6 M10.2 Cancelar, estornar ou corrigir um pagamento (valor, portador, meio ou data, no contas ou na conferencia do gerente) acerta o razao junto; com o caixa daquele dinheiro ja conferido, so reabrindo
- [ ] #7 M10.3 O detalhe do pagamento, no contas e no PDV, mostra os lancamentos do razao, com a sessao do caixa ou o periodo em que cairam, e os desfeitos riscados
- [ ] #8 M10.4 O saldo de cada caixa, cofre e banco e o saldo inicial do periodo mais o que caiu ate hoje
- [ ] #9 M10.5 Pagamento, cheque, extrato bancario e bonificacao guardam a data e hora em que o fato aconteceu, separada de quando foi digitado (o PIX de ontem lancado hoje fica com a data de ontem)
- [ ] #10 R1 - Uma tela Portadores no contas mostra todos os portadores por filial, com o saldo da especie e a situacao de cada caixa, e substitui Saldos e Cadastros > Portadores (criar, editar, inativar e importar OFX nela)
- [ ] #11 R2 - Ao abrir um portador, os periodos aparecem em abas Ano > Mes > Periodo, e o endereco da pagina leva direto ao periodo escolhido
- [ ] #12 R3 - O periodo mostra saldo inicial, entradas e saidas por origem (vendas, titulos, transferencias, avulsos, itens) e saldo final, com a lista de lancamentos e o saldo corrente linha a linha
- [ ] #13 R4 - Transferir, lancar avulso, item do caixa, abrir, fechar e reabrir o caixa ou o periodo feitos na propria tela do periodo, que se atualiza na hora
- [ ] #14 R5 - Venda em dinheiro, sangria, confirmacao, cancelamento, recebimento no banco e fechamento com corte conferidos na tela do periodo (roteiro Valida do doc-4)
- [ ] #15 R7.1 - Ajuste de caixa lançado só no portador, sem virar pagamento, com observação; cancela com justificativa e fica visível em Mostrar cancelados
- [ ] #16 R7.2 - Sangria, reforço e depósito não viram pagamento: saem de um portador e entram no outro; ficam a confirmar quando quem registrou não é gestor do destino; o período não fecha com transferência a confirmar, chegando ou saindo
- [ ] #17 R7.3 - Cada portador tem sua lista de usuários com role (cadeado ao lado do editar): depositante só manda dinheiro para ele, operador vê e movimenta, gestor também confirma, reabre e cuida da lista; os selects de origem e destino só mostram os portadores do usuário; Administrador é gestor em todos; o PDV não valida
- [ ] #18 R7.4 - O período em espécie começa com a contagem final do anterior; a contagem inicial só confere e mostra se não bater
- [ ] #19 R7.5 - Fechar com diferença até a tolerância do portador (padrão R$ 2,00) fecha e registra a diferença; acima fica pendente até ser corrigido, sem travar o dia seguinte
- [ ] #20 R7.6 - Reabrir deixa corrigir a contagem final, do período mais novo para o mais antigo
- [ ] #21 R7.7 - Dividir um período numa data de corte e unificar dois períodos
- [ ] #22 R7.8 - Abrir o período novo logo depois de fechar o anterior, no mesmo segundo, não dá erro (começa no próprio fim do anterior)
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

R6 (03/10/2026, na árvore, sem commit; processo único do caixa): Portador::ehCaixa() = espécie (sessão: abre, só movimenta aberta, conta, fecha) e ehGaveta() = espécie com PDV só no que é do PDV (itens do caixa, grupo Caixa, baixa de título no contas, texto Sangria/Suprimento). CaixaService: abrir sem contagem obrigatória e sem ajuste; fechar exige a contagem do fechamento = saldo final (PortadorPeriodoService::exigirContagemBate, 422 dizendo o ajuste que falta); reabrir o fechado mais novo (a última volta a ficar aberta, anterior mantém o fim e só destrava correção; avulso nela cai no fim); permissão por portador (CaixaService::podeOperar(Portador)). Contagem: POST v1/portador-periodo/{id}/contagem (PortadorPeriodoService::contar, sessão não fechada, itens só na gaveta); resource 'contagem' {abertura, fechamento: contagem, contado, diferenca}. Cofre/troco/Financeiro deixam o fechar com corte e o avulso pelo período (M12 fica para banco, adquirente e cartão). Painel: situação e bloqueio 'Caixa não aberto' para toda espécie. Front contas: Abrir/Fechar/Reabrir caixa só confirmação; o único dialog é o da contagem, aberto pelo botão ao lado do saldo inicial e final no resumo; ContagemCaixa em duas colunas com hint do valor, totais e total geral. Tela do caixa do PDV (MgCaixaSessao, negocios /caixa e Fechamentos → sessão) NÃO adaptada, a pedido do Fábio (ele refatora o PDV depois). Conferido no tinker com rollback (cofre: fechar sem contagem/com diferença = 422, ajuste e fecha, transferência para fechado = 422, abrir, reabrir com outra aberta, refechar, saldo inicial seguinte recalculado; gaveta fechando e abrindo com contagem junto). Também na árvore: timeline dos lançamentos refeita e tela do período em duas colunas.

Contagem (03/10/2026, na árvore): ContagemCaixa em duas colunas (cédulas | moedas), o valor de cada campo no hint, total por coluna, total dos itens e total geral; vale para o dialog do período e para a tela do caixa (MgCaixaSessao). totalContagem do caixaSessaoStore saiu (só o componente usava).

Commitado em 03/10/2026 a pedido do Fábio, sem validação (57513c029): o R6 que estava na árvore, construído sobre as definições antigas, e a redefinição do domínio do dinheiro no doc-4 (seção 'Redefinição do domínio do dinheiro'). Roles do portador (depositante, operador, gestor) definidas em seguida. Próximo: etapa 1 do plano (DDL no dev e migração), só com o OK do Fábio.

Redefinição do dinheiro executada em dev em 03/10/2026, na árvore, sem commit (doc-4, seção 'Redefinição do domínio do dinheiro' → 'Como ficou no código'): DDL portador_movimento_tipo.sql rodado, ajuste e transferência como movimento do portador, papéis por portador, período em espécie com contagem, tolerância e pendente, dividir e unificar. Conferido no tinker e pelas rotas HTTP com rollback; tela do portador e painel abertos no navegador (só visualização). ACs R7.x desmarcados até a validação do Fábio.

Itens do caixa redefinidos com o Fábio (03-04/10/2026; doc-4, seção 'Itens do caixa: chips'): começar só pelos chips de celular (nossos, sem parceiro nem acerto). O chip conta como cédula: saldo da gaveta inclui os chips, contagem = cédulas + moedas + chips, uma diferença só; o único lançamento é a entrada/saída (tipo I no movimento do portador, sem pagamento); cadastro mínimo (nome, filial). Executado em dev, na árvore, sem commit: DDL api/database/caixa_item_dinamico.sql só acrescenta (tipo I, codcaixaitem/itens no movimento, contagemitensinicial/final no período, modo opcional, 5 itens não-chip inativados), rodado 2x; o Fábio apaga à mão o que saiu do código (tblcaixaitemlancamento, tblpagamento.codcaixaitemlancamento, tblcaixaitem.modo/codpessoa/codcontacontabil/ordem e os 5 itens). Saíram: pagamento do item na gaveta, título de repasse no fechamento e estorno na reabertura, linhas de item criadas sozinhas, recusa de dividir/unificar com item, origem 'Item do caixa' nos pagamentos, contas → Movimento dos Itens, item no PDV (MgCaixaSessao ganha o chip na refatoração dele). Contas: botão 'Entrada de chips' nos lançamentos do período da gaveta e chips no diálogo da contagem; borderô com os chips na contagem. Conferido no tinker e pelas rotas HTTP com rollback. AC #4 desmarcado até a validação. Anotado para depois (sem task): item sem estoque com borderô e foto, acerto com o parceiro (título a pagar), ingressos com variações.

Ajuste dos chips (05/10/2026, com o Fábio, depois de ver a tela): chip vale em qualquer portador em espécie, não só na gaveta; sem vínculo a cadastrar (o item entra na contagem do portador na primeira entrada e passa de um dia para o outro até zerar); filial saiu do cadastro (falta o drop de tblcaixaitem.codfilial no dev); tela do item no contas com os registros dele (entradas e saídas nos portadores, link para o período), ações no cabeçalho e a lista só navega. Conferido pelas rotas HTTP com rollback (troco: entrada, contagem, fechar, abrir com os chips, zerar e sumir no dia seguinte; movimentos do item).

Saldo do chip por caixa (06/10/2026, a pedido do Fábio, na árvore, sem commit): a tela do item no contas ganha o card 'Saldo nos caixas' acima dos registros: para cada portador que já contou o item, a contagem final do último período com fim (fechado pelo caixa, mesmo pendente), com as linhas, a quantidade e o total, levando ao período; o caixa cujo último fechamento não tem o item (zerou) fica de fora. GET v1/caixa-item/{id}/saldo (CaixaItemService::saldos, SQL cru com DISTINCT ON); PortadorPeriodoResource::textoLinhas reaproveitado pelo detalheItem. Conferido no tinker.

Saldo do chip por caixa, ajuste (06/10/2026, a pedido do Fábio, na árvore, sem commit): linha Total no card; clicar no caixa abre o popup dos fechamentos dele (todo período com fim, do mais novo para o mais antigo, rolagem infinita de 30 em 30), cada um com a contagem final do item (zerada se não tinha) e levando ao período. GET v1/caixa-item/{id}/saldo/{codportador} (CaixaItemService::fechamentos); CaixaItemFechamentosDialog no contas. Conferido no tinker.

Popup dos fechamentos do chip (06/10/2026, a pedido do Fábio, na árvore, sem commit): só os períodos em que o item mexeu (contagem do fechamento diferente da abertura, ou entrada/saída do item não cancelada no período); colunas abertura, fechamento e diferença (fechamento − abertura), em valor e unidades. Conferido no tinker: Caixa Atacado só o 81, Caixa 06 Centro o 91 e o 92.

Tela do portador no padrão das telas de detalhe (06/10/2026, a pedido do Fábio, na árvore, sem commit): voltar, nome, badges, saldo e ações num q-item fora do card, como na tela do item do caixa; o card ficou só com as abas Ano → Mês → Período (ou o Abrir novo período).

Dialogs do portador, do período, do caixa e do item no padrão da tela de títulos (06/10/2026, a pedido do Fábio, na árvore, sem commit): título text-grey-9 text-overline em maiúsculas, linha (q-separator inset) abaixo do título e acima dos botões; a explicação que ficava sob o título virou o primeiro texto do corpo. PortadorDialog, PortadorUsuariosDialog, OfxDialog, os 4 do PeriodoCabecalho (contagem, início e fim, dividir, fechar com corte), Avulso/Item/TransferirCaixaDialog (@components, valem no PDV também), CaixaItemDialog e CaixaItemFechamentosDialog. Fora: CorrecaoPagamentoDialog e os de fechamento/Index e fechamento/Venda (outras telas).

Tela do item reorganizada (06/10/2026, a pedido do Fábio, na árvore, sem commit): saiu o card das entradas e saídas (e a rota v1/caixa-item/{id}/movimento); o card do saldo lista todo caixa que já mexeu com o item (contou ou lançou), com 'Mostrar caixas sem saldo' (desligado: só os com saldo) e o total dos que aparecem. O popup do caixa traz o período aberto no topo e as colunas abertura, entradas (com cada lançamento, quando e quem), fechamento e vendido (abertura + entradas − fechamento). Usuário vazio ('05/10/2026 18:01 ·'): optional($m->UsuarioCriacao)->usuario num MgModel cai no acessor getUsuariocriacaoAttribute (nome de método no PHP não diferencia maiúsculas) e devolve a string, então o ->usuario saía nulo; trocado por $m->usuariocriacao / ->usuarioalteracao em 14 pontos (Caixa, Portador, Conferência, Pagamento). Conferido no tinker.

Popup do caixa: 'Vendido' virou 'Diferença' (06/10/2026, a pedido do Fábio: o chip pode ter ido para outro caixa), fechamento − abertura − entradas, com o sinal da diferença da contagem (negativa em vermelho).

Contagem (06/10/2026, a pedido do Fábio): no diálogo da contagem inicial/final o chip tem só o campo de quantidade por preço; '+ Preço', o X e '+ Item' saíram de lá e ficam só na entrada de chips (LinhasItemCaixa com a prop entrada).

Entrada de chips, linha nova de preço: Descrição, Preço, Quantidade nessa ordem, todos obrigatórios (preço ≥ 0,01, quantidade ≥ 1), validados no formulário.

Popup do caixa, totais (06/10/2026, alinhado com o Fábio): três cards em cima (Entradas, Saldo, Diferença negativa em vermelho) com o total dos períodos fechados, calculado no servidor (meta.totais só na 1ª página; a lista é paginada); entradas + diferença = saldo. Saiu a linha 'Total dos fechados'. Ctrl+clique num período abre noutra aba sem fechar o popup (saiu o v-close-popup; o popup fecha ao desmontar a tela).

Saldo nos caixas com as entradas do dia (06/10/2026, alinhado com o Fábio): saldo = contagem final do último período fechado + entradas e saídas do item depois dele (o período aberto), como o dinheiro; ingressos recebidos hoje já aparecem. Caixas do item pelas entradas (o item só entra no caixa por entrada), sem varrer o jsonb dos períodos. EXPLAIN ANALYZE: só index scan (codcaixaitem parcial, (codportador, inicio), pks), 0,6 ms; sem índice novo nem campo calculado. Popup ainda não mudou (próximo passo).

Popup do caixa, cards batendo com o período aberto (06/10/2026): Entradas de todos os períodos (o aberto também), Saldo = o mesmo de 'Saldo nos caixas', Diferença só dos fechados; entradas + diferença = saldo (chips 06 Centro 430 − 60 = 370; Atacado 175 − 90 = 85; ingressos 8.000 + 0 = 8.000). Cards aparecem mesmo sem período fechado; linha do aberto com 'em aberto' na diferença.

Descrições do item (06/10/2026, alinhado com o Fábio): card 'Descrições' na tela do item com os tipos distintos (descrição + preço, pelas entradas: índice codcaixaitem) e o editar, que troca a descrição de UM tipo (descrição + preço) em tudo: entradas e saídas (canceladas também) e contagens de abertura e fechamento de todos os caixas, fechados inclusive; só o texto, valores iguais; se já existe o tipo com a nova descrição no mesmo preço, juntam (o dialog avisa). GET/PUT v1/caixa-item/{id}/tipo (CaixaItemService::tipos/renomearTipo), mesmo grupo do cadastro do item (Administrador, Financeiro). Conferido no tinker com rollback: Oir 30,00 → Oi mudou o lançamento e as contagens de 91, 92 e 93.

Itens do caixa generalizados (06/10/2026, com o Fábio): não é só chip; vale para todo item que conta como cédula (chips, ingressos da Brígida). Botão 'Entrada de item' (ícone style); entrada só com linhas novas (descrição com typeahead das já usadas, preço, quantidade); totais dos fechados na tela do item; 'chips' tirado de textos, comentários e da variável do borderô; script renomeado para api/database/caixa_item_dinamico.sql, agora com os drops (conferido no dev com rollback, também na ordem do go-live). doc-4: seção 'Itens do caixa'.

Entrada de item, validação coerente tela + servidor (06/10/2026, alinhado com o Fábio): linha toda vazia é ignorada; com qualquer campo preenchido exige descrição, preço >= 0,01 e quantidade inteira >= 1; pelo menos uma linha. Servidor: CaixaItemService::validarEntrada (no lancarItem, único chamador), 422 'Linha N: ...'. Tela: as mesmas regras no ItemCaixaDialog. Contagem não muda. Conferido no tinker (ok, sem descrição, preço zero, quantidade zero e fracionada, só vazias).

Celular (06/10/2026, a pedido do Fábio; gestores vão usar no mobile): popup do caixa em tela cheia no celular, cada período em bloco (data em cima, os 4 números embaixo com o nome de cada um, sem o cabeçalho da tabela), número dos cards menor; tela do item: os ícones do cabeçalho não encolhem (col-auto) e o título ganha ellipsis; tela do portador: cabeçalho em linha que quebra, no celular saldo à esquerda e ícones à direita na linha de baixo, 'Extrato do banco' só com ícone. Desktop igual.

Cabeçalho do item no celular, 2ª tentativa: o col-auto só vale dentro de .row; o q-item do cabeçalho ganhou 'row no-wrap', aí os ícones não encolhem e o título ganha ellipsis.

Popup do caixa no celular, simplificado (a pedido do Fábio: padrão do Quasar): sem tela cheia; dialog padrão (700px / 95vw, lista com max-height 60vh); no xs cada período em bloco só com grid (col-12 col-sm, col-3 col-sm-2) e visibilidade do Quasar (nome de cada número com class xs, cabeçalho da tabela gt-xs); espaço entre colunas (q-col-gutter-x-sm). O 'nada aparece' era o maximized: o q-infinite-scroll media a lista no meio da animação e não carregava a 1ª página. Conferido em Chrome headless (Quasar 2.19.3 UMD, template real) a 360px e 1000px: carrega, layout ok.

Popup do caixa, ajustes do Fábio (06/10/2026): tela cheia no celular (:maximized="$q.screen.xs"; a lista mantém altura máxima, calc(100vh - 260px) no xs, 60vh no resto — com col/column o q-infinite-scroll não carregava); detalhe do fechamento ('1 × 25,00 Claro') some no xs; data sem 'Fechado em'/'Aberto desde': fechado em azul (fim), aberto em lilás (início); cards de mesma altura, com o nome numa linha e a quantidade na de baixo. Conferido em Chrome headless a 360 e 1000px.

Limpeza (06/10/2026): LinhasItemCaixa ficou só com a quantidade por preço (o único uso é a contagem); saíram a linha nova, '+ Preço', o X e a prop entrada, que a entrada refeita (ItemCaixaDialog) não usa mais.

Risco até a refatoração do PDV (06/10/2026, revisão de código; decisão do Fábio: deixar para a refatoração): o MgCaixaSessao do PDV abre e fecha a gaveta sem a contagem dos itens, mas o saldo já conta os itens (entradas + o que veio contado do fechamento anterior). Gaveta com chip/ingresso fechada pelo PDV acusa diferença falsa (ex.: 10 chips de 25,00 = −250,00), o período seguinte abre sem os itens e a tela do item mostra o caixa zerado. Até lá, gaveta com item fecha pelo contas.
<!-- SECTION:NOTES:END -->

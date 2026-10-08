---
id: TASK-39
title: Fazer tela de fechamento de caixa
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-07 23:31'
labels:
  - negocios
  - contas
  - api
dependencies:
  - TASK-186
documentation:
  - backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md
  - backlog/docs/doc-4 - Refatoração-das-telas-do-dinheiro-portador-e-período.md
priority: high
type: feature
ordinal: 59000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O fechamento de caixa era feito à mão no formulário de papel "Movimento do Caixa" (contagem de cédulas, moedas e chips, vendas à vista, recebimentos, sangrias, diferença) e o sistema só tinha o protótipo "Totais de Caixa" do MG Lara, que lista totais e não fecha nada.

Como ficou: o portador é o razão único do dinheiro (tblportadormovimento com pagamento, ajuste, transferência e item do caixa). Todo portador em espécie (gaveta, cofre, troco, Caixa Financeiro) abre, conta e fecha períodos, com tolerância de diferença; banco, adquirente e cartão fecham pela data de corte. Cada portador tem sua lista de usuários com papel. Tudo visto e operado em contas → Movimento → Portadores (painel) e na tela do portador e do período.

Onde está o detalhe: doc-3 (modelo de dados, decisões 1–29, M10 a M12, ordem dos DDL do go-live) e doc-4 (telas, redefinição do domínio do dinheiro, itens do caixa). Onde os dois divergem, manda o doc-4.

Fora desta task: a tela do caixa do PDV (negocios /caixa), refeita nos critérios M9.x da TASK-188. A fundação (M1 a M9) também está na TASK-188.

Consolidou TASK-33, TASK-34, TASK-48 e TASK-84 (arquivadas).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Dinheiro, PIX, depósito, transferência e boleto feitos depois do go-live aparecem no razão da gaveta, cofre, banco ou conta onde caíram: entrada positiva, saída negativa, na data da transação (M10.1)
- [x] #2 Cancelar, estornar ou corrigir um pagamento (valor, portador, meio ou data, no contas ou na conferência do gerente) acerta o razão junto; com o período daquele dinheiro fechado, só reabrindo (M10.2)
- [x] #3 O detalhe do pagamento, no contas e no PDV, mostra os lançamentos do razão, com o período em que caíram, e os desfeitos riscados (M10.3)
- [x] #4 O saldo de cada portador é o saldo inicial do período mais o que caiu até agora, gravado no período e no portador e sempre atualizado (M10.4)
- [x] #5 Pagamento, cheque, extrato bancário e bonificação guardam a data e hora em que o fato aconteceu, separada de quando foi digitado (o PIX de ontem lançado hoje fica com a data de ontem) (M10.5)
- [x] #6 Uma tela Portadores no contas mostra todos os portadores por filial, com o saldo da espécie e a situação de cada caixa, e substitui Saldos e Cadastros > Portadores (criar, editar, inativar e importar OFX nela) (R1)
- [x] #7 Ao abrir um portador, os períodos aparecem em abas Ano > Mês > Período, e o endereço da página leva direto ao período escolhido (R2)
- [x] #8 O período mostra saldo inicial, entradas e saídas por origem e saldo final, com a lista de lançamentos e o saldo corrente linha a linha (R3)
- [x] #9 Transferir, ajustar, dar entrada de item, contar, abrir, fechar e reabrir feitos na própria tela do período, que se atualiza na hora (R4)
- [x] #10 Ajuste de caixa lançado só no portador, sem virar pagamento, com observação; cancela com justificativa e fica visível em Mostrar cancelados (R7.1)
- [x] #11 Sangria, reforço e depósito entre gaveta, cofre, troco, Caixa Financeiro e banco não viram pagamento: saem de um portador e entram no outro; ficam a confirmar quando quem registrou não é gestor do destino; cancelam com justificativa; não caem em período fechado; o período não fecha com transferência a confirmar, chegando ou saindo (R7.2, antigo M11)
- [x] #12 Cada portador tem sua lista de usuários com papel (cadeado ao lado do editar): depositante só manda dinheiro para ele, operador vê e movimenta, gestor também confirma, reabre e cuida da lista; os selects de origem e destino só mostram os portadores do usuário; Administrador é gestor em todos; o PDV não valida (R7.3)
- [x] #13 O período em espécie começa com a contagem final do anterior; a contagem inicial só confere e mostra se não bater (R7.4)
- [x] #14 Fechar com diferença até a tolerância do portador (padrão R$ 2,00) fecha e registra a diferença; acima fica pendente até ser corrigido, sem travar o dia seguinte (R7.5)
- [x] #15 Reabrir deixa corrigir a contagem final, do período mais novo para o mais antigo (R7.6)
- [x] #16 Dividir um período numa data de corte e unificar dois períodos (R7.7)
- [x] #17 Abrir o período novo logo depois de fechar o anterior, no mesmo segundo, não dá erro (começa no próprio fim do anterior) (R7.8)
- [x] #18 Banco, adquirente e cartão: o financeiro fecha o período pela data de corte; fechado é imutável; reabre e fecha em ordem, do mais novo para o mais antigo (antigo M12)
- [x] #19 Itens do caixa (chips, ingressos) contados no portador em espécie junto com as cédulas (como cédula, preço × quantidade): só a entrada ou a saída sem venda é lançada; vender não lança nada; cadastro de itens dinâmico, com a tela do item mostrando o saldo em cada caixa e os períodos em que mexeu
- [x] #20 Maquineta de parceiro (Redeflex, Bilhete Agora, Rede Card), cadastrada como item do caixa, uma por maquineta: o caixa lança na tela do período o total em dinheiro do borderô do dia (negativo quando devolveu dinheiro), com a foto do borderô opcional ("sem borderô" na linha até anexar) e o valor explica o dinheiro a mais na contagem; a maquineta não entra na contagem
- [x] #21 O financeiro vê a conta corrente de cada maquineta (borderôs dos caixas, títulos gerados e ajustes como a comissão que o parceiro desconta) e gera o título a pagar ao parceiro pelo saldo, a qualquer hora, sem depender do caixa estar fechado
- [ ] #22 Validação de ponta a ponta na tela do período pelos roteiros Valida do doc-4 (o core, a redefinição, os itens e os parceiros): venda em dinheiro, sangria e confirmação, cancelamento, recebimento no banco, fechamento com contagem e com corte, itens do caixa (R5)
- [x] #23 A lista de Itens do Caixa mostra o saldo de cada item sem precisar abrir um por um: quanto tem de cada chip/ingresso nos caixas e quanto devemos a cada maquineta de parceiro, com o total
- [ ] #24 Na contagem, cada bloco (cédulas, moedas, cada item) tem um botão de copiar: a inicial copia a contagem final do período anterior; a final copia a inicial, com chips e ingressos somando as entradas e tirando as saídas do período
- [x] #25 O resumo do período em espécie segue a folha Movimento do Caixa: Moedas e Cédulas numa linha cada (entrada = contado no começo, saída = no fim), Total e Diferença no pé
- [x] #26 Saída de item só oferece o que está no caixa (saldo inicial + entradas do período), no jeito da contagem, com a quantidade limitada ao disponível
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Reorganizada em 06/10/2026: critérios renumerados por assunto; os do M11 (transferência como pagamento) e do M12 (corte no cofre) que a redefinição do dinheiro substituiu foram fundidos nos que valem hoje (#11 e #18). Marcados = construídos e commitados (cada etapa foi commitada sem validação, a pedido do Fábio); a validação ficou concentrada no #22. O histórico dia a dia que estava aqui está no git (versões anteriores deste arquivo) e o detalhe técnico, nos "Como ficou no código" do doc-3 e do doc-4.

**Etapas e commits**
- M10 razão do dinheiro e transacao = data do fato gerador: e82d49511 (02/10)
- M11 transferências entre portadores: 565647005 (02/10), refeito na redefinição
- M12 períodos no contas com corte: c570a6f7e (03/10), hoje só fora da espécie
- M13 itens do caixa e caixa numa tela só: e8ab52048 (03/10), itens refeitos depois
- Painel e tela do portador e do período (doc-4, R1 a R5): bc4354f7d (03/10)
- R6 processo único do caixa em espécie: 57513c029 (03/10)
- Redefinição do dinheiro (ajuste e transferência no movimento do portador, papéis, período com tolerância, dividir e unificar): 86138ae06 (03/10)
- Itens do caixa que contam como cédula, tela do item com saldo nos caixas e popup dos períodos, R7.8: 08f79b64d (06/10)

**O que saiu pelo caminho** (não procurar no código): ajuste e transferência como pagamento (PagamentoService::transferir, TransferenciaAutorizador, motivo A, origem X na listagem); ajuste automático na abertura e no fechamento; permissão do caixa por grupo e filial; telas Saldos, Cadastros → Portadores e Movimento → Caixas; o item do M13 (pagamento na gaveta, título de repasse ao parceiro, Movimento dos Itens, tblcaixaitemlancamento).

**DDL do go-live** (ordem completa no topo do doc-3): razao.sql, caixa_item.sql, portador_saldo.sql, portador_movimento_tipo.sql, caixa_item_dinamico.sql, caixa_item_maquineta.sql, caixa_item_saldo.sql, antes do tipo_titulo_limpeza.sql.

**Caixa do PDV refeito** (TASK-188, M9.1/M9.5/M9.7, 06/10/2026): o PDV usa a tela do período (só o aberto), conta os itens e lança o borderô da maquineta; quem fecha é o gerente, no contas. Os dois riscos que estavam aqui (gaveta com itens fechada pelo PDV acusando diferença falsa; borderô só no contas) deixam de existir.

**Maquinetas de parceiro (#20 e #21, desenho com o Fábio em 06/10/2026)**: decisões e "Como ficou no código" no doc-4, seção "Itens de parceiro". Cada maquineta é um item do caixa no modo M; o borderô é o tipo M no movimento do portador; a conta corrente da maquineta tem os borderôs (crédito), os títulos gerados e os ajustes (tblcaixaitemacerto). O antigo critério do bloquinho de ingresso com maquineta saiu: não existe hoje (Fábio, 06/10/2026). DDL do go-live: caixa_item_maquineta.sql, depois do caixa_item_dinamico.sql. **Validado pelo Fábio em 07/10/2026** (cadastro, borderô com foto, conta corrente por semana/mês/personalizado, título com a data do fechamento, estorno pelo cancelamento do débito). Falta o #23 (saldo na lista de Itens do Caixa): feito e validado pelo Fábio em 07/10/2026; desenho e "Como ficou no código" no doc-4, "Saldo dos itens na lista".

**Achado de passagem, já corrigido**: optional($m->UsuarioCriacao)->usuario num MgModel cai no acessor getUsuariocriacaoAttribute (método no PHP não diferencia maiúsculas) e devolve a string, então ->usuario sai nulo; usar $m->usuariocriacao / ->usuarioalteracao (trocado em 14 pontos de Caixa, Portador, Conferência e Pagamento).

**Virada das maquinetas** (Fábio, 07/10/2026): as maquinetas reais (parceiro, filial, conta) e o saldo inicial de cada uma (ajuste "saldo inicial" na conta corrente) são cadastrados pela tela, pelo Fábio ou pelo financeiro, depois do caixa_item_maquineta.sql; sem script.

**Resumo no jeito do papel** (#25, 07/10/2026): o quadro da espécie é montado no servidor (PortadorPeriodoResource::quadro) e desenhado igual na tela do período e no borderô térmico: Moedas e Cédulas (entrada = contagem que deu o saldo inicial, saída = contagem final), cada item do caixa com Abertura e fechamento e Movimentação, as origens com as maquinetas sob Parceiros, Total e Diferença (só avisa quando passa da tolerância). A calculadora ao lado de cada valor abre a contagem só daquele bloco (quantidade × face = total, um recalcula o outro); a contagem completa saiu. Borderô com situação e impressora também no período aberto.
<!-- SECTION:NOTES:END -->

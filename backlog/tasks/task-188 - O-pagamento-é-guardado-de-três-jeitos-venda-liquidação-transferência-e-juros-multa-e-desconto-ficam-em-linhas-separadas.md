---
id: TASK-188
title: >-
  O pagamento é guardado de três jeitos (venda, liquidação, transferência) e
  juros, multa e desconto ficam em linhas separadas
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-30 02:24'
updated_date: '2026-10-10 16:16'
labels:
  - contas
  - negocios
  - api
dependencies:
  - TASK-186
documentation:
  - backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md
  - backlog/docs/doc-4 - Refatoração-das-telas-do-dinheiro-portador-e-período.md
priority: high
type: chore
ordinal: 201000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Fundação do fechamento de caixa (TASK-39): um jeito só de guardar como o dinheiro se moveu. Hoje a forma de pagamento da venda, a liquidação de títulos e a transferência entre portadores são três registros diferentes, e juros, multa e desconto de uma baixa ficam em linhas separadas da baixa.

Desenho, decisões e o registro da execução de cada etapa: doc-3 (Plano do fechamento de caixa por milestones). Telas do caixa e da maquineta: doc-4.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Na baixa de título, juros, multa e desconto ficam na mesma linha do principal, e o histórico antigo aparece convertido com os mesmos saldos e totais
- [x] #2 Cada portador tem um tipo (espécie, banco, adquirente, cartão da empresa, outros) e cada PDV de caixa aponta para a sua gaveta
- [x] #3 As maquinetas ficam num cadastro só no contas, e o PDV escolhe a maquineta da lista em vez de digitar o serial
- [x] #4 A venda grava pagamentos e parcelas no formato novo, com nota fiscal, DIMP, romaneio e Totais de Caixa iguais aos de antes
- [x] #5 A liquidação virou pagamento: encontro de contas, acerto de RH, boleto BB e o histórico continuam funcionando
- [x] #6 Lançar no período por um botão só, que abre a lista do que fazer, cada opção no seu wizard
- [x] #7 O período do caixa unifica com o anterior pendente mesmo com diferença de contagem
- [x] #8 No PDV, receber funciona em todas as formas; na venda a prazo dá para ajustar o vencimento e o valor de cada parcela, e a venda para o fechamento mensal vence no último dia útil do mês seguinte
- [x] #9 Receber e pagar título no contas e no PDV pelo mesmo wizard (cartão, PIX, transferência, cofre, cheque, compensação), com uma listagem só de pagamentos nos dois apps
- [x] #10 No PDV o caixa recebe notinha e o gerente paga vale ou crédito do cliente, em dinheiro ou desfazendo no cartão ou no PIX
- [x] #11 Cheque só com o nome do emitente, sem CPF/CNPJ, é aceito na venda e no recebimento de título
- [x] #12 Vale e adiantamento lançados no PDV e no contas, e qualquer título de crédito do cliente paga uma compra no PDV
- [x] #13 A lista de tipos de título tem só os 14 que se usam, a receber e a pagar separados
- [x] #14 O caixa do PDV abre, conta, faz sangria e reforço, lança o borderô da maquineta de parceiro e imprime o borderô; quem fecha é o gerente, no contas
- [x] #15 Venda e recebimento em dinheiro caem no caixa aberto do PDV (sem caixa aberto, o Dinheiro fica bloqueado com o motivo) e cancelar tira do caixa
- [x] #16 Só Caixa da filial, Gerente ou Administrador recebe em dinheiro (não feito: hoje basta o caixa estar aberto)
- [x] #17 O gerente confere o cartão de cada maquineta com o borderô na tela da maquineta e seus períodos
- [x] #18 Uma baixa de título (e um vale/adiantamento) = um pagamento: o wizard vem com o valor travado (só o dinheiro calcula troco); o pagamento guarda só o dinheiro e juros, multa e desconto ficam nos movimentos dos títulos
- [x] #19 Baixa trava os títulos e reconfere o saldo: título repetido ou duas baixas ao mesmo tempo não passam; valor com mais de 2 casas é recusado
- [x] #20 Encontro de contas (títulos que se anulam) e compensação gravam no portador Encontro de Contas (antigo Programação Pagamentos), também no PDV; permissão pelo papel nesse portador
- [x] #21 Forma 'Já recebido' no wizard (só online): amarra um pagamento sem amarração que tenha saldo livre; o título baixa na data do pagamento
- [x] #22 PDV: Receber Título vem com a maquininha e a conta PIX padrão do PDV
- [x] #23 Desamarrar (todos os títulos ou um) estorna as baixas: os títulos reabrem e o pagamento continua, sem amarração, em Pagamentos não resolvidos
- [x] #24 Cancelar só o pagamento manual já desamarrado (sai do razão, cancela o cheque a repassar); integrado (PIX, maquineta, Stone/SafraPay) e boleto nunca se cancelam
- [x] #25 Lápis do pagamento: pessoa e observação; a data só do manual; meio e portador não mudam (o servidor recusa)
- [x] #26 Detalhe do pagamento mostra as amarrações com o histórico (título desamarrado riscado), o saldo, o amarrado e o livre
- [x] #27 Cancelar a venda cancela só o pagamento manual; PIX e cartão integrado ficam efetivados e saem da venda (órfãos, em não resolvidos), com a mudança na auditoria
- [x] #28 'Já recebido' na venda do PDV amarra o pagamento sem amarração inteiro na venda aberta, se couber no que falta; maior que a venda é recusado com a orientação de amarrar o excedente como adiantamento
- [x] #29 Tela Pagamentos não resolvidos (menu do contas e botão em Pagamentos do PDV): lista o que não bate (pago − devolvido ≠ amarrado), só dos portadores em que o usuário tem papel
- [x] #30 Não resolvido: amarrar a títulos (abre o Receber Título com o pagamento), lançar como vale/adiantamento, 'já lançado' (fica o integrado, o digitado é cancelado como indevido e as amarrações passam) e devolver PIX/cartão
- [x] #31 PIX pela chave que o banco confirma vira pagamento efetivado, sem documento, no razão do banco (a partir do início do razão), com a pessoa do CPF/CNPJ do pagador; aparece em não resolvidos
- [x] #32 PIX QR e Stone sem venda confirmam com a pessoa da cobrança
- [x] #33 'PIX pela chave' sai da forma Banco do contas (é só da venda, como parcela a receber)
- [x] #34 Permissão do dinheiro pelo papel do usuário no portador: receber = depositante; pagar, desamarrar, cancelar, corrigir e devolver = operador; alterar data = gestor, ou operador com o período de onde sai e o para onde vai abertos (no cartão, o portador da adquirente); o PDV só pré-seleciona a gaveta (sai a gaveta livre por codpdv)
- [x] #35 SQL de go-live api/database/pagamento_amarracao.sql: portador Encontro de Contas e os papéis que faltam (caixa/gerente depositante em adquirentes, Carteira e bancos da filial; Cobrança como o Financeiro)
- [x] #36 Venda com diferença no Fechamentos só mostra a diferença para consertar no negócio: sai o acerto da venda (perdoar, vale do colaborador, duplicata, crédito do cliente)
- [x] #37 Cartão Stone ou SafraPay de venda cancelada aparece em Não resolvidos para o caixa da filial, como entrada, com Já lançado, Devolver e Já recebido na venda; a listagem mostra a adquirente como portador e o filtro por portador acha o cartão
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Registro da execução no doc-3, seção "Registro da execução da TASK-188".

## Handoff da madrugada 09→10/10/2026 (pagamento = fato, amarração = outra coisa)

Conceito registrado no doc-3 (Decisões fechadas → 'Pagamento = fato, amarração = outra coisa'). Commits no master local, sem push:
- 77c47309b uma baixa = um pagamento (wizard com valor travado, botão Gravar, encontro de contas no portador Encontro de Contas, forma Já recebido)
- 03a28288c desamarrar ≠ cancelar; lápis só pessoa/observação (data só do manual)
- 1e6eeca87 venda cancelada não cancela integrado; Já recebido na venda do PDV
- 2d0904919 tela Pagamentos não resolvidos (contas: menu; PDV: botão laranja em Pagamentos)
- 8c4fb6507 PIX pela chave vira pagamento no razão do banco
- 44d585388 permissão pelo papel no portador
- 3336c3b40 (TASK-204) datas: limites iguais, hora obrigatória, sem fuso

SQL de go-live (depois do auditoria.sql e do ocorrencia.sql): api/database/pagamento_amarracao.sql — reativa/renomeia o 202016 como Encontro de Contas e completa os papéis (revisar a seção 2 antes de produção). Já rodado no dev.

Roteiro de teste (dev):
1. Contas /pagamento/novo: títulos → FAB → Receber → wizard (valor travado; Insert não muda; dinheiro calcula troco) → a forma aparece no diálogo → Gravar → detalhe.
2. Detalhe: Desamarrar (todos ou um título, ícone link_off) → pagamento continua, aparece Não resolvido com saldo/livre; Cancelar (ícone block) só aparece no manual desamarrado.
3. Menu Não Resolvidos: Amarrar a títulos (abre o Receber com o pagamento), Vale/adiantamento, Já lançado (só integrado), Devolver (PIX/cartão).
4. PDV: venda com PIX QR, cancelar a venda → o PIX vai para não resolvidos (dinheiro da mesma venda é cancelado); nova venda → Receber → Já recebido → amarra; offline a forma some.
5. Lápis: só pessoa e observação; data só em pagamento manual (pede justificativa; precisa ser gestor do portador).
6. Permissão: usuário só depositante no cofre recebe mas não paga por ele; caixa recebe na gaveta do PDV.
7. Data: digitar só o dia é recusado; ano errado (antes do início do razão) é recusado.
8. O #80246854 do dev: desamarrar e cancelar pelo fluxo novo.

Observações:
- PIX pela chave caído ANTES do início do razão não vira pagamento: baixar esses títulos com Banco → Transferência/TED.
- Integrado não pode ser tirado de venda aberta no PDV (a tela já não deixava); só sai pelo cancelamento da venda.
- Pagar vale/crédito no PDV agora segue o papel (operador na gaveta), não mais 'só Gerente'.
- Devolução parcial de uma venda no cartão faz o pagamento da venda aparecer em não resolvidos (pago − devolvido ≠ venda), pela regra 'o que vale é o saldo'.
- Workers da fila do dev reiniciados (queue:restart) para usar o código novo.

## Testes da madrugada (10/10/2026)

Pedido do Fábio: testar tudo, corrigir e commitar. Feito:
- Bateria pela API (rotas reais, token de admin e de usuária só Caixa, transação desfeita): 139 verificações, todas passando no fim. Cobre baixa, desamarrar/cancelar, lápis, encontro de contas, órfão/Já recebido, já lançado, devolver, vale, permissões por papel, PDV (baixa, vale, venda pelo sync com rascunho P, fechar, cancelar venda com PIX), alterar data, maquineta, PIX pela chave.
- Três rodadas de teste de tela no navegador (contas e PDV): os bugs achados foram corrigidos e verificados na terceira rodada.

Bugs achados e corrigidos (commits [FIX] TASK-188 desta madrugada):
- caixas sem papel na gaveta do PDV (travaria terça) → pagamento_amarracao.sql refaz os papéis por usuário+portador;
- compensação com valor gravava total zero;
- PDV: recibo/detalhe de PIX do banco amarrado dava 403; não resolvidos misturava filiais e cortava em 500;
- pagamento misto (receber+pagar) aparecia não resolvido com livre negativo; encontro e misto desamarram inteiros;
- já lançado não achava a transferência digitada para um PIX; PIX pela chave conta como integrado;
- recibo (contas e térmico) contava título desamarrado;
- desconto de 100% sem caminho; título zerado virava encontro;
- listas de cofre/banco/maquineta com número de atalho que não funcionava (levava a outro portador);
- data: Tab do dia para a hora perdia o dia; Tab rápido saía do diálogo;
- wizard: dinheiro digitado ficava ao trocar de forma; foco perdido após clique;
- pessoa escolhida à mão apagada ao editar juros.

Conhecido, sem mexer (componente compartilhado, só aparece em velocidade de robô): no campo de valor (MgInputValor), digitar poucos milissegundos depois do clique pode juntar os dígitos ao valor antigo. Tentei três consertos; um deles quebrou a digitação do wizard, então voltei o componente ao original.

Mudanças de regra que o Fábio precisa conhecer:
- o caixa (operador da gaveta) não altera mais a data das linhas da gaveta: alterar data é do gestor (gerente);
- pagar vale/crédito no PDV segue o papel (operador na gaveta), não mais 'só Gerente';
- desconto de 100% grava no portador Encontro de Contas e pede papel nele (Financeiro/Cobrança/Admin pelo SQL).

Deploy: rodar api/database/pagamento_amarracao.sql (revisar a seção 2), php artisan optimize (rotas novas) e reiniciar os workers da fila (queue:restart).

Dados do dev criados/alterados pelos testes de tela: pagamentos 80247775–80247881 (vários cancelados), títulos 650418–650431, PIX 80246944/80246945/80247735 amarrados em parte, rascunho de venda 4549121 no PDV 511. Tudo de valor pequeno.

Cartão órfão da venda (10/10/2026, testes depois do go-live): o pagamento de cartão da venda nunca gravou portador (codportadordestino e codportadororigem nulos; ~1 milhão de registros, Stone e SafraPay inclusive). Tudo que decidia o sentido e o portador por esses dois campos errava no cartão: o órfão não entrava em Não resolvidos para quem não é admin (filtro pelos portadores do papel), saía como saída (sem Já lançado nem Devolver), não amarrava em venda nova e na listagem virava encontro de contas (CP). Correção na leitura, sem SQL: Pagamento::portadorDoPagamento/codportadorDoPagamento caem na adquirente da maquineta e Pagamento::entrada() trata o cartão sem portador como entrada (salvo o cancelamento); usados em PagamentoPendenciaService (listar em SQL, formatar, duplicados, jaLancado, devolver), PagamentoTituloService (baixa com Já recebido, lápis), PdvPagamentoService (carregar, amarrarVenda), TituloService (vale) e PagamentoListaService (operacao CR e filtro por portador). portadorDaMaquineta lembra a adquirente no request (listagem de 48 para 14 consultas) e relê a pessoa quando a maquineta veio parcial. Testado: bateria s11 (23 checagens) e E2E no PDV como caixa.

Teste ignorado pelo Fábio (10/10/2026): ele dispensou o teste manual dele e pediu para marcar e commitar. Critérios marcados com base no que está implementado e nos testes registrados nas notas acima. O #16 (só Caixa da filial, Gerente ou Administrador recebe em dinheiro) foi superado pelo #34: a permissão do dinheiro é o papel do usuário no portador, e receber exige ser depositante na gaveta.
<!-- SECTION:NOTES:END -->

---
id: TASK-186
title: 'Títulos têm tipos sem uso, colunas duplicadas e triggers do sistema antigo'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-29 00:59'
updated_date: '2026-09-29 14:51'
labels:
  - contas
  - api
dependencies: []
documentation:
  - backlog/docs/doc-3 - Plano-do-fechamento-de-caixa-por-milestones.md
priority: high
type: chore
ordinal: 199000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Task prévia do fechamento de caixa (TASK-39). Milestone M0 do plano doc-3 (Plano do fechamento de caixa por milestones).

O modelo de títulos carrega herança do sistema antigo: o valor do título e do movimento fica repartido em duas colunas (débito/crédito) e mais quatro derivadas no título (débito/crédito total e saldo), quatro triggers criam a implantação, o ajuste e recalculam saldo e totais escondido do código, e os catálogos têm tipos de título e de movimento sem uso há anos. O razão do portador (TASK-39) precisa de um título com um valor só, com sinal, e de um único ponto que grave movimento.

Entrega em dois milestones, cada um validado antes do seguinte:
- M0.1: valor/saldo com sinal (positivo = a receber, negativo = a pagar), escritor único de movimento, triggers desligadas, leitores migrados, colunas antigas e views derrubadas.
- M0.2: catálogos enxutos (natureza e movimenta-portador no tipo de título, Vale Colaborador, Repasse Parceiro, tipos sem uso inativados).

Script DDL: api/database/titulo_valor.sql (idempotente; dev agora, produção no go-live).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 M0.1.1 Título e movimento guardam um valor só, com sinal (positivo a receber, negativo a pagar), preenchido em todo o histórico e igual a débito − crédito
- [x] #2 M0.1.2 Todo movimento de título é gravado por um ponto só (MovimentoTituloService::lancar), que grava valor e débito/crédito juntos e recalcula saldo, estorno e data de liquidação do título e o total da liquidação e do agrupamento
- [x] #3 M0.1.3 Estorno de movimento usa o mesmo tipo do movimento original e aponta para ele
- [x] #4 M0.1.4 Criar título gera a implantação no código e alterar o valor gera o ajuste; as 4 triggers do sistema antigo deixam de existir
- [x] #5 M0.1.5 Telas, relatórios e PDFs leem valor e saldo; os filtros da listagem de títulos passam a valor e natureza; o vale no PDV lê saldo
- [x] #6 M0.1.6 Conferência sum(valor) = sum(débito − crédito) fecha por título, por pessoa e por portador
- [x] #7 M0.1.7 Colunas antigas e as 4 views derrubadas, depois de confirmar que nada vivo lê
- [x] #8 M0.2.1 Tipo de título tem natureza (receber ou pagar) e a marca de que a implantação movimenta o portador
- [x] #9 M0.2.2 Vale Funcionario passa a se chamar Vale Colaborador e existe o tipo Repasse Parceiro
- [x] #10 M0.2.3 Os tipos de título sem uso e os tipos de movimento 610, 910, 920, 992 e 993 ficam inativos
- [x] #11 M0.2.4 Os 7 flags do tipo de movimento e o tipo de movimento de implantação do tipo de título deixam de existir (implantação é sempre 100)
- [x] #12 M0.2.5 Cadastros de Tipos de Título e de Tipos de Movimento e os selects refletem as colunas novas
- [x] #13 M0.2.6 Bugs de passagem corrigidos: estorno inalcançável no retorno de boleto, recibo de pagamento errado em título a crédito, duplicata de NFe de terceiro e detalhe do cheque pedindo valor inexistente
- [x] #14 M0.1.8 Agrupamento guarda o total num valor só, com sinal, igual a débito − crédito
- [x] #15 M0.1.9 Liquidação guarda só o total líquido, com sinal; o recibo de recebimento e o de pagamento continuam saindo, decididos pelos movimentos
- [x] #16 M0.1.10 Coluna sistema sai de título, movimento e liquidação; a data de gravação fica só em criação e alteração
- [x] #17 M0.1.11 No detalhe do título, o movimento estornado e o estorno dele ficam escondidos; a opção Mostrar estornos exibe os dois identificados e dizendo qual se refere a qual
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
M0.1 implementado em 28/09/2026, aguardando validação (ACs #1 a #6 ficam desmarcados até o OK; #7 depende de decisão).

Feito:
- DDL api/database/titulo_valor.sql aplicado em dev (2x, idempotente): tbltitulo.valor, tblmovimentotitulo.valor, codmovimentotituloestorno (auto-FK), backfill valor = debito - credito, 4 triggers e funções derrubadas.
- Escritor único MovimentoTituloService::lancar (dual-write, recalcula título, liquidação e agrupamento); estornar grava o mesmo tipo do original + FK; TituloService::implantar cria a implantação (tipo do catálogo, 100 quando nulo); atualizar cria o ajuste 200; estorno do título continua tipo 900 (com FK), porque o campo estornado sai dele.
- 7 escritores e 8 criadores trocados; leitores (PHP, blades, contas, negocios, notas) em valor/saldo/ehReceber/ehEstorno; filtros valor_de, valor_ate, natureza (R/P).

Achados da conferência (diferem do plano):
- MGsis (usuário mgsis_yii) ainda cria título em NFe de Terceiros e Negócios e dependia da trigger de saldo. Bloqueia o AC #7 e precisa de decisão antes de rodar em produção.
- Implantação nem sempre é 100 (600 em 4 tipos, 901 nos agrupamentos, 992 em 3, nulo em 4); 364 movimentos sem tipo, todos Rubrica RH (952).
- 241 títulos em que saldo <> soma dos movimentos (212 zerados direto no cabeçalho, quase todos vale compras antigo; 29 de valor zero sem movimento e saldo nulo). Não corrigidos.
- 16 movimentos com valor negativo ou débito e crédito juntos; um de 24/09/2026 (título N04541416-3/3 nasceu com -143,33 na venda a prazo do PDV).
- Telas de boleto Bradesco (remessa/retorno) só existem no quasar v1; a API manteve a chave debito nesses 3 retornos, lida de valor.
- Deploy: script e código sobem na mesma janela, com a API parada.
- Definições das triggers guardadas fora do repo para rollback (também estão nas migrations do MGsis).

MGsis (29/09/2026): a única tela viva é a NFe de Terceiros. Ajustados lá só os models Titulo e MovimentoTitulo (repo /opt/www/MGsis, na árvore, sem commit): gravam valor com sinal, recalculam saldo/estornado/transacaoliquidacao no lugar da trigger e funcionam com ou sem as colunas antigas. Testado em dev com rollback nos dois estados (colunas presentes e limpeza simulada). Sobe junto com o titulo_valor.sql. As demais telas de título do MGsis (menu desligado) não foram ajustadas e quebram depois da limpeza.

Limpeza e M0.2 (29/09/2026, aguardando validação junto com o M0.1).

Limpeza: derrubadas as 4 views e as colunas antigas de título, movimento e agrupamento; dual-write removido. FICAM debito/credito de tblliquidacaotitulo: o Totais de Caixa do MGLara (CaixaController) soma as duas colunas e é o que o caixa usa hoje. Guardados fora do repo os 22 valores antigos que não se reconstituem a partir de valor (movimentos com débito e crédito juntos ou negativos).

M0.2: natureza R/P no lugar dos flags debito/credito do tipo de título (pagar/receber ficam: são a carteira); movimentaportador nos 4 tipos que implantavam com 600 + Vale Colaborador; implantação sempre 100; Vale Funcionario virou Vale Colaborador; tipo novo 953 Repasse Parceiro; 23 tipos de título inativados (946 ficou ativo: natureza 72 com financeiro ligado aponta para ele); tipos de movimento 610/910/920/992/993 inativados e os 7 flags removidos (estorno agora é a lista fixa MovimentoTituloService::TIPOS_ESTORNO ou o ponteiro para o original). Naturezas ambíguas decididas pelos títulos existentes: 932 Permuta P, 948 Garantia R, 952 Rubrica RH P.

Bugs de passagem: retorno Bradesco tratava estorno com os mesmos códigos da liquidação (agora ocorrência 40; último retorno Bradesco é de 2021); recibo de pagamento ignorava juros/multa e desconto de título a pagar; duplicata da NFe de terceiro pedia coluna titulo inexistente (numero); detalhe do cheque passou a ter valor com a coluna nova.

Tipos inativados com título em aberto: 8 Remessa Conserto (2), 932 Permuta (1), 936 Provisao A Pagar (30), 943 Entrada Consignacao (1).

MGsis: Titulo.php passou a ler natureza do tipo. Importação real da NFe de terceiro testada em dev com rollback depois da limpeza e do M0.2.

Coluna sistema (29/09/2026): derrubada de tbltitulo, tblmovimentotitulo e tblliquidacaotitulo. Antes, criacao em branco recebeu o valor de sistema (121.099 títulos e 342.178 movimentos; liquidação não tinha nenhum). alteracao em branco não foi preenchida. No código, a ordem dos movimentos e a data de estorno do título passaram a usar criacao. MGsis: Titulo.php e MovimentoTitulo.php deixaram de gravar sistema.

Estornos no detalhe do título (29/09/2026, aguardando validação): a API devolve em cada movimento quem ele estorna e por quem foi estornado; a tela esconde o par e oferece Mostrar estornos. Estorno antigo (tipos 9xx, sem ponteiro para o original) continua sempre à vista, só com a etiqueta Estorno, porque não dá para saber qual movimento ele desfez.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
M0.1 e M0.2 entregues e validados pelo Fábio em 29/09/2026 (contas, negocios, pessoas, notas e a NFe de Terceiros do MGsis). Títulos, movimentos, liquidações e agrupamentos guardam um valor só, com sinal; as 4 triggers, as 4 views, as colunas antigas e a coluna sistema saíram; tipos de título têm natureza e movimentaportador; catálogos enxutos.

Pendência conhecida: debito/credito de tblliquidacaotitulo continuam no banco, gravados junto com valor, porque o Totais de Caixa do MGLara soma as duas colunas. Saem quando essa tela sair (M2 da TASK-39).

Deploy: api/database/titulo_valor.sql + código do MGspa + Titulo.php e MovimentoTitulo.php do MGsis, na mesma janela, com a API parada.
<!-- SECTION:FINAL_SUMMARY:END -->

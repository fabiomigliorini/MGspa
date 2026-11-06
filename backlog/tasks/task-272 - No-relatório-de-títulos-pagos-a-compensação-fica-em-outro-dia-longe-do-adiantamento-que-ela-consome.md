---
id: TASK-272
title: >-
  No relatório de títulos pagos, a compensação fica em outro dia, longe do
  adiantamento que ela consome
status: In Progress
assignee: []
created_date: '2026-11-06 20:23'
updated_date: '2026-11-06 20:26'
labels:
  - contas
dependencies: []
priority: high
type: enhancement
ordinal: 283000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido de 06/11/2026.

**Hoje.** O PDF Títulos pagos (contas → Portadores → Contabilidade, TASK-256) mostra os pagamentos de cada filial dia a dia, em ordem de valor (TASK-267). O adiantamento ao fornecedor aparece no dia em que o dinheiro saiu. A compensação que abate esse adiantamento na NF aparece no dia em que a NF foi baixada, muitas vezes dias depois e em outra página. Quem confere precisa procurar a compensação em outro dia para entender o adiantamento.

**O que se quer.** O adiantamento e a compensação que o consome aparecem juntos, um embaixo do outro, mesmo sendo de dias diferentes. É uma exceção de propósito à ordem por dia: na mesma página e em sequência fica mais fácil de entender.

**Regras (decididas em 06/11/2026):**
1. Só a compensação pura (encontro de contas, valor 0) se junta ao adiantamento. A baixa que usou o adiantamento e pagou a diferença em dinheiro (PIX, banco) fica no dia dela, para bater com o extrato.
2. A compensação que consome vários adiantamentos do período junta todos num bloco só. O bloco fica no dia do primeiro adiantamento, em ordem de data, com a compensação no fim.
3. Cada pagamento do bloco mostra a própria data.
4. Se o adiantamento foi dado antes do período do relatório, a compensação fica no dia dela, como hoje.
5. Os totais da filial, o geral e o resumo por portador não mudam. Só o dia que recebe o bloco passa a somá-lo.
6. Só vale para o formato novo (de out/2026 em diante): no histórico convertido, o adiantamento não tem pagamento ligado.

**Como o sistema liga os dois.** O pagamento do adiantamento tem a Implantação do título ADTO (tblmovimentotitulo tipo 100 com codpagamento, criada em TituloAdiantamentoService::lancar). A compensação (meio 91) tem a Liquidação do mesmo título. Os dois movimentos são do mesmo título, então caem na mesma filial e no mesmo fiscal/gerencial do relatório.

**Arquivos:** api/app/Mg/Pagamento/PagamentoContabilidadeService.php (agrupar) e api/resources/views/pagamento/contabilidade.blade.php.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A compensação que consome um adiantamento dado no período aparece logo abaixo dele, mesmo sendo de outro dia
- [x] #2 A compensação que consome vários adiantamentos do período junta todos num bloco, no dia do primeiro adiantamento, em ordem de data
- [x] #3 Cada pagamento do bloco mostra a própria data
- [x] #4 A baixa contra adiantamento que pagou a diferença em dinheiro e a compensação de adiantamento de antes do período ficam como hoje
- [x] #5 Os totais da filial, o geral e o resumo por portador não mudam; só o dia do bloco passa a somá-lo
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (06/11/2026), na árvore, sem commit:
- PagamentoContabilidadeService::juntarCompensacoes, chamado por agrupar() em cada filial antes dos totais. Mapeia o título implantado por um pagamento (tblmovimentotitulo tipo 100, MovimentoTituloService::TIPO_IMPLANTACAO) e une cada compensação (meio 91) aos adiantamentos cujos títulos ela liquida. A união fecha a corrente: dois adiantamentos numa compensação ou um adiantamento em duas compensações viram um bloco só. O bloco vai para o dia do membro mais antigo, em ordem de data, e se ordena no dia pela chave do primeiro (valor, data, código). O dia que fica vazio sai. Filial, geral e portador não mudam.
- View: o pagamento do bloco mostra a própria data na coluna da direita (06/11/2026 · #00000000), e só o primeiro tem o espaço antes, então os outros vêm colados.

Conferido no dev:
- Regressão: o HTML de 01 a 14/07/2026 (histórico, sem ligação) e o de 01/10 a 30/11/2026 (Todos) saíram iguais aos de antes da mudança, fora espaços em branco.
- Caso real numa transação desfeita no fim (filial Centro): ADTO 02/11 de 300 e ADTO 04/11 de 200, compensados contra uma NF de 500 em 06/11, saíram num bloco no dia 02/11. O ADTO 03/11 de 100, usado numa NF de 250 com os 150 pagos em transferência em 05/11, ficou nos dias dele. Total da filial = soma dos pagamentos = resumo por portador = 750. PDF conferido (pdftoppm).

Como testar no contas (https://sistema-dev.mgpapelaria.com.br:8086):
1. Pagamentos → Adiantamento: dois adiantamentos ao mesmo fornecedor, em dias diferentes (ex.: 02/11 e 04/11), por um banco.
2. Títulos → Novo: Duplicata a Pagar do mesmo fornecedor, no valor dos dois somados.
3. Baixe a duplicata junto com os dois adiantamentos noutro dia (ex.: 06/11): vira um encontro de contas.
4. Portadores → Contabilidade → 01/11/2026 a 30/11/2026, a filial usada → Gerar PDF. No dia 02/11, os dois adiantamentos e a compensação aparecem juntos, cada um com a sua data; o 04/11 e o 06/11 não trazem esses pagamentos.
5. Opcional: um adiantamento numa NF maior, pagando a diferença no banco. Essa baixa fica no dia dela.
<!-- SECTION:NOTES:END -->

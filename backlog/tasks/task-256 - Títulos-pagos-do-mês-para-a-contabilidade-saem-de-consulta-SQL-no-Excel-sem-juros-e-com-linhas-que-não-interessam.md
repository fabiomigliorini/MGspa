---
id: TASK-256
title: >-
  Títulos pagos do mês para a contabilidade saem de consulta SQL no Excel, sem
  juros e com linhas que não interessam
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-23 21:25'
updated_date: '2026-10-23 22:14'
labels:
  - contas
dependencies: []
priority: high
type: feature
ordinal: 265000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Todo mês o financeiro manda à contabilidade os títulos pagos no mês, para conferir com os comprovantes. Hoje isso sai de uma consulta SQL no Excel (tblmovimentotitulo tipo 600 + tt.pagar) que não roda mais desde a TASK-188 (debito/credito/codliquidacaotitulo saíram do movimento), não mostra juros, multa e desconto, e traz estorno datado no dia do estorno, compensação e linhas de adiantamento sem dizer o que são. Precisam ir também os adiantamentos dados no mês e as baixas contra adiantamento (ex.: ADTO da Beevale contra as NF 257 e 231). Pedido do Fábio em 23/10/2026.

Decisões (23/10/2026):
1. Só o formato novo (out/2026 em diante); meses antigos sem tratamento nem bloqueio.
2. Pagamento com títulos de várias filiais ou fiscal com gerencial: cabeçalho com o valor cheio do comprovante, embaixo só os títulos do filtro e o aviso 'parte de um pagamento de R$ X'.
3. Entra o pagamento de título efetivado no período, fora de venda, se saiu dinheiro (origem sem destino), se é encontro de contas ou folha (meio 91/92), ou se mexeu em título a pagar (tt.pagar). Recebimento puro fica de fora.

Implementação: botão Contabilidade no painel de Portadores (contas) abre a janela com período, filial, fiscal/gerencial e portador; GET v1/pagamento/contabilidade (PagamentoContabilidadeService, mPDF paisagem; ?html=1 para conferir o layout).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Botão no painel de Portadores abre a janela do relatório (período, filial, fiscal/gerencial, portador) e o PDF abre na janela de PDF
- [x] #2 Entram os pagamentos de título efetivados no período em que saiu dinheiro, os encontros de contas, o acerto do RH e os que mexeram em título a pagar; recebimento puro e venda ficam fora
- [x] #3 Agrupado por fiscal/gerencial, filial, portador, pagamento (por data) e títulos, com o total de cada nível no cabeçalho do grupo
- [x] #4 Cada título mostra principal, juros, multa, desconto e total; o adiantamento dado e o consumido na baixa aparecem como linhas do pagamento
- [x] #5 Pagamento com títulos fora do filtro mostra o valor cheio e quantos títulos dele estão no filtro ('1 de 3 títulos no filtro')
- [x] #6 Pagamento cancelado e linha estornada não aparecem
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (23/10/2026), na árvore, sem commit:
- api: GET v1/pagamento/contabilidade (PagamentoController@contabilidade, só Administrador/Financeiro; transacao_de/transacao_ate obrigatórios, codfilial, codportador e gerencial opcionais; ?html=1 devolve o HTML). PagamentoContabilidadeService: um SQL cru com uma linha por movimento ativo (tipo < 900, sem codmovimentotituloestorno e não estornado, como PagamentoTituloService::baixas), pagamento E, sem codnegocio, no período, que saiu dinheiro (origem sem destino) ou é meio 91/92 ou tem título tt.pagar ativo. Filial e gerencial filtram o título; portador = coalesce(destino, origem). Agrupa fiscal/gerencial > filial > portador > pagamento com totais; o pagamento com menos linhas no filtro que no todo mostra 'só N dos M títulos deste pagamento estão no filtro'. View pagamento/contabilidade.blade.php, mPDF A4 deitado.
- contas: botão Contabilidade no cabeçalho do painel /portador (Financeiro) abre ContabilidadeDialog (De/Até = mês anterior, filial, Fiscal/Gerencial/Todos, portador) e o PDF abre no MgRelatorioPdfDialog via abrirPdf.
- Ajuste sobre o plano: o aviso do parcial diz quantos títulos estão no filtro em vez de repetir o valor cheio, que já está no cabeçalho do pagamento.

Como testar: contas > Portadores > Contabilidade, 01/10/2026 a 31/10/2026, FDF, Fiscal, Gerar PDF. Prévia do layout com dados de exemplo: https://claude.ai/artifact/CooZxhTSJUzUCeSLBHYJYR

Validação de 23/10/2026:
- Abril com Todos dava 'HTML code size is larger than pcre.backtrack_limit': o pdf() sobe o pcre.backtrack_limit, como o EstoqueSaldoRelatorioService e o PdvNegocioRelatorioService.
- A pedido do Fábio o relatório passou para retrato (A4, 194 mm): a fatura fica embaixo do número e o tipo do título (e o movimento, quando não é Liquidação) embaixo da conta contábil, em cinza. Pessoa e conta quebram linha em vez de cortar.

Meio do pagamento fora do relatório (Fábio, 23/10/2026): no histórico convertido (pagamento_liquidacao.sql) todo pagamento por banco virou meio 18 Transferência pelo tipo do portador, inclusive os boletos; no wizard a escolha depende de quem lança. O cabeçalho do pagamento ficou com data, código, pessoa e observação (onde o financeiro escreve DDA, boleto, QR code). O rótulo do grupo sem portador continua Compensação/Folha.

Totais no cabeçalho (Fábio, 23/10/2026: o total do pagamento saía no começo e o do portador no fim): todo grupo mostra o total no próprio cabeçalho, no começo; saíram as linhas de total do fim. Total geral só com mais de uma filial; linha Fiscal/Gerencial só com os dois. O portador ganhou faixa azul maior; o nome do grupo é grande e os valores ficam em 8,5 pt, com Principal 21 mm e Total 22 mm para o mês de todas as filiais (milhões) caber.

Cabeçalho das colunas (Número, Pessoa, Conta contábil, Venc., valores) repetido embaixo de cada pagamento, antes dos títulos, a pedido do Fábio (23/10/2026), em letra menor e cinza; o do topo da página continua, rotulando os totais dos grupos. Uma lista só ($colunas) serve os dois.

Ajustes de 23/10/2026 (Fábio): espaço antes de cada pagamento e de cada portador (linha vazia de 4 mm; borda branca grossa não serve no mPDF: invade a linha de cima e corta o título anterior); faixa do pagamento em 9 pt, fundo #eceff1, aviso do parcial curto ('1 dos 3 títulos no filtro', 7,5 pt); saiu o cabeçalho de colunas do topo da página. Sem ele o mPDF ignora o colgroup: as larguras foram para as células do cabeçalho repetido em cada pagamento ($colunas = rótulo => mm).

Layout refeito (Fábio, 23/10/2026: 'replaneja pra ficar moderno e organizado'):
- DejaVu Sans Condensed 7,5 pt; cada título em no máximo duas linhas: número e conta contábil numa linha com reticências (mb_strimwidth), fatura e tipo do título em cinza embaixo; a pessoa pode usar as duas linhas. O tipo do movimento (Implantação) saiu: o sinal já diz se o adiantamento foi dado ou consumido.
- Total geral numa faixa escura (só com mais de uma filial); filial com a etiqueta FISCAL/GERENCIAL; portador em faixa azul com barra à esquerda e a quantidade de pagamentos; totais no cabeçalho de cada grupo.
- Pagamento numa linha: data, pessoa e observação (dividem a largura), código e '1 de 3 títulos no filtro' em cinza, e o valor seco na coluna Total com o sinal das linhas (saída positiva, entrada negativa em laranja); saiu o 'Saiu R$'.
- Cabeçalho da página com o título à esquerda e período/filtro à direita; rodapé 'emitido em … · página x de y'.
Prévia: https://claude.ai/artifact/CooZxhTSJUzUCeSLBHYJYR
<!-- SECTION:NOTES:END -->

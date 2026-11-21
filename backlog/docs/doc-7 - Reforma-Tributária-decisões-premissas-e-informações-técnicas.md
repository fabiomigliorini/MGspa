---
id: doc-7
title: 'Reforma Tributária: decisões, premissas e informações técnicas'
type: other
created_date: '2026-11-19 16:34'
---

# Reforma Tributária: decisões, premissas e informações técnicas

Documento compartilhado pelas tasks da reforma tributária (IBS/CBS). Cada task registra aqui o que
decidiu, o que assumiu e o que levantou. Nas seções de premissas e decisões, só se acrescenta: quando
mudar de ideia, entra uma linha nova, com data, dizendo o que mudou.

## Fases

Cada fase é uma task, na ordem do core para as beiradas. O reajuste de preço fica no go-live, por último.

| # | Task | O que faz | Depende de | Prazo |
|---|---|---|---|---|
| 1 | TASK-284 | Calcular o IBS/CBS de cada produto com as regras de 2027 | — | 31/12/2026 |
| 2 | TASK-285 | Emitir NF-e e NFC-e com os impostos de 2027 | 284 | 31/12/2026 |
| 3 | TASK-286 | Fazer o estoque fiscal voltar a movimentar e bater em 31/12/2026 | — | antes da 287 |
| 4 | TASK-287 | Apurar o crédito de 9,25% da CBS sobre o estoque de 01/01/2027 | 284, 286 | 30/06/2027 |
| 5 | TASK-288 | Descontar do custo da compra o crédito de IBS/CBS da nota do fornecedor | 284 | — |
| 6 | TASK-289 | Recalcular os preços de venda com IBS/CBS e reajustar em lote no go-live | 284, 288 | go-live |

## Linha do tempo e legislação

Levantado em 14/11/2026. Conferir na norma antes de usar em cálculo.

- **2026:** CBS de 0,9% e IBS de 0,1% só informativos, destacados na nota.
- **01/01/2027:**
  - CBS = alíquota de referência menos 0,1 ponto. O TCU calculou a referência em 11,3% (28/10/2026), o
    que dá CBS de 11,2%. O Senado fixa até 15/12/2026; sem resolução, vale a do TCU.
  - IBS de 0,05% estadual + 0,05% municipal em 2027 e 2028.
  - PIS e COFINS acabam.
- **Base do IBS/CBS:** durante a transição o ICMS, o ISS e o PIS/COFINS não entram na base (LC 214,
  art. 12). O IPI também não.
- **Crédito presumido sobre o estoque (LC 214, art. 381):**
  - Para quem não pôde se creditar de PIS/COFINS, como no regime cumulativo.
  - 9,25% sobre o estoque de bens materiais em 01/01/2027, para bens adquiridos no país. Para importados,
    vale o PIS/COFINS-Importação efetivamente pago.
  - Apurar até 30/06/2027. Usar em 12 parcelas mensais, só contra a CBS, sem ressarcimento.
  - Devolução de item desse estoque a partir de 2027 estorna o crédito.
- **2029 a 2032:** o ICMS cai e o IBS sobe, ano a ano. **2033:** ICMS e ISS acabam.
- **NF-e (NT 2025.002):**
  - A v1.52 entrou em homologação em 26/10/2026 e em produção em 16/11/2026.
  - A regra que rejeita item sem o grupo IBS/CBS (UB12-10) está sem data para produção.

Fontes:
- [LC 214/2025 (Planalto)](https://www.planalto.gov.br/ccivil_03/leis/lcp/lcp214.htm)
- [Art. 381 (Jusbrasil)](https://www.jusbrasil.com.br/topicos/802633047/art-381-da-lei-complementar-n-214-de-16-de-janeiro-de-2025)
- [Crédito presumido sobre estoque (Pedro Moreira Associados)](https://www.pedromoreiraassociados.adv.br/artigos/creditos-presumidos-de-ibs-e-cbs-mapeamento-pratico-e-polemicas-na-lc-214-2025-110/)
- [Referência da CBS pelo TCU](https://www.urbanovitalino.com.br/aliquota-de-referencia-da-cbs-tcu-calculou-em-113-e-encaminhou-para-definicao-do-senado/)
- [CBS 2027 e o prazo do Senado (Barbieri)](https://www.barbieriadvogados.com/aliquota-cbs-2027/)
- [NT 2025.002 v1.52 (Tax360)](https://www.tax360.com.br/conteudo/nt-nfe-nfce-2025-002-rtc-v152)
- [Base na transição com ICMS (Simtax)](https://simtax.com.br/base-calculo-ibs-cbs-transicao/)

## Premissas

Cada premissa diz a origem e se está **definida** (por nós) ou se é para **levar à consultoria**.

| Data | Premissa | Origem | Situação |
|---|---|---|---|
| 19/11/2026 | Migliorini (filiais 101 a 105) está no PIS/COFINS cumulativo (Lucro Presumido): vende com PIS 0,65% + COFINS 3% e compra sem crédito (CST 70/99) | Cadastro de tributação no sistema | Levantada do sistema, a confirmar |

## Decisões

| Data | Task | Decisão | Por quê |
|---|---|---|---|
| 19/11/2026 | — | Projeto quebrado em 6 fases, uma task cada, na ordem da tabela de fases | Do core para as beiradas: o motor de impostos é usado por todas as outras |
| 19/11/2026 | — | Reajuste de preço em lote fica no go-live, junto com a reprecificação (TASK-289) | É a parte menos importante e só existe para a virada de preço |
| 19/11/2026 | — | Até a TASK-289 entrar, os preços ficam os de hoje e a diferença de imposto sai da margem | Consequência da ordem acima |
| 19/11/2026 | — | Tasks com título de ação e prefixo "Reforma Tributária:"; todas novas, sem reaproveitar task antiga | Pedido do Fábio |

## Perguntas para a consultoria

Perguntas em aberto. A task que precisar da resposta define a posição ou leva à consultoria.

- O custo do estoque que entra no crédito de 9,25% leva IPI, ICMS-ST e frete? (TASK-287)
- Livro comprado com PIS/COFINS de alíquota zero, e que é imune ao IBS/CBS, entra no crédito? (TASK-287)
- O preço da gôndola continua sendo o preço final, com a nota desmembrando o IBS/CBS? Como fica o total
  da nota? (TASK-285, TASK-289)

## Informações técnicas

Levantamento de 14/11/2026, no banco local (cópia de produção).

### Motor de IBS/CBS (TASK-284)
- `api/app/Mg/Tributacao/TributacaoService.php`:
  - `recalcularTributosItem` grava em `tblnotafiscalitemtributo`.
  - `resolverRegras` busca em `tbltributacaoregra`. A regra já aceita natureza, estado, cidade, tipo de
    cliente, tipo de produto, prefixo de NCM e vigência (`vigenciainicio`/`vigenciafim`), e escolhe a
    mais específica.
  - É chamado em `NotaFiscalItemService`, `NotaFiscalNegocioService` e `NotaFiscalProdutoBarraController`.
- **Base de cálculo hoje:** `valortotal − desconto + frete + seguro + outras`. Não tira o ICMS.
- **Regras cadastradas:** 47, todas com vigência desde 01/01/2026 e só por natureza, sem NCM.
  - Venda e compra: CBS 0,9%, IBS estadual 0,1%, IBS municipal 0%, CST 000 / cClassTrib 000001.
  - Transferência: CST 410 na saída e 090 na entrada.
  - Bonificação: CST 040.
- Desde 05/01/2026 cerca de 685 mil itens de nota já gravaram CBS, IBS estadual e IBS municipal.
- Tributos em `tbltributo` (`codigo` + `ente`): CBS FEDERAL, IBS ESTADUAL, IBS MUNICIPAL, IS FEDERAL.
- **Produtos ativos com tratamento especial:** 43.717 produtos ativos em 2.302 NCMs.
  - Livros: 4901 (1.285), 4902 (102), 4903 (229).
  - Agro: 3808 (56), 3105 (9), 3101 (2), 1201 (5), 1005 (3).

### Tributação antiga: ICMS, PIS e COFINS (TASK-284, TASK-285)
- `tbltributacaonaturezaoperacao` (tributação × natureza × tipo de produto × estado × NCM) guarda CFOP,
  ICMS, PIS/COFINS, IPI e Domínio.
- Essa tabela **não tem vigência**.
- Venda: PIS CST 01 a 0,65% e COFINS CST 01 a 3% (12 regras); outras com CST 04, 05, 06 e 49.
- Compra: CST 70, 99, 73 e 74, sem crédito.

### XML da NF-e (TASK-285)
- `api/app/Mg/NFePHP/NFePHPMakeService.php`:
  - `gerarTributosReforma` monta o grupo IBSCBS a partir de `tblnotafiscalitemtributo`.
  - O `Make` é instanciado com o schema PL_010.
- Biblioteca: `nfephp-org/sped-nfe` v5.2.5. Conferir se cobre a NT 2025.002 v1.52.
- A tributação do item é calculada quando o item é criado. A data usada é `NotaFiscal->emissao`.

### Filiais e regime
- `tblfilial.crt`: 1 = Simples, 2 = Simples com excesso, 3 = regime normal. Nada separa Lucro Real de
  Presumido.
- Emitem NF-e com CRT 3: 101 a 105 (Migliorini), 401 (Fazenda), 701, 702 e 801 (Terceiros).
- FDF (201, 202) é Simples e não emite NF-e.

### Estoque e custo (TASK-286, TASK-287, TASK-288)
- **Tabelas:**
  - `tblestoquesaldo`: saldo atual por local × variação. `fiscal` false = físico, true = fiscal.
    Guarda `customedio`.
  - `tblestoquemes`: fechamento mensal.
  - `tblestoquemovimento`: movimentos.
- **Onde roda:** os movimentos e o custo médio são gerados por jobs no **MGLara**
  (`/opt/www/MGLara/app/Jobs`).
  - O fiscal sai das notas, em `EstoqueGeraMovimentoNotaFiscalProdutoBarra`.
  - O físico sai dos negócios, em `EstoqueGeraMovimentoNegocioProdutoBarra`.
  - O MGspa não chama a geração do fiscal.
- **Custo médio:** mensal e ponderado, por local × variação, separado entre físico e fiscal. Está em
  `EstoqueSaldoService::estoqueCalculaCustoMedio`, e há um job igual no MGLara.
- **Custo de entrada:** nenhum dos dois tira imposto recuperável.
  - Físico: `vprod + IPI + ICMS-ST − desconto + frete + seguro + outras` (`NfeTerceiroImportarService`).
  - Fiscal: `vprod` menos o desconto rateado.
- **Estoque fiscal parado:** os movimentos fiscais caíram de 164.408 em janeiro de 2026 para 22 em
  abril, 11 em maio e 6 em junho. O último `tblestoquemes` fiscal é de julho de 2026. O físico segue
  normal, e as notas também (cerca de 27 a 31 mil por mês).
- **Saldo fiscal positivo hoje:**

  | Filial | Saldo fiscal |
  |---|---|
  | 101 | R$ 2,05 mi |
  | 102 | R$ 3,41 mi |
  | 103 | R$ 3,67 mi |
  | 104 | R$ 1,56 mi |
  | 105 | R$ 0,74 mi |
  | **Total** | **cerca de R$ 11,4 mi** |

  Há muitos saldos negativos; no físico da 102, por exemplo, são 4.935 linhas.
- **Relatórios por data que já existem:**
  - Arquivo de estoque do Domínio (`api/app/Mg/Dominio/Arquivo/ArquivoEstoque.php`): estoque fiscal,
    tipo de produto 0, no último dia do mês. O `valorUnitario` sai com o preço de venda.
  - Físico × Fiscal (`EstoqueSaldoRelatorioService`).
- O produto tem a flag `importado`.

### Preço (TASK-289)
- **Onde mora o preço:**
  - `tblproduto.preco` é o preço por unidade.
  - `tblprodutoembalagem.preco` é opcional, por embalagem.
  - O preço usado vem da `vwprodutobarra`.
  - Um preço só, igual em todas as filiais. Sem tabela de preço e sem promoção.
- **Margem:** nenhuma gravada no produto, na marca ou na seção. A única é `tblnfeterceiroitem.margem`,
  por item da nota de entrada, com média de 35%.
- **Sugestão de preço:** só na entrada da nota (`NfeTerceiroItemAnaliseService`).
  - Custo: tira o crédito de ICMS.
  - Preço: "margem inversa" com ICMS de venda fixo em 17% e MVA padrão de 57,97%.
  - Ignora PIS/COFINS.
  - Só mostra; não grava o preço.
- **Reajuste em lote:** não existe. O preço muda um produto por vez, em `ProdutoService` e
  `ProdutoEmbalagemService`.
- **Histórico:** `tblprodutohistoricopreco`, gravado a cada troca. As etiquetas usam esse histórico para
  imprimir o que mudou no período (`EtiquetaService`).
- **Outros canais:** Mercos e Woo usam o mesmo `preco`.

## Fora do escopo por enquanto

Sem task. Entram quando o Fábio pedir.

- Nota de crédito e nota de débito, e os eventos novos da NF-e (perda/roubo, consumo próprio, crédito
  presumido).
- Queda do ICMS de 2029 a 2032 (a tabela antiga não tem vigência).
- Agro: insumos com redução e produtor rural (Fazenda).
- Simples Nacional (FDF não emite NF-e).

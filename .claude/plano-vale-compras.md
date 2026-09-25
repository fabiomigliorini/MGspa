# Vale Compras dentro do negócio

## RESULTADO DA NOITE (24→25/09/2026)

**VEREDITO DO CASO 1 DO MILESTONE 6 (regressão do XML de venda só com mercadoria):
DIFF VAZIO.** O XML de uma venda só com mercadoria saiu **byte a byte idêntico** antes e depois da
mudança — 5.960 bytes dos dois lados, `diff` sem uma linha. Mais duas formas de venda sem vale foram
pelo mesmo caminho e também deram diff vazio: com **juros** parcelados (5.945 bytes), que é onde ficam
as duas outras linhas mexidas, e **NFe 55 a prazo com duplicatas** (5.493 bytes). A emissão de nota
da empresa não mudou.

O método está descrito no milestone 6 abaixo e foi provado antes de servir de prova: gerando duas
vezes o XML do mesmo negócio **sem mudar nada de código**, o resultado já era byte a byte idêntico —
então uma diferença depois seria da mudança, não do método.

### Pronto
Os cinco milestones pedidos, todos na árvore de trabalho e **sem nenhum commit**: **4** (rateio de
desconto/frete/seguro/outras entre mercadoria e vales, e o job de estoque do MGLara aposentando o
rateio de cabeçalho), **5** (crédito tipo 3 no fechamento, estorno no cancelamento, comprovante 80mm
com escola/aluno/turma/lista e as duas travas temporárias removidas), **6** (rateio do `detPag`, com
os 6 casos testados), **7** (conciliação DIMP em PDF) e **8** (consumo por escola/turma em FIFO, com
a trava de saldo sob lock). O detalhe de cada um está na seção "Execução da noite", no fim do
arquivo. O milestone 9 **não foi tocado**, como combinado.

### Pendente
- **Validar na tela.** Nada foi validado no navegador: a checagem do front foi prettier, eslint e
  compilação dos SFC com o compilador do Vue. O `quasar build` não roda nesta máquina (Node 18 no
  host, o app pede 22) e o seu `quasar dev` não foi tocado.
- Falta o **ponto de entrada do relatório DIMP no app** — hoje é só a rota da API
  (`v1/dimp/conciliacao?ano=&mes=`). Não fiz tela porque não sei onde ela mora (negócios? contas?).
- O `saidavalor` em `tblestoquemovimento` precisa ser conferido depois de um negócio real passar pelo
  job de estoque do MGLara (só a mudança foi feita, o efeito não foi observado).
- Os negócios de teste ficaram no banco de **dev** (4541400 a 4541429, mais alguns). Não limpei.
- A bancada de teste está em `api/storage/app/vale-teste/` — fora do repositório, com os XMLs, os PDFs
  e os scripts. Dá para rodar tudo de novo com `docker exec mgspa-api php storage/app/vale-teste/m6_caso1.php`.

### Quebrou
Nada. Os três XMLs de regressão sem vale (só mercadoria, com juros, e NFe 55 com duplicatas) saíram
byte a byte idênticos antes e depois. **Mas uma coisa mudou fora do caminho sem vale, de propósito:**
negócio que consome **dois ou mais vales bipados** passa a sair com **um** `detPag tPag=12` em vez de
dois (decisão 6). Isso já acontece em produção hoje e sai diferente a partir de agora.

### Dúvidas — RESOLVIDAS em 25/09
1. **Portador do título: `null`.** Como o vale compras do MGLara sempre nasceu — o crédito não está
   em lugar nenhum até ser resgatado. Aplicado em `PdvNegocioValeService::emitirCredito`.
2. **Numeração: `V{codnegocio}-{A,B,C}`.** Mantém o prefixo `V` que o financeiro conhece do legado,
   com o codnegocio no lugar do codvalecompra. Aplicado.
3. **Relatório DIMP** — está sendo tratado em outro chat.
4. **Trigger × milestone 9: os convertidos nascem com `codpdv` preenchido.**
   ⚠️ A premissa inicial era dropar as triggers legadas — **não dá.** Conferido no banco: as três
   triggers ativas chamam aquelas funções (`tblnegocioaiau_…` → `fnTblNegocio_Atualiza_ValorTotal`,
   `tblnegocioprodutobarraaiauad` → `_ValorProdutos`, `tblnegocioformapagamentoaiauad` →
   `_ValorAPrazo`), e os negócios sem `codpdv` dos últimos 12 meses são **3.088, sendo 2.971 de
   Compra**. O `codpdv is null` não é resquício: é o roteador entre "total calculado pelo app" e
   "total calculado pelo banco", e o segundo atende o fluxo de entrada. Dropar zeraria o total de
   toda compra. Por isso a saída é pelo outro lado — dar `codpdv` aos convertidos, que são registros
   históricos imutáveis.
   **Pendência latente:** se um dia vale entrar por caminho que não é PDV (Mercos, Woo, entrada), a
   trigger zera o `valorvales`. Aí a fórmula dela precisa aprender a coluna.

### Ainda falta
- **Validar as telas no navegador** — em andamento com o Fabio.
- **Milestone 9**, com o `codpdv` acima.
- **TASK-175** — conferir o `saidavalor` do estoque depois de um negócio real passar pelo job.
- Limpar os negócios de teste do banco de dev (4541400–4541429).

---

## Contexto

A venda de vale compras vive no MGLara, fora do negócio. Quem leva material **e** compra um vale passa
o cartão duas vezes, e muitas vezes nenhuma das duas vendas atinge o mínimo de parcela — é o motivo da
TASK-38. O resgate (bipar `VAL…` no wizard Receber) **já roda em produção**; só a emissão está fora.

Tese fiscal: `backlog/docs/doc-1 - Tese-de-regularidade-fiscal-da-venda-de-vale-compras.md`. A venda do
vale é recebimento antecipado sem fato gerador de ICMS; o documento fiscal sai na retirada, com
`tPag=12`. É **vedado** fechar a nota com `vTroco` inflado ou item fictício.

---

## 1. Decisões tomadas (sessões de 23 e 24/09/2026)

| # | Decisão | Consequência principal |
|---|---|---|
| 1 | **O saldo continua sendo título `codtipotitulo = 3`** a crédito, conta contábil 83, em nome da escola | Financeiro, resgate, estorno e código de barras `VAL+codtitulo` não mudam; os ~766 papéis já impressos seguem valendo |
| 2 | **O vale é um bloco próprio do negócio — não é produto e não é item** | Elimina por construção ~10 pontos de vazamento; em troca, 4 lugares passam a somar `valorvales` |
| 3 | **Converter o legado criando os negócios** retroativos | É o que permite repontar os 313 títulos 240 e dropar as tabelas antigas de vez |
| 3b | Negócios convertidos nascem com a **natureza de venda normal** | O marcador para identificá-los é `tblnegociovale.codvalecompra IS NOT NULL` + `codpdv IS NULL`, não uma natureza |
| 4 | **REVISADA — cada vale é uma seção própria abaixo da grade de produtos** ("Vale A", "Vale B"), com uma grade de itens igual à da mercadoria | A grade de itens deixa de ler o store direto e passa a receber a lista por prop |
| 5 | **Desconto, frete, seguro, outras e juros rateados proporcionalmente** entre mercadoria e vale | A NFC-e leva só a fração da mercadoria |
| 5c | **O crédito emitido é sempre o valor de FACE do vale** | `tblnegociovale` guarda `valorvale` (a face, que é o crédito) e `valortotal` (a fatia paga) separados |
| 6 | Consumo de vários vales = **N pagamentos no banco, agrupados na tela e na nota** | `baixarVales`/`estornarBaixaVales` seguem intactos; um único `detPag tPag=12` na NFC-e |
| 7 | **REVISADA — o módulo vale compras do MGLara morre na Fase 2** e é desabilitado no go-live | Sem fallback a partir daí, por decisão explícita. **A aplicação MGLara continua no ar** — ela ainda é dona do estoque e das imagens (ver seção 6) |
| 8 | **Validade de 1 ano** da emissão, informativa | Sem job de expiração nesta rodada; o saldo continua resgatável depois da data |
| 9 | **Comprovante térmico 80mm com código de barras**, como o vale de devolução | O caixa passa a bipar em vez de digitar o número |
| 10 | **REVISADA — a estrutura TRANSACIONAL morre e é convertida; o CATÁLOGO evolui in place** | Os 204 modelos nunca se movem: `ALTER TABLE … RENAME` resolve. Nada com o nome `valecompra` sobrevive assim mesmo, e some a conversão do catálogo |
| 11 | **Prefixo `tblnegocio` em tudo que aponta para `codnegocio`** | O vale emitido é `tblnegociovale`; o catálogo, que não aponta para negócio, é `tblvalemodelo` |
| 12 | **Modelo enxuto**: sem `desconto`, e `modelo`+`turma`+`ano` viram uma `descricao` só | `totalprodutos` e `total` colapsam em `valorprodutos`, que mapeia 1:1 para a face do vale |
| 13 | **Vale ao portador**: favorecido opcional no modelo e `= Consumidor (1)` no vale quando não há escola | Capacidade nova — passa a dar para vender vale-presente avulso; o resgate por bipagem já funciona sem mudança |
| 14 | **O DDL do catálogo acontece já na Fase 2** | Zero retrabalho na tela nova, ao custo de perder o fallback do MGLara antes de a venda nova estar validada |
| 15 | **Os itens do vale são editáveis na venda** — dá para tirar item e mudar quantidade | O valor do vale passa a ser a **soma dos seus itens** |
| 16 | **Na grade do vale só se tira e ajusta — não se acrescenta** | O modelo define o universo do vale; item fora da lista o cliente leva como mercadoria. O `InputBarras` não é tocado e a bipagem segue caindo sempre em mercadoria |
| 17 | **REVISADA — o valor livre virou coluna: `valoravulso`** | Substitui o "valor digitado quando não há modelo". Um modelo pode ser kit puro, avulso puro ou os dois; e o `confereTotais` volta a poder conferir `valorprodutos` contra os itens, com o avulso livre ao lado |
| 18 | **Zero toque no que é de item de mercadoria** — componentes, formulários e actions ficam como estão; o vale ganha os seus, ainda que duplicados | Separabilidade acima de reuso: dá para apagar o vale inteiro sem tocar no PDV. Custo aceito: melhoria futura na grade de item não propaga para a do vale |
| 19 | **Desconto, frete, seguro, outras e juros NÃO descem ao item do vale** — param em `tblnegociovale` | O item do vale é só quantidade × preço; some metade das colunas dele e some o rateio item a item |
| 20 | **`valorvales` é BRUTO, simétrico ao `valorprodutos`** — e o `valordesconto`/`valorjuros` do negócio passam a somar mercadoria + vale | Custa dois ajustes que a decisão 2 tinha dispensado: o denominador do custo de estoque no MGLara e o do `percJuros` da NFC-e |

| 21 | **`tblnegociovale` espelha as duas parcelas do modelo**: `valorprodutos` (soma dos itens) e `valoravulso` (valor livre) | Dá para saber depois quanto do crédito veio de kit e quanto foi avulso, e o operador ajusta cada parte |
| 22 | **A face do vale chama `valorvale`**, não `valortotal` | No modelo `valortotal` é a face; no vale seria a fatia paga. Nomes próprios evitam a armadilha: `valorvale` = face = crédito; `valortotal` = fatia paga após rateio, como em todo o resto do negócio |

Do escopo original, também decidido: vale usado em **qualquer filial, sem restrição**, e **sem
breakage/expiração** nesta rodada.

### O que a decisão 2 dispensou

Por o vale não ser item nem produto, saem do escopo: criar produto "VALE COMPRAS", flag
`indicadorvenda`, guarda no `ProcessarVendaService` (RH), bloqueio de devolução do item de vale,
guarda contra o produto ser bipado por engano, action de item que não funde duplicados, e as
exclusões em `juntarItensPorBarras` / `informarPessoa` / `itemSalvar`. Nenhum desses caminhos
enxerga o vale. **Exceção:** o job de estoque do MGLara entrou no escopo (milestone 4), não porque
enxergue o vale, mas porque o rateio de desconto dele está obsoleto e o vale expõe isso.

---

## 2. Modelagem

**Duas tabelas novas** (`api/database/vale.sql`):

- **`tblnegociovale`** — `codnegociovale` PK · `uuid` unique · `codnegocio` NOT NULL · `codtitulo`
  unique null · `codpessoafavorecido` NOT NULL (escola, ou **Consumidor = 1** se ao portador) ·
  `aluno` null · `turma` null · `codvalemodelo` null · `valorprodutos` (soma dos itens) ·
  `valoravulso` (valor livre) · **`valorvale`** (= produtos + avulso; é a **face** e o crédito
  emitido) · `valordesconto` · `valorfrete` · `valorseguro` · `valoroutras` · `valorjuros` ·
  `valortotal` (a fatia paga, depois do rateio) · `validade` · `codvalecompra` null (número antigo,
  impresso nos papéis em circulação) · `observacoes` · `inativo` · auditoria.
- **`tblnegociovaleprodutobarra`** — `codnegociovaleprodutobarra` PK · `uuid` unique ·
  `codnegociovale` · `codprodutobarra` · `quantidade` · `valorunitario` · `valorprodutos` ·
  `ordenacao` · `inativo` (soft-delete) · auditoria. Sem desconto/frete/seguro/outras: item de vale é
  quantidade × preço (decisão 19).

**Uma coluna nova:** `tblnegocio.valorvales` numeric(14,2) — a soma dos `valorvale` de cada vale.

**Catálogo — evolui in place** (`api/database/vale_catalogo.sql`), sem mover um dado. O bloco roda só
se a tabela antiga ainda existir; é isso que o torna idempotente, porque o `UPDATE` de concatenação
não pode rodar duas vezes:

```sql
ALTER TABLE tblvalecompramodelo RENAME TO tblvalemodelo;
ALTER TABLE tblvalemodelo RENAME COLUMN codvalecompramodelo TO codvalemodelo;
ALTER TABLE tblvalemodelo RENAME COLUMN total TO valorprodutos;
-- a coluna `modelo` passa a ser a descrição: sem campo novo, sem rename.
-- 63 dos 204 estouram varchar(50) depois de concatenar (o maior fica com 85)
ALTER TABLE tblvalemodelo ALTER COLUMN modelo TYPE varchar(100);
UPDATE  tblvalemodelo SET modelo = concat_ws(' - ', modelo, nullif(turma,''), ano);
ALTER TABLE tblvalemodelo ALTER COLUMN codpessoafavorecido DROP NOT NULL;
ALTER TABLE tblvalemodelo DROP COLUMN turma, DROP COLUMN ano,
                          DROP COLUMN desconto, DROP COLUMN totalprodutos;
-- idem tblvalecompramodeloprodutobarra -> tblvalemodeloprodutobarra
```

Os números justificam os cortes: **0 de 204** modelos usam `desconto`, **139 de 204** têm `turma`
preenchida (por isso a descrição concatena em vez de descartar) e **1 de 204** usa `observacoes`, que
fica. A FK de `tblvalecompra` acompanha o rename sozinha; quem quebra é o PHP do MGLara.

### Composição dos totais

```
negocio.valortotal = valorprodutos + valorvales − valordesconto + valorfrete
                     + valorseguro + valoroutras + valorjuros
```

`valorprodutos` e `valorvales` são **brutos**; os valores de cabeçalho somam as duas fatias
(decisão 20). Exemplo — R$ 500 de material + vale de R$ 200 com R$ 70 de desconto, cliente paga 630:

| | Face | Desconto rateado | Líquido | Crédito |
|---|---|---|---|---|
| Mercadoria | `valorprodutos` 500,00 | 50,00 | `valortotal` 450,00 | — |
| Vale | `valorvale` 200,00 | 20,00 | `valortotal` 180,00 | **200,00** |

Negócio: `valorprodutos` 500 · `valorvales` 200 · `valordesconto` 70 · `valortotal` 630. NFC-e sai com
`vNF` 450. A escola recebe a **face**, R$ 200 — não os 180 que o cliente pagou (decisão 5c).

### Shape no store (`negocios/src/stores/negocio.js`)

- `negocio.vales[]` — um objeto por vale: `uuid`, `codnegociovale`, `codpessoafavorecido`, `aluno`,
  `turma`, `codvalemodelo`, os valores, e **`itens[]` dentro**.
- `negocio.vales[i].itens[]` — mesmo shape do item de mercadoria (`uuid`, `codprodutobarra`,
  `barras`, `produto`, `quantidade`, `valorunitario`, `valorprodutos`, `percentualdesconto`,
  `valordesconto`, `valorfrete`, `valorseguro`, `valoroutras`, `valortotal`, `ordenacao`, `inativo`).
- **`itensAtivos` continua significando só mercadoria.** O getter não muda, e o `aplicarValores()`
  também não — ele segue rateando só mercadoria (decisão 18). Quem soma as duas coleções é o
  `recalcularValorTotal()`: mercadoria em `valorprodutos`, face dos vales em `valorvales`.
- De graça: `gruposDuplicadosPorBarras`, `juntarItensPorBarras` e `itemAdicionar` seguem operando só
  sobre `negocio.itens`, então nunca alcançam item de vale.

---

## 3. Milestones

Cada um termina com algo que dá para abrir na tela e validar.

### 1 · CRUD de modelos de vale
**Só o cadastro. Nada de PDV, nada de `v1/pdv/`, nada de Dexie.**
`vale_catalogo.sql` (o bloco da seção 2) · models `ValeModelo` e `ValeModeloProdutoBarra` em
`api/app/Mg/Vale/` · domínio `Mg\Vale` no padrão da casa (Controller, Service estático, Resource,
FormRequests, rotas em `auth:api`+`v1`, `Autorizador::autoriza()`) · tela de CRUD com descrição única
e favorecido opcional.
⚠️ **Derruba a venda de vale no MGLara**, que só volta a existir no milestone 5.
**Valida:** `select count(*), count(modelo) from tblvalemodelo` → 204/204; cadastra, edita e inativa
um modelo pela tela.

### 2 · Impressão do modelo (orçamento para a escola validar)
Documento A4 com o cabeçalho da loja, a escola favorecida, a descrição do modelo, a lista de produtos
com quantidade, preço unitário e total, o `valoravulso` quando houver, e o `valorvale`. **Com preços**,
que é o objetivo — a escola confere itens e valores antes da temporada. **Sem validade**: leva só a
**data da impressão**, já que preço de material muda entre a validação e janeiro.
`ValeModeloRelatorioService` com `html()` e `pdf()` (mPDF, padrão do `CargaRelatorioService`), rota
aceitando `?html=1` para ajustar o layout sem re-renderizar PDF a cada tentativa, controller devolvendo
`Content-Type: application/pdf` inline; no front, o helper `@components/abrirPdf` com o
`MgRelatorioPdfDialog` no desktop e nova aba no mobile.
**Por que aqui:** depende só do milestone 1 e vence **antes** da venda — as escolas validam as listas
em nov/dez, enquanto a venda nova precisa estar pronta em janeiro.
**Valida:** abre o PDF de um modelo real, confere itens, preços e total contra a tela, e manda para
outra pessoa abrir.

### 3 · Vale dentro do negócio
Começa pelo sync do catálogo pro PDV, que é onde ele passa a ser necessário: `GET v1/pdv/vale-modelo`
espelhando `PdvService::formaPagamento()`, `db.version(7)`, `sincronizarValeModelo()` e o carimbo no
`.sort()[0]` de `inicializaVars()`.
⚠️ O template de sync lê `data[0].sincronizado` sem checar lista vazia e o catálogo é sazonal:
guardar `if (!data.length) return`, e testar com catálogo cheio **e vazio**.
Depois: `vale.sql` (as duas tabelas novas e `tblnegocio.valorvales`) · models `NegocioVale` e
`NegocioValeProdutoBarra` em `api/app/Mg/Negocio/` · `criar()` inicializa `vales: []` e
`recalcularValorTotal()` soma a coleção · actions `valeAdicionar`, `valeExcluir`, `valeItemSalvar`,
`valeItemInativar` · `ValeDialog.vue` (modelo opcional → favorecido → aluno/turma; o modelo semeia
os itens **e** o `valoravulso`, ambos editáveis; sem modelo, só o `valoravulso`; sem favorecido grava
Consumidor e o vale nasce ao portador) · `ListagemItensVale.vue`,
uma seção por vale sob "Vale A"/"Vale B", sem botão de acrescentar · `negocioAberto` faz upsert de
`tblnegociovale` e dos itens por uuid, e o `confereTotais()` passa a somar os vales.
⚠️ **Trava temporária: negócio com vale não fecha.** Sem ela este milestone produziria negócio
fechado sem crédito. O milestone 5 remove.
**Valida:** monta um vale, tira item, muda quantidade, vê o total do negócio subir, sincroniza e
confere `tblnegociovale` e os itens; dois vales no mesmo negócio dão duas seções.

### 4 · Rateio entre mercadoria e vales
O diálogo de valores do `TotalNegocio.vue` divide desconto/frete/seguro/outras entre mercadoria e
vales na proporção dos `valorprodutos`, chama o `aplicarValores()` **intacto** para a mercadoria e
grava a fatia do vale direto no registro dele (decisões 18 e 19).
Junto: aposentar o rateio de desconto do job de estoque do MGLara
(`EstoqueGeraMovimentoNegocioProdutoBarra.php:114-118`) — trocar o ajuste por
`1 − (negocio.valordesconto / negocio.valorprodutos)` pelo valor já rateado do próprio item,
`NegocioProdutoBarra.valorprodutos − valordesconto`. Aquele rateio é resquício de quando o item não
tinha `valordesconto`; hoje tem, e tirá-lo deixa o job imune a valores de cabeçalho.
**Valida:** desconto de cabeçalho num negócio com mercadoria + vale → cada lado com sua fatia e a
**face do vale inalterada**; `saidavalor` em `tblestoquemovimento` batendo com
`valorprodutos − valordesconto` do item.

### 5 · Créditos, estorno e comprovante
`fechar()` cria o título tipo 3 / conta 83 em nome do favorecido, vencimento +1 ano, condicionado a
`codtitulo IS NULL`, com unique index como rede. O título fica **solto**, só referenciado por
`tblnegociovale.codtitulo` — nunca pendurado em `tblnegocioformapagamento`, porque
`negocioFechado():166-173` reescreve `codpessoa`, `codtipotitulo` e `codcontacontabil` de todo título
pendurado num pagamento, a cada PUT de negócio fechado · `cancelar()` ganha loop próprio, com mensagem
clara quando o vale já foi usado · bloqueia vale em natureza sem `financeiro` e vale zerado ·
comprovante térmico 80mm com `VAL+codtitulo` lendo `tblnegociovale` · **remove a trava do
milestone 3**.
⚠️ Entra a trava **negócio com vale não emite NFC-e**, que o milestone 6 remove.
**Valida:** fecha → título 3 / conta 83 / escola / `creditosaldo` negativo pela **face** → imprime →
**bipa o `VAL…` de volta** e resgata em outra filial → cancela um sem uso (estorna) e um já usado
(recusa com mensagem) → PUT no negócio já fechado trocando o cliente, e o título continua intacto.

### 6 · Fiscal: rateio do `detPag`
Em `gerarNotaFiscalDoNegocio`: `$nota->refresh()` e ratear os pagamentos contra `$nota->valortotal`,
consumindo o valor dos vales primeiro do **dinheiro → PIX → cartão**; cap em
`valortotal − valortroco`; descartar pagamento zerado; **último pagamento absorve a diferença** (a
rede do `NFePHPMakeService:895-911` só corrige para menos, errar para mais é rejeição). Corrigir o
`percJuros` (`:124`) e a sobra jogada no último item (`:252-257`), que com vale é justamente a fatia
dele. Tratar as duplicatas da NFe 55. Remove a trava do milestone 5.
**Tudo condicionado a "existe vale neste negócio"**, para o caminho sem vale seguir idêntico.
**Valida:** homologação com só mercadoria (**regressão**, diff de XML byte a byte), mercadoria+vale em
dinheiro com troco, em PIX, em cartão, e nota de retirada com `tPag 12`.

### 7 · Conciliação DIMP
Relatório mensal (mPDF, padrão `CargaRelatorioService` com `?html=1`): recebimentos cartão/PIX ×
notas emitidas × vendas de vale × consumos `tPag=12` × recebimentos de crediário, fechando em zero.
Vem logo depois do fiscal de propósito — é o milestone 6 que cria a divergência entre o `cAut` da
adquirente e o valor da NFC-e, e este relatório é a única peça que a explica. Lê `tblnegociovale`
**e** `tblvalecompra` em `UNION` até o milestone 9.
**Valida:** rodar sobre um mês fechado e conferir que a diferença dá zero.

### 8 · Consumo por escopo
Escola / turma / bipados, FIFO com o último vale parcial, N pagamentos agrupados na tela e num único
`detPag tPag=12`. Vale ao portador fica fora do escopo por escola (todos têm favorecido = Consumidor)
— só por bipagem.
⚠️ **Reconferir saldo com lock no `fechar()`** antes do `baixarVales`: hoje não existe validação de
saldo no servidor, e com escopo "escola inteira" dois PDVs montam FIFO sobre o mesmo pool.
**Valida:** consumo com saldo maior e menor que a compra; **dois PDVs no mesmo pool ao mesmo tempo**;
excluir um pagamento do lote; cancelar e reconferir saldos.

### 9 · Conversão do legado e limpeza
`vale_conversao.sql` cria, para cada um dos 3.718 vales, 1 `tblnegocio` (fechado, `codpdv` null,
natureza de venda normal, `valorprodutos` 0), 1 `tblnegocioformapagamento`, 1 `tblnegociovale` e N
itens; reponta os 313 títulos 240. Depois dropa `tblvalecompra*` e a coluna
`tbltitulo.codvalecompraformapagamento`, remove os 5 models da API, a flag
`tblformapagamento.valecompra` (e os 3 arquivos do app contas que a expõem) e o módulo do MGLara.
**Valida:** roda duas vezes; `count(*)` e `sum(total)` antes e depois; os 313 títulos somando o mesmo
e os 2 abertos com saldo; abre um vale histórico com crédito vivo e resgata bipando o papel antigo.

---

## 4. Riscos

| Risco | Onde |
|---|---|
| **Alto** — o milestone 6 toca o gerador de NFC-e de **toda** a empresa | `NotaFiscalNegocioService` |
| **Alto** — consumo por escopo sem validação de saldo no servidor → estouro do crédito | `PdvNegocioPrazoService::baixarVales` |
| **Alto** — janela sem nenhuma tela de venda de vale entre os milestones 1 e 5 | aceito; ver prazo abaixo |
| **Médio** — `cancelar()` não alcança título solto sem código novo → crédito órfão vivo | `PdvNegocioService:388-398` |
| **Médio** — `fechar()` concorrente sem `lockForUpdate` (furo que já existe, o vale amplia) | `PdvNegocioService:207-210` |
| **Fiscal** — `cAut` real com `vPag` reduzido é o que o cruzamento DIMP enxerga; o milestone 7 é o que explica | doc-1 §4.2 |
| **Fiscal** — escolher item a item aproxima o vale de "mercadoria determinada", o ponto mais sensível da tese (requalificação como venda para entrega futura) | doc-1 §6; levar na consulta à SEFAZ-MT |

**Prazo:** janeiro concentra **84% da venda** de vale compras — 864 vales e R$ 91,6 mil desde 2024,
contra ~87 de março a dezembro somados. Setembro a dezembro dão ~18 vales/ano, então quebrar o MGLara
agora custa quase nada, mas a venda nova precisa estar validada **antes de janeiro**.

**Não desligar a aplicação MGLara.** O go-live desabilita o módulo vale compras dela, mas ela segue
dona da movimentação de estoque de todo negócio (`PdvNegocioService::movimentarEstoque`), do custo
médio, da fila de jobs de estoque e das imagens de produto. Se for desligada, o estoque da empresa
para de se mexer **em silêncio** — a falha é engolida num `try/catch` que só escreve no log.

**DDL em produção é do Fábio, e roda no go-live — não é pendência de milestone nenhum.** Todo `.sql`
deste plano (`vale_catalogo.sql`, `vale.sql`, `vale_conversao.sql`) roda em **dev** durante o
desenvolvimento e em **produção só no go-live**, por quem tem acesso. Um milestone está fechado
quando funciona em dev; o banco de produção estar atrasado é o estado **esperado**, não um gap.
Não relatar isso como pendência, não perguntar se já rodou, não sugerir rodar.

---

## 5. Execução da noite de 24→25/09/2026

### Milestone 4 · Rateio entre mercadoria e vales — **PRONTO**

O diálogo de valores (`TotalNegocio.vue`) passou a tratar o negócio inteiro como base: o `%` de
desconto, o teto do campo e o total agora saem de `valorprodutos + valorvales`, e o `salvar()` chama
a ação nova `aplicarValoresCabecalho()` no lugar do `aplicarValores()`. A ação nova rateia
desconto/frete/seguro/outras entre mercadoria e vales na proporção dos brutos (mercadoria pelo
`valorprodutos`, cada vale pela sua **face**), grava a fatia direto em cada vale e chama o
`aplicarValores()` **intacto** para a fatia da mercadoria — que segue sendo a única a descer ao item
(decisões 18 e 19). A face do vale nunca se mexe: o desconto muda o `valortotal` (o que o cliente
paga), não o crédito. A mercadoria absorve a sobra de arredondamento; quando não há mercadoria
(negócio 100% vale) quem absorve é o último vale. Negócio sem vale cai num `return` antes de qualquer
conta e a chamada é literalmente a de hoje. No MGLara, o job de estoque
(`EstoqueGeraMovimentoNegocioProdutoBarra.php`) parou de ajustar o valor por
`1 − (desconto do cabeçalho / valorprodutos do cabeçalho)` e passou a ler
`NegocioProdutoBarra.valorprodutos − valordesconto` — o rateio já está no item, então o ajuste velho
aplicava o desconto duas vezes; agora o job é imune a valor de cabeçalho.

Validado: prettier e eslint limpos nos dois arquivos; a matemática do rateio foi conferida fora do
Pinia em 8 casos (o exemplo do plano 500+200/desconto 70 → 50 e 20; sem vale; 100% vale; dois e três
vales com quebra de centavo; desconto zero; vale zerado no meio) e em todos a soma das fatias fecha
exatamente com o total do cabeçalho. **Falta validar na tela** (lançar mercadoria + vale, dar
desconto de cabeçalho e conferir as duas fatias) e conferir o `saidavalor` em `tblestoquemovimento`
depois de um negócio real passar pelo job.

### Milestone 5 · Créditos, estorno e comprovante — **PRONTO**

Nasceu o `PdvNegocioValeService` (api/app/Mg/Pdv/). O `fechar()` perdeu a trava do milestone 3 — no
backend e no store do PDV — e ganhou, no lugar dela, `validarFechamento()` (natureza sem `financeiro`
recusa, vale zerado recusa, com o nome da seção: "O Vale A está zerado!") e `emitirCreditos()`, que
cria um título tipo 3 / conta contábil 83 em nome do **favorecido**, valor = a **face** do vale,
vencimento = a validade de 1 ano, portador CARTEIRA, número `N{codnegocio}-VAL{A,B,…}`. O título fica
**solto**: só `tblnegociovale.codtitulo` aponta para ele, nunca `tblnegocioformapagamento`. A emissão
é idempotente por `codtitulo IS NULL`, com o unique index `unq_tblnegociovale_codtitulo` de rede. O
`cancelar()` chama `validarCancelamento()` **antes de estornar qualquer coisa** e depois
`estornarCreditos()`. O comprovante térmico 80mm (`ValeService::pdf` + `negocio/vale.blade.php`)
passou a ler `tblnegociovale`: o vale vendido sai com escola (ou "Ao portador"), aluno, turma,
validade, a lista do kit com quantidade e o valor avulso; o vale de devolução e o saldo de resgate
parcial continuam saindo como sempre. Entrou a trava do milestone 6 — negócio com vale não emite
NFC-e.

Verificado em dev com bancada própria (`api/storage/app/vale-teste/`, fora do repositório), negócios
reais criados e fechados pelo caminho do PDV: negócio 221,00 (21,00 de mercadoria + vale A de 100,00
da escola + vale B de 100,00 ao portador) fecha e emite 2 títulos tipo 3 / conta 83 / saldo −100,00
cada, um para a escola 14842 e outro para o Consumidor; reemitir não duplica (2 vales, 2 títulos);
comprovante 80mm sai com as duas vias; NFC-e recusada com mensagem clara; cancelar estorna os dois
créditos; vale com 30,00 já resgatados recusa o cancelamento com "O Vale A (#650120) já foi usado em
compras: R$ 30,00 do crédito já saiu"; vale zerado não fecha; negócio **sem** vale fecha normal
(regressão); e o PUT num negócio já fechado trocando o cliente deixa o título do vale **intacto** —
que era o motivo de ele nascer solto.

Consertado durante a revisão, não é task: o `#header` do comprovante é `position:fixed` e o Dompdf
guarda só a última definição dele, então num negócio com dois vales a página 1 saía carimbada no
cabeçalho com o código do vale 2. O número saiu do cabeçalho — ele já aparece no corpo, que é onde é
de fato por vale.

**A confirmar na validação:** o título nasce com portador CARTEIRA (999), como o crédito de
devolução que já roda; o vale compras do MGLara nascia **sem** portador. Se o financeiro esperar
portador nulo, é uma linha.

### Milestone 6 · Fiscal: rateio do `detPag` — **PRONTO**

Tudo que o vale faz no gerador de NFC-e está atrás de um único `$temVale`, calculado logo depois da
validação de status. O caminho sem vale não tem um `if` novo no meio: o bloco de pagamentos de hoje
foi movido inteiro para dentro de `if (!$temVale)`, linha por linha, e o bloco novo é um `else` que
chama `pagamentosComVale()`. Dá para apagar o vale do gerador deletando o `else` e o método.

O que o bloco novo faz: `$nota->refresh()` (o `valortotal` da nota é somado pelo banco a partir dos
itens recém-gravados, e o objeto em memória ainda está zerado), calcula
`aConsumir = negocio.valortotal − nota.valortotal` — que é a fatia paga do vale mais a fatia de juros
dele — e desconta esse valor dos pagamentos na ordem **dinheiro → PIX → o resto**, cada um limitado
ao seu valor líquido (`valortotal − valortroco`, porque troco é dinheiro que voltou para a mão do
cliente e não pagou nada). Pagamento que foi inteiro para o vale é descartado; o `cAut` e a bandeira
dos que sobram nunca são tocados. No fim, o **último pagamento absorve a diferença**, porque a rede
do `NFePHPMakeService:895-911` só corrige para menos — faltar centavo passa, sobrar centavo é
rejeição. O `percJuros` passou a ter a base `valorprodutos + valorvales` e a sobra de juros jogada no
último item passou a ser medida contra `jurosDaNota` (com vale, a fatia da mercadoria; sem vale, o
próprio `negocio->valorjuros`, idêntico a hoje). As duplicatas da NFe 55 saem rateadas na proporção
`nota.valortotal / negocio.valortotal`, com a última absorvendo a sobra. Negócio 100% vale recusa a
nota com mensagem em vez de estourar dividindo por zero.

**Método do teste** (bancada em `api/storage/app/vale-teste/`, fora do repositório; XMLs guardados
lá): a mesma nota não pode ser gerada duas vezes, mas o mesmo **negócio** pode
(`$ignorarJaNotados = true`). Duas notas do mesmo negócio só diferem no número e na hora de emissão —
e delas saem `cNF`, `cDV` e a chave — então a segunda nota recebe o número e a emissão da primeira
antes do `montarXml`. Rodando isso duas vezes **sem mudar código**, o XML saiu idêntico: o método
serve. Nada foi transmitido à SEFAZ.

**Resultado dos 6 casos:**

| # | Caso | Resultado |
|---|---|---|
| 1 | Só mercadoria (regressão) | **DIFF VAZIO**, 5.960 bytes idênticos |
| 2 | Mercadoria 100 + vale 200, dinheiro 350 com troco 50 | `vPag 150 − vTroco 50 = 100 = vNF`; troco preservado, vNF só da mercadoria |
| 3 | Mercadoria 200 + vale 100, PIX 150 + cartão 150 | PIX reduzido para 50 com `cAut PIXE2E9988` intacto, cartão intacto 150 com `cAut AUT777777`; soma 200 = vNF |
| 4 | Mercadoria 200 + vale 100, cartão lançado **antes** do dinheiro | o vale consumiu o **dinheiro** (150→50) e deixou o cartão inteiro; soma 200 = vNF |
| 5 | Negócio 100% vale | recusa com "Este negócio é só de Vale Compras e não gera Nota Fiscal…", sem exceção crua |
| 6 | Mercadoria 100 + vale 100 + juros 20 | só 10,00 de juros na nota (`valoroutras`), vNF 110, `vPag 110` |

Extras, além dos seis: NFe 55 com vale a prazo (as duplicatas saem rateadas, `vOrig = vLiq = vNF =
133,32`), e as duas regressões sem vale citadas no topo.

Junto, e não estava no plano: `fechar()` recusava negócio sem nenhum item de mercadoria, o que
impedia a **venda de vale avulso** — que é o caso mais comum do vale (dos 3.718 vales históricos,
quase todos são assim). A validação passou a aceitar vale como conteúdo; sem item **e** sem vale
continua recusando igual.

### Milestone 7 · Conciliação DIMP — **PRONTO**

Domínio novo `Mg\Dimp` (`DimpConciliacaoService` + `DimpConciliacaoController`), rota
`GET v1/dimp/conciliacao?ano=&mes=&codfilial=` com `?html=1`, mPDF A4 retrato no padrão do
`CargaRelatorioService`, blade em `resources/views/dimp/conciliacao.blade.php`. O relatório tem duas
partes: a **narrativa** (quanto entrou de cartão/PIX nos negócios do mês, quanto as notas do mês
declararam, a diferença e o quanto dela é vale compras vendido) e **três conferências que têm que
dar zero**. Lê as duas estruturas de vale, `tblnegociovale` e `tblvalecompra`, e a segunda some
sozinha quando a tabela deixar de existir. Só operação de **saída** entra — compra é dinheiro
saindo, não é DIMP.

A ideia original — "soma dos negócios do mês menos as notas do mês tem que dar zero" — **não** é
computável e foi descartada com motivo: o mesmo item pode estar em **duas notas ativas** (o cupom do
balcão e a NFe 55 do faturamento do crediário). Em julho/2026 são 7.622 itens nessa situação; somar
as notas dobrava R$ 300 mil. No lugar dela ficaram três conferências que são verdade item a item e
podem falhar alto: (1) notas do mês em que `Σ vPag − vTroco ≠ vNF` — a conta que a própria SEFAZ faz,
e exatamente a que o rateio do milestone 6 precisa manter de pé; (2) vale vendido em negócio fechado
sem crédito emitido; (3) venda do PDV em que os pagamentos não cobrem o total cobrado.

Rodado sobre três meses reais de dev. **Julho/2026**: 28.524 negócios de saída, R$ 872.837,45 de
cartão+PIX recebido contra R$ 871.105,19 declarado nas notas, divergência de R$ 1.732,26 — da qual
R$ 473,23 são os 3 vales que o MGLara ainda vendeu no mês. Conferência 1 e 2 deram **zero**.
**Setembro/2026** (o mês dos negócios de teste com vale): as **três** deram zero, e a seção do vale
se preencheu — 8 vales, cobrado R$ 996,68, face R$ 986,68, cartão/PIX R$ 326,68. O PDF foi gerado e
conferido página a página.

**Achado real, não é gap do trabalho** (relatado no chat, sem abrir task): a conferência 3 encontrou
**4 vendas do PDV, entre junho e julho, com pagamento lançado em duplicidade** — negócios 4485692
(R$ 61,42 cobrados, R$ 122,84 pagos), 4513488 (85,00 × 149,00), 4531948 (169,06 × 338,12) e um de
1 centavo, o 4501184. Três deles são exatamente o dobro. E 1 nota de junho (a 1262041) com 1 centavo
de diferença entre `Σ vPag` e `vNF`. São dados de produção anteriores a este trabalho.

### Milestone 8 · Consumo por escopo — **PRONTO**

`PdvValeEscopoService` novo: `favorecidos()` lista as escolas com crédito vivo e o saldo de cada uma,
`turmas()` abre a escola, e `selecionar()` monta o lote em **FIFO pelo vencimento**, com só o último
vale entrando parcial. Lê as duas estruturas de vale em `union`. Vale **ao portador fica fora do
escopo** e recusa com mensagem: são todos do mesmo favorecido (Consumidor), então "consumir o crédito
do Consumidor" gastaria o vale de um estranho — ao portador só por bipagem. Rotas
`v1/pdv/vale-escopo/favorecido`, `…/{cod}/turma` e `…/selecionar`.

A trava que o plano pedia entrou: **`reconferirSaldos()` com `lockForUpdate`** no `fechar()`, antes
do `baixarVales`, travando os títulos **sempre na ordem do código** para dois fechamentos não
travarem um no outro. Sem ela, dois PDVs montam o FIFO sobre o mesmo pool da escola, os dois passam
na validação da tela e o segundo estoura o crédito em silêncio.

Os N pagamentos viram **um único `detPag tPag=12`** na nota (decisão 6), somado dentro do laço do
rateio para entrar na conta da diferença absorvida pelo último pagamento. No banco continuam N — é
assim que o `baixarVales` amortiza cada crédito e que o cancelamento sabe o que estornar. **Atenção:
isso muda o XML de um caso que já existe hoje** — negócio que consome 2 vales bipados passa a sair
com 1 detPag em vez de 2. É a decisão 6, mas está fora do "caminho sem vale idêntico" e merece um
olhar na validação.

No app: `FormaVale.vue` ganhou dois modos num `q-btn-toggle` — **Bipar o vale** (o de sempre, agora
com `MgInput` no lugar do `q-input` cru, TASK-174) e **Pela escola** (lista de escolas com saldo →
chips de turma → lote em FIFO com quanto sai de cada vale). O `TotalNegocio.vue` agrupa os vales numa
linha "Vale Compras · N vales" que abre no toque, para o operador ainda conseguir tirar um do lote.

Consertado durante a revisão, não é task: a linha agrupada do `tPag 12` era criada **depois** do
laço, então ela caía como último pagamento e era ela que absorvia a sobra de centavo do rateio — a
nota declararia mais vale do que foi usado. Agora ela entra na frente da lista, e só absorve quando
for o único pagamento da nota.

Verificado em dev: FIFO gastando 3 vales com o último parcial; escopo com saldo menor que a compra
mostrando o que falta; ao portador recusado; 3 pagamentos no banco virando **1 `detPag tPag=12` com
`vPag 120.00`** no XML; **dois PDVs no mesmo pool** — o primeiro fecha, o segundo é recusado com "O
vale N04541420-VALC não tem mais saldo suficiente: disponível R$ 0,00, faltam R$ 80,00. Alguém
consumiu esse crédito em outro caixa", e o saldo do título **nunca fica positivo**; cancelar a venda
devolve o saldo exato dos dois vales; e excluir um pagamento do lote impede o fechamento. As
regressões do milestone 6 foram **re-rodadas depois destas mudanças e continuam com diff vazio**.

# Vale Compras dentro do negócio

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
| 5c | **O crédito emitido é sempre o valor de FACE do vale** | `tblnegociovale` guarda `valorprodutos` (o crédito) e `valortotal` (a fatia paga) separados |
| 6 | Consumo de vários vales = **N pagamentos no banco, agrupados na tela e na nota** | `baixarVales`/`estornarBaixaVales` seguem intactos; um único `detPag tPag=12` na NFC-e |
| 7 | **REVISADA — o módulo vale compras do MGLara morre na Fase 2** e é desabilitado no go-live | Sem fallback a partir daí, por decisão explícita. **A aplicação MGLara continua no ar** — ela ainda é dona do estoque e das imagens (ver seção 6) |
| 8 | **Validade de 1 ano** da emissão, informativa | Sem job de expiração nesta rodada; o saldo continua resgatável depois da data |
| 9 | **Comprovante térmico 80mm com código de barras**, como o vale de devolução | O caixa passa a bipar em vez de digitar o número |
| 10 | **REVISADA — a estrutura TRANSACIONAL morre e é convertida; o CATÁLOGO evolui in place** | Os 204 modelos nunca se movem: `ALTER TABLE … RENAME` resolve. Nada com o nome `valecompra` sobrevive assim mesmo, e some a conversão do catálogo |
| 11 | **Prefixo `tblnegocio` em tudo que aponta para `codnegocio`** | O vale emitido é `tblnegociovale`; o catálogo, que não aponta para negócio, é `tblvalemodelo` |
| 12 | **Modelo enxuto**: sem `desconto`, e `modelo`+`turma`+`ano` viram uma `descricao` só | Some `totalprodutos`; `total` vira `valorprodutos`. A face passa a ser `valortotal` (decisão 21) |
| 13 | **Vale ao portador**: favorecido opcional no modelo e `= Consumidor (1)` no vale quando não há escola | Capacidade nova — passa a dar para vender vale-presente avulso; o resgate por bipagem já funciona sem mudança |
| 14 | **O DDL do catálogo acontece já na Fase 2** | Zero retrabalho na tela nova, ao custo de perder o fallback do MGLara antes de a venda nova estar validada |
| 15 | **Os itens do vale são editáveis na venda** — dá para tirar item e mudar quantidade | O valor do vale passa a ser a **soma dos seus itens** |
| 16 | **Na grade do vale só se tira e ajusta — não se acrescenta** | O modelo define o universo do vale; item fora da lista o cliente leva como mercadoria. O `InputBarras` não é tocado e a bipagem segue caindo sempre em mercadoria |
| 17 | **Vale sem modelo não tem grade: o valor é digitado** | O valor digitado é `valoravulso` (decisão 21), e o `confereTotais` não confere o vale contra os itens dele, só o total do negócio |
| 18 | **Zero toque no que é de item de mercadoria** — componentes, formulários e actions ficam como estão; o vale ganha os seus, ainda que duplicados | Separabilidade acima de reuso: dá para apagar o vale inteiro sem tocar no PDV. Custo aceito: melhoria futura na grade de item não propaga para a do vale |
| 19 | **Desconto, frete, seguro, outras e juros NÃO descem ao item do vale** — param em `tblnegociovale` | O item do vale é só quantidade × preço; some metade das colunas dele e some o rateio item a item |
| 20 | **`valorvales` é BRUTO, simétrico ao `valorprodutos`** — e o `valordesconto`/`valorjuros` do negócio passam a somar mercadoria + vale | Custa dois ajustes que a decisão 2 tinha dispensado: o denominador do custo de estoque no MGLara e o do `percJuros` da NFC-e |
| 21 | **NOVA (24/09) — o valor do modelo vem em TRÊS partes**: `valorprodutos` (soma dos itens) + `valoravulso` (digitado) = `valortotal` (a face) | É o que permite modelo sem produto nenhum — vale-presente de valor redondo. Só o avulso é digitado; os outros dois o service calcula. **Atenção ao nome:** `valortotal` é a FACE no catálogo e a FATIA PAGA em `tblnegociovale` — ver seção 2 |

Do escopo original, também decidido: vale usado em **qualquer filial, sem restrição**, e **sem
breakage/expiração** nesta rodada.

### O que a decisão 2 dispensou

Por o vale não ser item nem produto, saem do escopo: criar produto "VALE COMPRAS", flag
`indicadorvenda`, guarda no `ProcessarVendaService` (RH), bloqueio de devolução do item de vale,
guarda contra o produto ser bipado por engano, action de item que não funde duplicados, e as
exclusões em `juntarItensPorBarras` / `informarPessoa` / `itemSalvar`. Nenhum desses caminhos
enxerga o vale. **Exceção:** o job de estoque do MGLara entrou no escopo (milestone 3), não porque
enxergue o vale, mas porque o rateio de desconto dele está obsoleto e o vale expõe isso.

---

## 2. Modelagem

**Duas tabelas novas** (`api/database/vale.sql`):

- **`tblnegociovale`** — `codnegociovale` PK · `uuid` unique · `codnegocio` NOT NULL · `codtitulo`
  unique null · `codpessoafavorecido` NOT NULL (escola, ou **Consumidor = 1** se ao portador) ·
  `aluno` null · `turma` null · `codvalemodelo` null · `valorprodutos` (face = o crédito; soma dos
  itens, ou digitado quando não há itens) · `valordesconto` · `valorfrete` · `valorseguro` ·
  `valoroutras` · `valorjuros` · `valortotal` · `validade` · `codvalecompra` null (número antigo,
  impresso nos papéis em circulação) · `observacoes` · `inativo` · auditoria.
- **`tblnegociovaleprodutobarra`** — `codnegociovaleprodutobarra` PK · `uuid` unique ·
  `codnegociovale` · `codprodutobarra` · `quantidade` · `valorunitario` · `valorprodutos` ·
  `ordenacao` · `inativo` (soft-delete) · auditoria. Sem desconto/frete/seguro/outras: item de vale é
  quantidade × preço (decisão 19).

**Uma coluna nova:** `tblnegocio.valorvales` numeric(14,2).

**Catálogo — evolui in place** (`api/database/vale_catalogo.sql`), sem mover um dado. São **dois
blocos**, cada um com a sua guarda: o 1º só roda se a tabela antiga existir (é isso que protege o
`UPDATE` de concatenação de rodar duas vezes), o 2º só se `valoravulso` ainda não existir. Produção
roda o arquivo inteiro de uma vez.

```sql
-- BLOCO 1 — rename
ALTER TABLE tblvalecompramodelo RENAME TO tblvalemodelo;
ALTER TABLE tblvalemodelo RENAME COLUMN codvalecompramodelo TO codvalemodelo;
ALTER TABLE tblvalemodelo RENAME COLUMN total TO valorprodutos;
ALTER SEQUENCE tblvalecompramodelo_codvalecompramodelo_seq
      RENAME TO tblvalemodelo_codvalemodelo_seq;
-- a coluna `modelo` passa a ser a descrição: sem campo novo, sem rename.
-- 63 dos 204 estouram varchar(50) depois de concatenar (o maior fica com 85)
ALTER TABLE tblvalemodelo ALTER COLUMN modelo TYPE varchar(100);
-- a turma só entra quando acrescenta informação: em 105 dos 139 modelos com
-- turma o texto já está dentro de `modelo`, e concatenar cru duplicaria.
UPDATE tblvalemodelo SET modelo = concat_ws(' - ', modelo,
         CASE WHEN coalesce(btrim(turma),'') = ''                    THEN NULL
              WHEN position(lower(btrim(turma)) in lower(modelo)) > 0 THEN NULL
              ELSE btrim(turma) END, ano);
ALTER TABLE tblvalemodelo ALTER COLUMN codpessoafavorecido DROP NOT NULL;
ALTER TABLE tblvalemodelo DROP COLUMN turma, DROP COLUMN ano,
                          DROP COLUMN desconto, DROP COLUMN totalprodutos;
-- idem tblvalecompramodeloprodutobarra -> tblvalemodeloprodutobarra, mais
-- `preco` -> `valorunitario` e `total` -> `valorprodutos`, para bater com
-- tblnegocioprodutobarra e a semeadura do milestone 2 ser cópia campo a campo.

-- BLOCO 2 — valor em três partes (decisão 21)
ALTER TABLE tblvalemodelo ADD COLUMN valoravulso numeric(14,2);
ALTER TABLE tblvalemodelo ADD COLUMN valortotal  numeric(14,2);
-- as três ficam NOT NULL DEFAULT 0 para a soma nunca dar null
```

Os números justificam os cortes: **0 de 204** modelos usam `desconto`, **139 de 204** têm `turma`
preenchida (por isso a descrição concatena em vez de descartar) e **1 de 204** usa `observacoes`, que
fica. A FK de `tblvalecompra` acompanha o rename sozinha; quem quebra é o PHP do MGLara **e 4 pontos
da API** — `ProdutoBarraService::unificaBarras()`, `ProdutoBarra`, `Pessoa` e `ValeCompra` —, todos
repontados no milestone 1.

### O valor do modelo, e o cuidado com `valortotal`

`tblvalemodelo` guarda o valor em três colunas, todas `numeric(14,2) NOT NULL DEFAULT 0`:

| coluna | de onde vem | o que é |
|---|---|---|
| `valorprodutos` | calculada — soma dos itens do kit | quanto o kit vale em mercadoria |
| `valoravulso` | **digitada** | o único campo de valor que o usuário edita |
| `valortotal` | calculada — `valorprodutos + valoravulso` | **a face**: o crédito que a emissão gera |

Kit vazio + avulso digitado = vale-presente de valor redondo, sem lista de produtos.

⚠️ **`valortotal` significa coisas diferentes nas duas tabelas.** No catálogo é a **face**; em
`tblnegociovale` é a **fatia paga** (no exemplo abaixo, 180 contra uma face de 200). Ao semear o vale
a partir do modelo, o milestone 2 mapeia:

```
tblvalemodelo.valortotal  ──>  tblnegociovale.valorprodutos   (a face, o crédito)
tblvalemodelo.valoravulso ──>  usado quando o modelo não tem itens
```

**Ponto em aberto para o milestone 2:** decidir se `tblnegociovale` também ganha as três colunas (e
aí `valortotal` muda de sentido lá) ou se fica com `valorprodutos` = face e `valortotal` = fatia
paga, convivendo com a colisão de nome. Hoje o plano assume a segunda.

### Composição dos totais

```
negocio.valortotal = valorprodutos + valorvales − valordesconto + valorfrete
                     + valorseguro + valoroutras + valorjuros
```

`valorprodutos` e `valorvales` são **brutos**; os valores de cabeçalho somam as duas fatias
(decisão 20). Exemplo — R$ 500 de material + vale de R$ 200 com R$ 70 de desconto, cliente paga 630:

| | Face | Desconto rateado | Líquido | Crédito |
|---|---|---|---|---|
| Mercadoria | 500,00 | 50,00 | 450,00 | — |
| Vale | 200,00 | 20,00 | 180,00 | **200,00** |

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
`vale_catalogo.sql` (os dois blocos da seção 2) · models `ValeModelo` e `ValeModeloProdutoBarra` em
`api/app/Mg/Vale/` · domínio `Mg\Vale` no padrão da casa (Controller, Service estático, Resource,
FormRequests, rotas em `auth:api`+`v1`, `Autorizador::autoriza()`) · tela de CRUD no app **negocios**
(menu Cadastros → Modelos de Vale), com descrição única, favorecido opcional e o valor em três partes
(decisão 21): produtos calculado, avulso digitado, total = face.
Listagem ordenada por **favorecido, depois descrição**, com favorecido na 1ª coluna; filtros na
drawer esquerda (padrão `FilterDrawerShell`/`FilterGroup` do Pix no app contas): favorecido,
descrição, faixa de valor sobre a face, e situação Ativos/Inativos/Todos. O default da listagem é
**só ativos** — 169 dos 204 modelos estão inativos, o catálogo é sazonal.
Formulário de novo/editar em **tela à parte** (`/vale-modelo/novo` e `/vale-modelo/:cod`), não em
dialog.
⚠️ **Derruba a venda de vale no MGLara**, que só volta a existir no milestone 4. Na API, 4 pontos
precisam ser repontados no mesmo commit (ver seção 2).
**Valida:** `select count(*), count(modelo) from tblvalemodelo` → 204/204; cadastra, edita e inativa
um modelo pela tela; cadastra um modelo **sem produto nenhum** só com valor avulso.

### 2 · Vale dentro do negócio
Começa pelo sync do catálogo pro PDV, que é onde ele passa a ser necessário: `GET v1/pdv/vale-modelo`
espelhando `PdvService::formaPagamento()`, `db.version(7)`, `sincronizarValeModelo()` e o carimbo no
`.sort()[0]` de `inicializaVars()`.
⚠️ O template de sync lê `data[0].sincronizado` sem checar lista vazia e o catálogo é sazonal:
guardar `if (!data.length) return`, e testar com catálogo cheio **e vazio**.
Depois: `vale.sql` (as duas tabelas novas e `tblnegocio.valorvales`) · models `NegocioVale` e
`NegocioValeProdutoBarra` em `api/app/Mg/Negocio/` · `criar()` inicializa `vales: []` e
`recalcularValorTotal()` soma a coleção · actions `valeAdicionar`, `valeExcluir`, `valeItemSalvar`,
`valeItemInativar` · `ValeDialog.vue` (modelo opcional → favorecido → aluno/turma; com modelo semeia
os itens, sem modelo o valor é digitado; sem favorecido grava Consumidor) · `ListagemItensVale.vue`,
uma seção por vale sob "Vale A"/"Vale B", sem botão de acrescentar · `negocioAberto` faz upsert de
`tblnegociovale` e dos itens por uuid, e o `confereTotais()` passa a somar os vales.
⚠️ **Trava temporária: negócio com vale não fecha.** Sem ela este milestone produziria negócio
fechado sem crédito. O milestone 4 remove.
**Valida:** monta um vale, tira item, muda quantidade, vê o total do negócio subir, sincroniza e
confere `tblnegociovale` e os itens; dois vales no mesmo negócio dão duas seções.

### 3 · Rateio entre mercadoria e vales
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

### 4 · Créditos, estorno e comprovante
`fechar()` cria o título tipo 3 / conta 83 em nome do favorecido, vencimento +1 ano, condicionado a
`codtitulo IS NULL`, com unique index como rede. O título fica **solto**, só referenciado por
`tblnegociovale.codtitulo` — nunca pendurado em `tblnegocioformapagamento`, porque
`negocioFechado():166-173` reescreve `codpessoa`, `codtipotitulo` e `codcontacontabil` de todo título
pendurado num pagamento, a cada PUT de negócio fechado · `cancelar()` ganha loop próprio, com mensagem
clara quando o vale já foi usado · bloqueia vale em natureza sem `financeiro` e vale zerado ·
comprovante térmico 80mm com `VAL+codtitulo` lendo `tblnegociovale` · **remove a trava do
milestone 2**.
⚠️ Entra a trava **negócio com vale não emite NFC-e**, que o milestone 5 remove.
**Valida:** fecha → título 3 / conta 83 / escola / `creditosaldo` negativo pela **face** → imprime →
**bipa o `VAL…` de volta** e resgata em outra filial → cancela um sem uso (estorna) e um já usado
(recusa com mensagem) → PUT no negócio já fechado trocando o cliente, e o título continua intacto.

### 5 · Fiscal: rateio do `detPag`
Em `gerarNotaFiscalDoNegocio`: `$nota->refresh()` e ratear os pagamentos contra `$nota->valortotal`,
consumindo o valor dos vales primeiro do **dinheiro → PIX → cartão**; cap em
`valortotal − valortroco`; descartar pagamento zerado; **último pagamento absorve a diferença** (a
rede do `NFePHPMakeService:895-911` só corrige para menos, errar para mais é rejeição). Corrigir o
`percJuros` (`:124`) e a sobra jogada no último item (`:252-257`), que com vale é justamente a fatia
dele. Tratar as duplicatas da NFe 55. Remove a trava do milestone 4.
**Tudo condicionado a "existe vale neste negócio"**, para o caminho sem vale seguir idêntico.
**Valida:** homologação com só mercadoria (**regressão**, diff de XML byte a byte), mercadoria+vale em
dinheiro com troco, em PIX, em cartão, e nota de retirada com `tPag 12`.

### 6 · Conciliação DIMP
Relatório mensal (mPDF, padrão `CargaRelatorioService` com `?html=1`): recebimentos cartão/PIX ×
notas emitidas × vendas de vale × consumos `tPag=12` × recebimentos de crediário, fechando em zero.
Vem logo depois do fiscal de propósito — é o milestone 5 que cria a divergência entre o `cAut` da
adquirente e o valor da NFC-e, e este relatório é a única peça que a explica. Lê `tblnegociovale`
**e** `tblvalecompra` em `UNION` até o milestone 8.
**Valida:** rodar sobre um mês fechado e conferir que a diferença dá zero.

### 7 · Consumo por escopo
Escola / turma / bipados, FIFO com o último vale parcial, N pagamentos agrupados na tela e num único
`detPag tPag=12`. Vale ao portador fica fora do escopo por escola (todos têm favorecido = Consumidor)
— só por bipagem.
⚠️ **Reconferir saldo com lock no `fechar()`** antes do `baixarVales`: hoje não existe validação de
saldo no servidor, e com escopo "escola inteira" dois PDVs montam FIFO sobre o mesmo pool.
**Valida:** consumo com saldo maior e menor que a compra; **dois PDVs no mesmo pool ao mesmo tempo**;
excluir um pagamento do lote; cancelar e reconferir saldos.

### 8 · Conversão do legado e limpeza
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
| **Alto** — o milestone 5 toca o gerador de NFC-e de **toda** a empresa | `NotaFiscalNegocioService` |
| **Alto** — consumo por escopo sem validação de saldo no servidor → estouro do crédito | `PdvNegocioPrazoService::baixarVales` |
| **Alto** — janela sem nenhuma tela de venda de vale entre os milestones 1 e 4 | aceito; ver prazo abaixo |
| **Médio** — `cancelar()` não alcança título solto sem código novo → crédito órfão vivo | `PdvNegocioService:388-398` |
| **Médio** — `fechar()` concorrente sem `lockForUpdate` (furo que já existe, o vale amplia) | `PdvNegocioService:207-210` |
| **Fiscal** — `cAut` real com `vPag` reduzido é o que o cruzamento DIMP enxerga; o milestone 6 é o que explica | doc-1 §4.2 |
| **Fiscal** — escolher item a item aproxima o vale de "mercadoria determinada", o ponto mais sensível da tese (requalificação como venda para entrega futura) | doc-1 §6; levar na consulta à SEFAZ-MT |

**Prazo:** janeiro concentra **84% da venda** de vale compras — 864 vales e R$ 91,6 mil desde 2024,
contra ~87 de março a dezembro somados. Setembro a dezembro dão ~18 vales/ano, então quebrar o MGLara
agora custa quase nada, mas a venda nova precisa estar validada **antes de janeiro**.

**Não desligar a aplicação MGLara.** O go-live desabilita o módulo vale compras dela, mas ela segue
dona da movimentação de estoque de todo negócio (`PdvNegocioService::movimentarEstoque`), do custo
médio, da fila de jobs de estoque e das imagens de produto. Se for desligada, o estoque da empresa
para de se mexer **em silêncio** — a falha é engolida num `try/catch` que só escreve no log.

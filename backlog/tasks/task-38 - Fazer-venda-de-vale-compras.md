---
id: TASK-38
title: Fazer venda de vale-compras
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-09-26 19:21'
labels:
  - negocios
  - api
dependencies: []
priority: high
type: feature
ordinal: 58000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Converter a venda de Vale Compras do MGLara para o app negocios: a venda acontece DENTRO
da tela de negocio, junto com a venda de material, nao em tela separada.

MOTIVO: cliente compra material e vale-compras na mesma visita e quer passar o cartao uma vez
so, principalmente quando parcela. Hoje sao duas vendas em telas diferentes (MGLara + negocios),
cada uma passa o cartao separado e muitas vezes nenhuma das duas atinge o valor minimo de
parcela.

### Desenho (20 decisoes, 9 milestones) -- fonte de verdade e .claude/plano-vale-compras.md

O vale virou um BLOCO PROPRIO do negocio -- nao e item, nao e produto. Nada de mercadoria foi
tocado (InputBarras, ListagemProdutos, itemAdicionar/itemSalvar/itemInativar/
juntarItensPorBarras ficam como estavam). Catalogo evoluiu in place (tblvalecompramodelo ->
tblvalemodelo, 204 modelos preservados). O credito continua titulo tipo 3 / conta 83, resgatavel
pelo wizard Receber (components/offline/receber/FormaVale.vue -- renomeado de PagamentoVale.vue).

Milestones: 1 CRUD de modelos | 2 impressao do modelo (orcamento p/ escola validar) | 3 vale
dentro do negocio | 4 rateio desconto/frete/etc entre mercadoria e vales | 5 credito/estorno/
comprovante | 6 fiscal (rateio do detPag na NFC-e) | 7 conciliacao DIMP | 8 consumo por escopo
(escola/turma/bipados) | 9 conversao do legado (3.718 vales antigos) + desligar MGLara.

### Por que tem que ser um negocio so

Cada transacao de cartao (Lio, PagarMe, Saurus) fica amarrada a uma linha de
tblnegocioformapagamento, com indice unico por pedido. Uma maquininha nao se divide entre dois
negocios. Entao 'uma passada de cartao' implica produtos e vale no MESMO negocio.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Milestone 1-2: cadastro de modelos de vale + impressao do orcamento p/ escola validar (feito em dev, aguardando validacao na tela)
- [x] #2 Milestone 3: vale vendido dentro do negocio, sem abrir o MGLara; mais de um vale no mesmo negocio funciona (feito em dev, aguardando validacao na tela)
- [x] #3 Milestone 4: desconto/frete/seguro/outras/juros de cabecalho rateados entre mercadoria e vales, sem alterar a face do vale (feito em dev, aguardando validacao na tela)
- [x] #4 Milestone 5: fechamento gera credito tipo 3 em nome do favorecido; cancelamento estorna; comprovante termico com escola/aluno/turma/lista/codigo de barras (feito em dev, aguardando validacao na tela)
- [x] #5 Milestone 6: NFC-e da venda mista sai so com a mercadoria, detPag rateado sem vTroco nem item ficticio (regressao SEM vale testada com diff de XML byte a byte -- vazio)
- [x] #6 Milestone 7: relatorio de conciliacao DIMP mensal
- [x] #7 Milestone 8: consumo por escopo (escola/turma/vales bipados) com FIFO e trava de saldo sob lock
- [x] #8 Milestone 9: os 3.718 vales antigos convertidos para negocio+tblnegociovale, titulos repontados, tabelas tblvalecompra* dropadas, codigo do legado removido (o modulo do MGLara ja saiu; a aplicacao MGLara continua no ar) (feito em dev, aguardando validacao)
- [ ] #9 Comissao de caixa nao conta o vale compras (so a mercadoria do negocio)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
MILESTONE 1 (CRUD de modelos de vale) implementado em 2026-09-24, aguardando validacao.

Feito:
- api/database/vale_catalogo.sql RODADO no banco de DEV: tblvalecompramodelo ->
  tblvalemodelo e tblvalecompramodeloprodutobarra -> tblvalemodeloprodutobarra,
  in place, 204/204 modelos e 4.369 itens preservados. Producao roda no go-live, pelo Fabio (regra fixa no fim do plano)  -- nao e pendencia.
- Dominio Mg\Vale na api (model, service, resource, form requests, controller) e
  rotas v1/vale-modelo em auth:api.
- App negocios: menu Cadastros > Modelos de Vale, rota /vale-modelo, store de
  dominio valeModelo.js e tela ValeModeloPage.vue.

Fora do escopo deste milestone (vao no milestone 2): endpoint v1/pdv/vale-modelo,
db.version(7) do Dexie e sincronizarValeModelo().

QUEBRA CONHECIDA E PREVISTA: o modulo vale compras do MGLara (ValeCompraController,
ValeCompraModeloController e 9 blades) para de funcionar ate o milestone 4.
Na api foram repontados 4 lugares que tambem quebrariam: ProdutoBarraService::
unificaBarras(), ProdutoBarra, Pessoa e ValeCompra.

---

MILESTONE 2 (Impressao do modelo de vale) implementado em 2026-09-24, aguardando validacao.

Documento A4 "Modelo de Vale Compras", com precos, para a escola conferir itens e valores
antes da temporada. Sem validade: leva so a data da impressao no rodape, porque preco de material
muda entre a validacao (nov/dez) e a venda (janeiro).

Feito:
- api/app/Mg/Vale/ValeModeloRelatorioService.php com html() e pdf() (mPDF, A4 retrato),
  no padrao do Mg\Grao\CargaRelatorioService.
- api/resources/views/vale-modelo/relatorio.blade.php: cabecalho da marca repetido em
  toda pagina, favorecido, descricao do modelo, observacoes, tabela de itens (codigo,
  descricao, quantidade, preco unitario e total), avulso quando houver e a face do vale.
- ValeModeloController@relatorio + rota GET v1/vale-modelo/{valeModelo}/relatorio, com
  ?html=1 devolvendo o HTML cru para ajustar layout sem re-renderizar PDF.
- App negocios: botao imprimir (icone print) na linha da listagem, via @components/abrirPdf
  (modal MgRelatorioPdfDialog no desktop, nova aba no mobile).

Decisoes:
- O documento se chama "MODELO DE VALE COMPRAS", nao "Vale Compras": sem a palavra modelo
  a escola/cliente pode entender que e um vale ja comprado, e nao a lista a validar.
- CABECALHO SO COM O LOGO, sem filial e sem o nome escrito ao lado (o logo ja e o nome).
  O modelo e catalogo da empresa inteira e nao tem filial vinculada; pendurar a loja do
  usuario logado seria inventar um vinculo que os dados nao tem.
- A linha final e "TOTAL", nunca "Valor do Vale": mesma armadilha do titulo, da a
  entender que existe um vale ja comprado.
- Logo com largura E altura explicitas, na proporcao do arquivo (MGPapelariaLogo.jpeg,
  402x76 -> 45mm x 8,5mm). So com a largura o mPDF estica a imagem.
- COLUNA DE FOTO do produto (10mm). As fotos do MGLara sao 1000x1000 com ~60KB e nao
  existe variante miniatura no servidor: o service baixa em PARALELO (curl_multi) e
  REDUZ com GD antes de embutir. Cruas e em serie seriam ~3MB de PDF e 50 idas a rede;
  assim um kit de 54 itens (48 com foto) da 263KB em 1,6s. Cada miniatura leva largura
  E altura em mm calculadas da propria imagem, para nenhuma sair esticada. Foto que nao
  baixar nao sai e o resto imprime igual -- o documento vale pela lista, e nao pode
  deixar de sair porque o servidor de imagens piscou.
  ATENCAO: em dev /opt/www/Arquivos/Imagens esta vazio, entao as URLs apontam para
  producao (sistema.mgpapelaria.com.br). A api precisa de saida HTTPS para esse host.
- O CODIGO DE BARRAS saiu da coluna propria e virou a segunda linha da descricao; a
  largura liberada foi toda para a descricao (a tabela tem 5 colunas: foto, descricao,
  qtde, preco e total).
- ALTURA DE LINHA UNIFORME via <img> transparente de 1x1 esticada a 10mm nas linhas sem
  foto. O mPDF ignora height no <td>, mas respeita a altura de uma imagem. Sem isso a
  linha sem foto ficava em 9,3mm contra 12,2mm da linha com foto e a lista pulava.
- Os itens saem do ValeModeloProdutoBarraResource, o mesmo que alimenta a tela, para
  "conferir o PDF contra a tela" nao comparar duas montagens de descricao diferentes.
- Modelo avulso puro (sem itens) nao imprime cabecalho de tabela, so a face. O subtotal
  "Produtos" so aparece quando existe avulso; num kit puro ele repetiria a face.

Verificado em dev (tinker + render do PDF em imagem):
- Modelo 195 (kit puro, 54 itens, favorecido Escola Maria Chica): 2 paginas, thead
  repetido, TOTAL 543,10 batendo com o banco.
- Modelo 216 (avulso puro, ao portador): "Ao portador", Avulso 100,00, TOTAL 100,00.
  Sem itens nao sai cabecalho de tabela, so a linha TOTAL.
- Modelo 215 (1 item + avulso): Produtos 99,90 + Avulso 100,00 = TOTAL 199,90.
- Foto: servidor inalcancavel e imagem 404 devolvem zero miniaturas sem excecao, e o
  PDF sai do mesmo jeito (so sem a foto).
- Kit + avulso (montado em memoria): Produtos 543,10 + Avulso 50,00 = face 593,10.
- ?html=1 -> 200 text/html; sem o parametro -> 200 application/pdf inline; modelo
  inexistente -> 404. 12 queries para 54 itens (sem N+1).

Fora do escopo deste milestone: nada de PDV, negocio ou Dexie.

---

MILESTONE 3 (Vale dentro do negocio) implementado em 2026-09-24, aguardando validacao.

O vale virou um BLOCO PROPRIO do negocio: nao e produto, nao e item. Nada de
mercadoria foi tocado (InputBarras, ListagemProdutos e as actions
itemAdicionar/itemSalvar/itemInativar/juntarItensPorBarras ficaram como estavam).

Feito:
- api/database/vale.sql RODADO no banco de DEV (reaplicavel): tblnegociovale,
  tblnegociovaleprodutobarra e a coluna tblnegocio.valorvales.
  Producao roda no go-live, pelo Fabio (regra fixa no fim do plano)  -- nao e pendencia.
- Models NegocioVale e NegocioValeProdutoBarra + Resources; NegocioResource passou
  a devolver 'vales' (com itens, produto, barras e imagem) para a recarga do PDV.
- Sync do catalogo pro PDV: GET v1/pdv/vale-modelo (PdvService::valeModelo),
  db.version(7) do Dexie e sincronizarValeModelo() com guarda de catalogo vazio.
- PdvNegocioService: importarVales() faz upsert por uuid (reenvio nao duplica) e
  confereTotais() passou a somar as duas colecoes (mercadoria + vales).
- App negocios: actions vale* no store, ValeDialog.vue e ListagemItensVale.vue
  (uma secao por vale, 'Vale A'/'Vale B', abaixo da grade de mercadoria).

TRAVA TEMPORARIA E PROPOSITAL: negocio com vale NAO FECHA (front e backend). Sem
ela, fechar geraria venda cobrada do cliente sem credito nenhum. Sai no milestone 5,
que e quem emite o titulo tipo 3.

A decidir na validacao: (a) o ponto de entrada do vale e um cabecalho 'Vale Compras'
com botao + abaixo da grade de produtos; (b) o preco do item semeado vem do MODELO
(o que a escola validou), nao do preco atual do produto.

---

MILESTONES 4 a 8 implementados na noite de 24->25/09/2026, aguardando validacao.
O relato completo, milestone a milestone, esta em .claude/plano-vale-compras.md,
secao RESULTADO DA NOITE (topo) e Execucao da noite (fim).

VEREDITO DA REGRESSAO FISCAL: o XML de uma venda so com mercadoria saiu BYTE A
BYTE IDENTICO antes e depois da mudanca do gerador de NFC-e (5.960 bytes, diff
vazio). Mais duas formas sem vale tambem deram diff vazio: com juros parcelados
(5.945 bytes) e NFe 55 a prazo com duplicatas (5.493 bytes). Tudo que o vale faz
no gerador esta atras de um unico $temVale.

M4 Rateio: desconto/frete/seguro/outras divididos entre mercadoria e vales na
proporcao dos brutos; aplicarValores() intacto para a mercadoria e a fatia do
vale gravada direto em tblnegociovale. A FACE do vale nunca se mexe. No MGLara,
o job de estoque parou de ajustar por desconto de cabecalho e le o proprio item.

M5 Creditos: PdvNegocioValeService novo. fechar() emite titulo tipo 3 / conta 83
em nome do favorecido, valor = a face, numero N{codnegocio}-VAL{A,B..}, titulo
SOLTO (so tblnegociovale.codtitulo aponta). cancelar() estorna, e recusa com
mensagem quando o vale ja foi usado. Comprovante 80mm com escola, aluno, turma e
a lista do kit. As duas travas temporarias sairam.

M6 Fiscal: rateio do detPag consumindo o vale primeiro do dinheiro, depois PIX,
depois o resto; cap no liquido de cada pagamento; ultimo pagamento absorve a
diferenca; percJuros e a sobra dos juros corrigidos; duplicatas da NFe 55
rateadas. Os 6 casos pedidos foram testados gerando XML em arquivo, sem
transmitir nada.

M7 DIMP: dominio Mg\Dimp, rota v1/dimp/conciliacao?ano=&mes= com ?html=1, PDF
mPDF. Tres conferencias que tem que dar zero. Rodado sobre julho e junho/2026.

M8 Escopo: PdvValeEscopoService com FIFO por escola/turma, vale ao portador fora
do escopo, reconferirSaldos() com lockForUpdate no fechar() (dois PDVs no mesmo
pool: o segundo e recusado e o saldo nunca fica positivo), N pagamentos no banco
virando 1 detPag tPag=12 na nota. FormaVale.vue ganhou o modo 'Pela escola'.

ACHADO EM DADOS DE PRODUCAO (nao e desta task, relatado para decisao): a
conferencia do DIMP encontrou 4 vendas do PDV entre junho e julho com pagamento
lancado em duplicidade -- negocios 4485692, 4513488, 4531948 e 4501184.

RESOLVIDO em 25/09 -- ver bloco STATUS EM 25/09, no fim destas notas.

---

## STATUS EM 25/09 -- ler .claude/plano-vale-compras.md primeiro

Milestones 1 a 8 implementados em dev, cada um com nota detalhada acima (arquivos, SQL rodado,
decisoes tecnicas, verificacao no banco). NENHUM validado na tela ainda -- so checagem de
codigo (php -l, eslint, compilacao Vue) e conferencia direto no banco/XML. Milestone 9 nao
comecou.

Na arvore de trabalho, sem commit: portador do titulo do vale = null (era CARTEIRA) e numeracao
= V{codnegocio}-{A,B,C} (era N...-VAL{A,B,C}), em PdvNegocioValeService::emitirCredito.

Pendencias fora desta task: TASK-175 (conferir saidavalor do estoque), 3 campos com q-input cru
nas telas de vale-modelo (contra a regra nova do CLAUDE.md, ainda nao corrigidos), negocios de
teste no banco de dev (4541400-4541429) nao limpos.

MILESTONE 9 (conversao do legado e limpeza) implementado em 26/09/2026, aguardando validacao. api/database/vale_conversao.sql RODADO em DEV: 3.718 vales viraram negocio (codpdv NULL, natureza Venda, sem item) + pagamentos + tblnegociovale no MESMO titulo + itens; 313 titulos 240 repontados; tblvalecompra*, tbltitulo.codvalecompraformapagamento e tblformapagamento.valecompra dropados. Desconto antigo virou valoravulso NEGATIVO (decisao 23): valorvale = produtos + avulso = credito do titulo em todos. Numeros antes=depois: face=credito 252.502,28, saldo -16.311,84, 176 cancelados, 4.031 titulos identicos campo a campo. Codigo: models Mg\ValeCompra removidos, flag valecompra fora da API/PDV/contas, DIMP e escopo sem o ramo do legado, negocioFechado recusa alterar negocio convertido, comprovante mostra o desconto. Relato completo em .claude/plano-vale-compras.md secao 6; bancada em api/storage/app/vale-teste/m9.php.

2026-09-26: resgate no wizard Receber simplificado a pedido — saiu o modo 'Pela escola' do FormaVale (fica so bipar o vale); vale pula o passo 2 (valor) e abre direto num passo com codigo (readonly se veio do VAL… no input de barras), valor a receber, saldo do vale, valor utilizado (editavel, teto = min(saldo, a receber)), saldo a pagar e saldo do vale depois. Endpoints/store de escopo (valeEscopo*, adicionarPagamentosVale) ficaram sem uso no front.
<!-- SECTION:NOTES:END -->

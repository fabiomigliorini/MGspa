---
id: TASK-38
title: Fazer venda de vale-compras
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-09-24 22:37'
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
Origem: negocios/todo — secao IMPORTANTES.

Converter a venda de Vale Compras do MGLara para o app negocios, mas NAO como tela
separada: a venda do vale tem que acontecer dentro da propria tela de negocio, junto
com a venda de material, reaproveitando a estrutura de tabelas que ja existe.

MOTIVO (efeito colateral a corrigir): o cliente compra material e um vale-compras na
mesma visita e quer passar o cartao uma vez so, principalmente quando parcela. Hoje
sao duas vendas em telas diferentes, entao cada uma passa o cartao separado e muitas
vezes nenhuma das duas atinge o valor minimo de parcela.

### Como funciona hoje no MGLara (/opt/www/MGLara)

Telas vale-compra e vale-compra-modelo, ValeCompraController, EscPrintValeCompra.

- tblvalecompramodelo = catalogo do kit: escola (codpessoafavorecido), turma, ano e a
  lista de produtos (tblvalecompramodeloprodutobarra).
- Venda: escolhe o modelo -> tblvalecompra (aluno, turma, desconto, total) +
  tblvalecompraprodutobarra (itens, quantidade ajustavel) + UMA forma de pagamento
  (tblvalecompraformapagamento).
- Financeiro no store() do controller:
  - se a forma nao for a vista, gera os titulos a receber parcelados
    (tipotitulo 240 Debito Cliente, conta contabil 82 Venda Vale, numero V00000000-1/N);
  - SEMPRE gera um titulo de CREDITO tipo 3 "Vale Compras" (conta contabil 83) em nome
    da ESCOLA (codpessoafavorecido), vencimento +1 ano, e grava o codtitulo no vale.
- Imprime o vale em matricial com escola, aluno, turma e a lista de produtos do kit.

Volume real (consulta em 2026-09-12): ~330 vales/ano; 2026 = 314 vales / R$ 41.091;
2025 = 338 / R$ 34.010. Ultimo vale emitido em 2026-07-27. Formas mais usadas em
2025/2026: Cartao (468), Dinheiro (83), PIX (70), Crediario (14), Boleto (9).
Favorecidos: Colegio San Petrus Sinop, Colegio Regina Pacis. Cliente quase sempre
Consumidor. Os creditos vao zerando ao longo do ano conforme o aluno retira o material
(saldo de 2022 pra tras = 0; 2026 ainda com -14.872 em aberto).

### O que JA existe no negocios / api (nao precisa refazer)

O RESGATE do vale ja esta pronto e em producao:

- components/offline/PagamentoVale.vue: bipa o codigo de barras VAL00000000 ou digita
  o numero, busca o titulo e usa como forma de pagamento 1030 (Vale).
- api PdvController@buscarVale: valida se o titulo e do tipo 3 (TituloService::TIPO_VALE).
- api PdvNegocioPrazoService::baixarVales()/estornarBaixaVales(): amortiza e estorna o
  titulo no fechamento/cancelamento do negocio.
- api Pdv/ValeService::pdf()/imprimir(): imprime vale em bobina 80mm com codigo de
  barras VAL + codtitulo (Ably -> impressora).

E a DEVOLUCAO ja EMITE vale exatamente pelo caminho que essa task precisa
(PdvNegocioDevolucaoService, final do metodo): cria negocio com natureza 2, forma de
pagamento 1030, e um titulo tipo 3 de credito com vencimento +1 ano, portador CARTEIRA,
numero N00000000-DEV. Falta so a VENDA do vale.

### Por que tem que ser um negocio so

Cada transacao de cartao (Lio, PagarMe, Saurus) fica amarrada a uma linha de
tblnegocioformapagamento, com indice unico por pedido (codliopedido, codpagarmepedido,
codsauruspedido). Uma maquininha nao se divide entre dois negocios. Entao "uma passada
de cartao" implica produtos e vale no MESMO negocio.

Isso derruba a ideia de resolver so com natureza de operacao: a natureza e por negocio
(tblnegocio.codnaturezaoperacao), nao por item — tblnegocioprodutobarra nao tem
natureza propria. Uma natureza "Venda de Vale Compras" resolveria estoque/fiscal/
financeiro de um negocio inteiro de vale, mas nao deixa misturar material e vale no
mesmo cupom, que e justamente o objetivo.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Vale-compras vendido dentro da tela de negocios, sem abrir o MGLara
- [ ] #2 Material e vale no mesmo negocio, com uma unica passada de cartao e parcelamento sobre o total (problema do valor minimo de parcela resolvido)
- [ ] #3 Venda do vale nao movimenta estoque
- [ ] #4 Fechamento gera o titulo de credito tipo 3 em nome do favorecido, com validade de 1 ano, resgatavel pelo fluxo de PagamentoVale.vue que ja existe
- [ ] #5 Vale impresso com escola, aluno, turma, lista do kit e codigo de barras VAL+codtitulo
- [ ] #6 Mais de um vale no mesmo negocio (dois filhos, dois kits) funciona
- [ ] #7 Cancelamento do negocio estorna o titulo do vale junto
- [ ] #8 Tratamento fiscal definido e implementado conforme a decisao 2 das notas
- [ ] #9 Telas de vale-compra do MGLara aposentadas, com destino definido tambem para o cadastro de modelos de kit (ver notas)
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
<!-- SECTION:NOTES:END -->

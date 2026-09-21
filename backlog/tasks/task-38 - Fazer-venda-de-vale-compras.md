---
id: TASK-38
title: Fazer venda de vale-compras
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-09-12 17:45'
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
LEVANTAMENTO FEITO EM 2026-09-12. TRES DECISOES EM ABERTO — decidir na hora de executar.

### Decisao 1 — como o vale entra no negocio

(a) VALE COMO ITEM PRODUTO-SERVICO (era a recomendacao do levantamento)
    Produto "VALE COMPRAS" com codtipoproduto = 9 (Servicos) e tblproduto.estoque = false.
    O job de estoque ja ignora os dois casos — ver MGLara
    app/Jobs/EstoqueGeraMovimentoNegocioProdutoBarra.php, que so gera movimento se
    NaturezaOperacao->estoque AND Produto->TipoProduto->estoque AND Produto->estoque.
    Entao vale como item nao mexe em estoque sem tocar em nada do motor.
    tblvalecompra continua existindo e ganha o vinculo com o item
    (codnegocioprodutobarra ou codnegocio), guardando escola/aluno/turma/modelo e a
    lista do kit pra impressao e conferencia no resgate. tblvalecompramodelo fica igual.
    No fechamento, gera o titulo tipo 3 igual a devolucao faz hoje.
    + Um negocio, um cartao, vende vale puro ou misturado.
    - Precisa resolver o fiscal (decisao 2) e excluir o produto-servico dos relatorios
      de venda e dos indicadores de RH (senao conta a venda duas vezes: na emissao e no
      resgate).

(b) NEGOCIO SEPARADO COM NATUREZA PROPRIA
    Natureza "Venda de Vale Compras" com estoque=false, financeiro=true, emitida=false.
    Fiel ao modelo atual e limpo fiscalmente, mas NAO resolve o cartao unico — continuam
    duas vendas, que e o motivo da task existir. So serve se a decisao for tratar o
    cartao unico de outro jeito.

(c) TROCO EM VALE
    Sem item: o cliente paga a mais e o excedente vira vale, como troco. Encaixa perfeito
    na NFCe (vPag - vTroco = vNF, ja suportado: PdvNegocioService::fechar soma
    valortotal - valortroco, e NotaFiscalNegocioService copia o troco pro pagamento da
    nota). Mas nao permite vender vale sozinho (o fechamento exige pelo menos um item) e
    perde a amarracao com escola/aluno/kit. Serviria no maximo como complemento de (a).

### Decisao 2 — tratamento fiscal (se valer a decisao 1a)

A NFCe nao e automatica: e sob demanda, por PdvController@notaFiscal ->
NotaFiscalNegocioService::gerarNotaFiscalDoNegocio, que hoje leva TODOS os itens do
negocio e copia os pagamentos 1:1.

(a) FORA DA NOTA, COMO TROCO — nota sai so com as mercadorias e o valor do vale vai como
    troco no pagamento (vPag - vTroco = vNF). O fato gerador do ICMS fica no resgate,
    como ja e hoje. Evita tributar duas vezes. Exige filtrar o item no
    NotaFiscalNegocioService e ajustar o bloco de pagamentos.
(b) DENTRO DA NOTA — zero mudanca no NotaFiscalNegocioService, mas tributa o vale na
    venda e de novo no resgate.
(c) BLOQUEAR NOTA no negocio que tem vale — simples, joga o problema pro balcao.

### Decisao 3 — escopo do vale

(a) So modelo de kit, como hoje: escolhe escola/serie no catalogo tblvalecompramodelo,
    carrega os itens, ajusta quantidade e desconto.
(b) Modelo de kit + vale de valor livre (vale-presente / credito avulso, sem escola nem
    lista de produtos).
(c) Kit primeiro, valor livre em outra task.

### Decisao 4 — destino do cadastro de modelos de kit

O catalogo de kits (tblvalecompramodelo + tblvalecompramodeloprodutobarra) so tem
manutencao no MGLara (telas vale-compra-modelo, ValeCompraModeloController). Um modelo
guarda escola (codpessoafavorecido), turma, ano e a lista de produtos com quantidade e
preco; e dele que a venda do vale carrega os itens.

Se a venda sair do MGLara, o cadastro nao pode ficar orfao no sistema velho. Opcoes:
converter pro negocios junto com a venda, mandar pro estoque/pessoas, ou deixar no MGLara
por enquanto. Sao poucos modelos e mexem uma vez por ano (inicio do ano letivo), entao
talvez nao valha a conversao imediata — mas precisa de decisao antes de aposentar a tela
de venda.

### Pontos de atencao levantados

- CONTABIL: hoje o vale separa as contas 82 (Venda Vale) e 83 (Credito Vale). Dentro de
  um negocio normal a receita cai em "Venda" (conta 2). Definir se isso importa pro
  gerencial — lembrando que pagamento a vista nao gera titulo, entao a conta contabil so
  aparece nas vendas a prazo.
- RH: PdvNegocioService::fechar dispara ProcessarVendaJob. Vale como item entraria nos
  indicadores de venda do vendedor na emissao E no resgate. Precisa excluir o produto do
  calculo.
- OFFLINE: a tela de negocio e offline-first (Dexie). Decidir se o catalogo de modelos de
  kit sincroniza pro offline ou se a emissao de vale exige estar online.
- IMPRESSAO: o vale so pode ser impresso depois do fechamento no servidor, porque o
  codigo de barras e o codtitulo. Mesmo comportamento do vale de devolucao hoje.
- FAVORECIDO: manter a escola como codpessoa do titulo de credito, como e hoje.
- Resolve a metade "Vale-compra" da TASK-84 (destinar Caixa e Vale-compra a um app):
  vale-compra fica no negocios.
<!-- SECTION:NOTES:END -->

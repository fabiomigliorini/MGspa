- Parametrização de Outras Saídas
- Tratamento de erro quando não tem tributação da natureza de operação

- no dashboard
  - Adicionar um grafico de autorizadas por Usuario Criacao.
  - criar um % de cancelado/inutilizado por usuario Criacao
  - criar os indices no banco de dados pra não pesar

- dar uma olhada em como amarrar o cclasstrib ao produto. talvez vai ter que refatorar todo motor de calculo da ibs/cbs
- limpar endpoints de nota-fiscal não utilizados
- se for produtor rural pode editar notas com ERR

- Não está buscando alguns produtos pelo código, especifocos, como as distintas colorações de EVA.
- Ao fazer o fechamento de um cliente com vários negócios, com os mesmos produtos, a listagem na nota fiscal fica com vários quadros do mesmo produto, devemos juntar esse produtos em um só slote da nota. (PAMELA)

Eduardo

- Tela Natureza Operacao
- Tela DFE
- Tela NFeTrceiro

DONE:

05/01

- nao esta salvando as observacoes
- nao esta trazendo as observacoes da natureza ao informar a natureza.
- editei um item (descricao alternativa) e nao atualizou pagina principal
- Deixar informar NFEChave quando emitida = false
- botao pra adicionar nova nota fiscal
- colocar botao nossa emissao
- Criar o botao do xml
- ratear os valores editaveis da nota fiscal (desconto, frete, seguro, outras)
- adicionar botao remover desconto do total (incorporar)
- carta de correcao nao esta funcionando
- quando zera quantidade esta zerando valor unitario
- fazer link pra tela de negocios a partir da nota
- desabilitar mgsis
- tirar a impressao automatica das NFCe
- fazer a "danfe" da carta de correcao
- nao esta alterando o status quando atribui numero pra notafiscal
- quando alera uma nota, volta pra lsitagem, ela nao atualiza o registro na listagem
- verificar se esta recalculando tributacao quando altera (natureza, cliente, filial)
- Ignorado (SEFAZ PRECISA HOMOLOGAR A INUTILIZACAO, PRECISA ESPERAR SAIR DO CSTAT 563)
- criar botao pra recalcular tributacao.
- publicar o notas.mgpapelaria.com.br
- nao pode alterar nota já enviada (com numero ativo)
- na tela de negocios, quando uma nf está inutilizada, ele mostra como se fosse não autorizada
- criar o "version": "0.15.2" no package.json e mostrar no cabeçalho

06/01

- quando abre pra editar uma forma de pagamento, clica em cancelar, depois abre de novo, o form fica zerado. verificar com com nota referenciada e duplicata se não esta acontecendo o mesmo
- Alinhamento de titles
- deixar a nota como padrão na abertura do app

07/01

- danfe no mobile fazer aquele esquema do mgsis ao inves de abrir a dialog
- colocar um link pro cadastro do produto ({MGLARA_URL}/produt- Tela de Tributacoeso/{codproduto})
- popups em geral estourando em celular

08/01

- Dashboard
- deixar responsivo (celular e tablet na vertical)
- colcoar natureza de operacao nas listagens
- deixar o padding dos totais la encima igual no vertical e no horizontal
- Mudar os labels pra 7 dias e Hoje.
- mostrar de alguma forma que são as ultimas 20 de cada lista
- quando filtra modelo (nfe/nfce) totais lá encima ficam zerados
- criar um dashboard com um resumo das notas com problema e as notas emitidas na inialização. totais por nat operacao, etc. assim fica mais facil de gerenciar os problemas.

09/01

- Trocar o grafico de % de Erro Por Filial para % de Canceladas/inutilizadas por Filial
- quando passar o mouse encima da imagem, ver se tem como mostrar ela cheia

12/01

- conferir se todos os subitens da nota, batem com o espelho (duplicatas, pagamento, referenciadas, carta de correcao)
- Na tela de nota, precisamos ter um link para o cadastro da pessoa.

13/01

- Pesquisa não aperece os produtos
- colocar link pra nfe de terceiro ({MGSIS_URL}/index.php?r=nfeTerceiro/view&id={codnfeterceiro}) -> \_blank
- Erro de endereço longo ao gerar notas.
- fazer botão devolução (venda e compra)
- fazer botao juntar notas

19/01

- Tela de CFOP
- Tela de Cidades

20/01

- Tela de Tributação

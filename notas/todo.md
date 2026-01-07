- no dashboard
  - deixar responsivo (celular e tablet na vertical)
  - colcoar natureza de operacao nas listagens
  - quando filtra modelo (nfe/nfce) totais lá encima ficam zerados
  - deixar o padding dos totais la encima igual no vertical e no horizontal
  - Trocar o grafico de % de Erro Por Filial para % de Canceladas/Inutilizadas por Filial
  - Adicionar um grafico de autorizadas por Usuario Alteração.
  - criar um % de cancelado/inutilizado por usuario Criacao
  - Mudar os labels pra 7 dias e Hoje.
  - criar os indices no banco de dados pra não pesar
  - mostrar de alguma forma que são as ultimas 20 de cada lista
- quando passar o mouse encima da imagem, ver se tem como mostrar ela cheia
- colocar link pra nfe de terceiro ({MGSIS_URL}/index.php?r=nfeTerceiro/view&id={codnfeterceiro}) -> \_blank
- criar um dashboard com um resumo das notas com problema e as notas emitidas na inialização. totais por nat operacao, etc. assim fica mais facil de gerenciar os problemas.
- fazer botão devolução (venda e compra)
- fazer botao juntar notas
- dar uma olhada em como amarrar o cclasstrib ao produto. talvez vai ter que refatorar todo motor de calculo da ibs/cbs
- limpar endpoints de nota-fiscal não utilizados
- se for produtor rural pode editar notas com ERR
- colocar link pra nfe de terceiro
- conferir se todos os subitens da nota, batem com o espelho (duplicatas, pagamento, referenciadas, carta de correcao)

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
- colocar um link pro cadastro do produto ({MGLARA_URL}/produto/{codproduto})
- popups em geral estourando em celular

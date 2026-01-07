- popups em geral estourando em celular
- colocar um link pro cadastro do produto
- danfe no mobile fazer aquele esquema do mgsis ao inves de abrir a dialog (protected/components/MGNotaFiscalBotoes/\_javascript.php | NFePHPDanfe)

      var xhr = new XMLHttpRequest();
      xhr.open('GET', "<?php echo MGSPA_NFEPHP_URL; ?>" + codnotafiscal + "/danfe", true);
      xhr.responseType = 'blob';
      xhr.onload = function(e) {
       if (this['status'] == 200) {
         var blob = new Blob([this['response']], {type: 'application/pdf'});
         var url = URL.createObjectURL(blob);
         var printWindow = window.open(url);
      	 if (recarregar) {
      		 location.reload();
      	 }
       }
      };
      xhr.send();

- criar um dashboard com um resumo das notas com problema e as notas emitidas na inialização. totais por nat operacao, etc. assim fica mais facil de gerenciar os problemas.
- fazer botão devolução (venda e compra)
- fazer botao juntar notas
- dar uma olhada em como amarrar o cclasstrib ao produto. talvez vai ter que refatorar todo motor de calculo da ibs/cbs
- quando passar o mouse encima da imagem, ver se tem como mostrar ela cheia
- limpar endpoints de nota-fiscal não utilizados
- se for produtor rural pode editar notas com ERR
- colocar link pra nfe de terceiro

DONE:

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

- 06/01
- quando abre pra editar uma forma de pagamento, clica em cancelar, depois abre de novo, o form fica zerado. verificar com com nota referenciada e duplicata se não esta acontecendo o mesmo
- Alinhamento de titles
- deixar a nota como padrão na abertura do app

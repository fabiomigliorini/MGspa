update tblnotafiscal 
set observacoes = observacoes || '  - ICMS Recolhido antecipadamente por estimativa simplificada / carga media - Mercadoria entregue ao destinatario dentro do territorio de Mato Grosso' 
where codnotafiscal = 1307558;

update tblnotafiscalprodutobarra
set icmscst = 060
where icmscst = 090 
and codnotafiscal = 1307558;

/*
Inutilização	151170223656680 - 24/08/2017 17:35:39
Justificativa	modelo incorreto, deve ser gerada nfe

update tblnotafiscal
set nfeinutilizacao = '151170230401063'
, nfedatainutilizacao = '31/08/2017 17:36'
where codnotafiscal = 613341
*/

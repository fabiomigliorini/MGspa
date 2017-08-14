update tblnotafiscal 
set observacoes = observacoes || '  - ICMS Recolhido antecipadamente por estimativa simplificada / carga media - Mercadoria entregue ao destinatario dentro do territorio de Mato Grosso' 
where codnotafiscal = 596882

update tblnotafiscalprodutobarra
set icmscst = 060
where codnotafiscal = 596882
and icmscst = 090 

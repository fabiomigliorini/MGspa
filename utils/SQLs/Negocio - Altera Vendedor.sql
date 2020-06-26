update tblnegocio 
set codpessoavendedor = 
	(select p.codpessoa 
	from tblpessoa p 
	where p.vendedor = true 
	and p.pessoa ilike '%elidiane%mol%') 
where codnegocio = 01918035
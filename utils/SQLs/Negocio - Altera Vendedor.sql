update tblnegocio 
set codpessoavendedor = 
	(select p.codpessoa 
	from tblpessoa p 
	where p.vendedor = true 
	and p.pessoa ilike '%iara%') 
where codnegocio = 1864162

update tblnegocio 
set codpessoavendedor = 
	(select p.codpessoa 
	from tblpessoa p 
	where p.vendedor = true
	and inativo is null
	and p.pessoa ilike '%Jaqueline%') 
where codnegocio = 2281776


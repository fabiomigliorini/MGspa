
update tblnegocio 
set codpessoavendedor = 
	(select p.codpessoa 
	from tblpessoa p 
	where p.vendedor = true
	and inativo is null
	and p.pessoa ilike '%jaqueline%') 
where codnegocio = 02281693


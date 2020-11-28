
update tblnegocio 
set codpessoavendedor = 
	(select p.codpessoa 
	from tblpessoa p 
	where p.vendedor = true 
	and p.pessoa ilike '%elidiane%mol%') 
where codnegocio = 2064654



update tblnegocio 
set codpessoa =1 
where codnegocio = 2025217

update tblnegocio set codnegociostatus = 1 where codnegocio = 2030859


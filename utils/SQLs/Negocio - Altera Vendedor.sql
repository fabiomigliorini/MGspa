
update tblnegocio 
set codpessoavendedor = 
	(select p.codpessoa 
	from tblpessoa p 
	where p.vendedor = true 
	and p.pessoa ilike '%nelsa%') 
where codnegocio = 02088447



update tblnegocio 
set codpessoa =1 
where codnegocio = 2025217

update tblnegocio set codnegociostatus = 1 where codnegocio = 2030859


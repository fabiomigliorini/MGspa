--alter table tblprodutovariacao add vendainicio date;

create temporary table tmpvendainicio as 
select pb.codprodutovariacao, min(n.lancamento) as vendainicio
from tblnegocio n
inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
where n.codnegociostatus = 2
--and pb.codproduto = 21
group by pb.codprodutovariacao;

update tmpvendainicio
set vendainicio = '2012-01-01'::date
where vendainicio < '2012-01-01'::date;

update tblprodutovariacao 
set vendainicio = tmpvendainicio.vendainicio
from tmpvendainicio
where tblprodutovariacao.codprodutovariacao = tmpvendainicio.codprodutovariacao
--and tblprodutovariacao = 21
;

update tblprodutovariacao 
set vendainicio = now()
where tblprodutovariacao.codprodutovariacao not in (select codprodutovariacao from tmpvendainicio)
--and tblprodutovariacao = 21
;

drop table if exists tmpvendainicio;

select min(vendainicio), max(vendainicio) from tblprodutovariacao;

--select * from tblprodutovariacao where vendainicio = '1899-12-20'

/*

select * from tblestoquelocalprodutovariacao limit 50

select * from tblprodutovariacao limit 50

select * from tblproduto limit 50

select distinct estoqueminimo from tblproduto

alter table tblproduto drop column vendaultimocalculo



select * from tblprodutovariacao where codprodutovariacao = 20
*/
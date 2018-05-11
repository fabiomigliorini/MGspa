-- NAO CONFERIDOS
select 
	el.sigla
	, p.codproduto
	, p.produto
	, pv.variacao
	, es.saldoquantidade
	, cast(es.dataentrada as date) entrada
	, cast(es.ultimaconferencia as date) conferencia
	, (
		select string_agg(barras, ', ')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		where pb.codprodutovariacao = pv.codprodutovariacao
		group by codprodutovariacao
	) as barras
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao) 
inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
left join tblsubgrupoproduto sgp on (sgp.codsubgrupoproduto = p.codsubgrupoproduto)
left join tblgrupoproduto gp on (gp.codgrupoproduto = sgp.codgrupoproduto)
left join tblfamiliaproduto fp on (fp.codfamiliaproduto = gp.codfamiliaproduto)
where es.saldoquantidade != 0
and (ultimaconferencia < '2017-09-20' or ultimaconferencia is null)
and coalesce(pv.codmarca, p.codmarca) in (select m.codmarca from tblmarca m where m.marca ilike '%link%' or m.marca ilike '%plasmel%') 
--and fp.codsecaoproduto = 11
order by el.estoquelocal, p.produto, pv.variacao


-- CONFERENCIA FEITA MAIS DE UMA VEZ
/*
select 'http://192.168.1.205/MGLara/estoque-saldo/' || cast(esc.codestoquesaldo as varchar), count(*)
from tblestoquesaldoconferencia esc
inner join tblestoquesaldo es on (es.codestoquesaldo = esc.codestoquesaldo)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = es.codestoquelocalprodutovariacao)
where elpv.codestoquelocal = 101001
and date_trunc('day', esc.criacao) >= '2017-07-26'
group by esc.codestoquesaldo 
having count(*) > 1 
order by 2 desc
*/

-- CONFERENCIA ZERADA
/*
select 'http://192.168.1.205/MGLara/estoque-saldo/' || cast(esc.codestoquesaldo as varchar)
from tblestoquesaldoconferencia esc
inner join tblestoquesaldo es on (es.codestoquesaldo = esc.codestoquesaldo)
inner join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocalprodutovariacao = es.codestoquelocalprodutovariacao)
where elpv.codestoquelocal = 101001
and date_trunc('day', esc.criacao) >= '2017-07-26'
and esc.quantidadeinformada = 0
*/


-- TROCA SUBGRUPO PELO NEGOCIO
/*
update tblproduto 
set codsubgrupoproduto = 3108
where codproduto in (
	select pb.codproduto
	from tblnegocioprodutobarra npb
	inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
	where npb.codnegocio = 815513
	)
*/

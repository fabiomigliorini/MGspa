
select 
	p.codproduto
	, p.inativo
	, p.produto
	--, p.referencia
	, pv.variacao
	, coalesce(pv.referencia, p.referencia)
	, (select array((
		select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pb.codprodutovariacao = pv.codprodutovariacao
		order by pe.quantidade nulls first, pb.barras
	))) as barras
	, (select array((
		select coalesce(um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutoembalagem pe 
		left join tblunidademedida um on (um.codunidademedida = pe.codunidademedida)
		where pe.codproduto = p.codproduto
		order by pe.quantidade nulls first
	)))  as embalagens
	, pv.codprodutovariacao
from tblproduto p
left join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
where p.produto ilike '%dac%'
--and p.produto ilike '%pasta%'
and p.produto ilike '%catalogo%'
order by p.produto, pv.variacao, pv.codprodutovariacao
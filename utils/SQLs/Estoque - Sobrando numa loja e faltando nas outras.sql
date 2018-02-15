select 
	p.codproduto, 
	p.produto,
	pv.variacao,
	(
		select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pb.codprodutovariacao = pv.codprodutovariacao
		order by pe.quantidade nulls first, pb.barras
		limit 1
	) as barras,	
	cast(es_c.saldoquantidade as int) as cen,
	cast(elpv_c.estoquemaximo as int) as max,
	cast(es_d.saldoquantidade as int) as dep,
	cast(es_b.saldoquantidade as int) as bot,
	cast(es_i.saldoquantidade as int) as imp
	--,
	--*
from tblproduto p
inner join tblmarca m on (m.codmarca = p.codmarca)
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join tblestoquelocalprodutovariacao elpv_d on (elpv_d.codprodutovariacao = pv.codprodutovariacao and elpv_d.codestoquelocal = 101001)
left join tblestoquesaldo es_d on (es_d.codestoquelocalprodutovariacao = elpv_d.codestoquelocalprodutovariacao and es_d.fiscal = false) 
left join tblestoquelocalprodutovariacao elpv_b on (elpv_b.codprodutovariacao = pv.codprodutovariacao and elpv_b.codestoquelocal = 102001)
left join tblestoquesaldo es_b on (es_b.codestoquelocalprodutovariacao = elpv_b.codestoquelocalprodutovariacao and es_b.fiscal = false) 
left join tblestoquelocalprodutovariacao elpv_c on (elpv_c.codprodutovariacao = pv.codprodutovariacao and elpv_c.codestoquelocal = 103001)
left join tblestoquesaldo es_c on (es_c.codestoquelocalprodutovariacao = elpv_c.codestoquelocalprodutovariacao and es_c.fiscal = false) 
left join tblestoquelocalprodutovariacao elpv_i on (elpv_i.codprodutovariacao = pv.codprodutovariacao and elpv_i.codestoquelocal = 104001)
left join tblestoquesaldo es_i on (es_i.codestoquelocalprodutovariacao = elpv_i.codestoquelocalprodutovariacao and es_i.fiscal = false) 
where m.controlada = true
and coalesce(es_c.saldoquantidade , 0) > 0
and coalesce(es_c.saldoquantidade, 0) >= (coalesce(elpv_c.estoquemaximo, 1) * 2)
and m.marca ilike 'acrilex'
/*
and (
	coalesce(es_b.saldoquantidade, 0) < coalesce(elpv_b.estoqueminimo, 1)
	or coalesce(es_i.saldoquantidade, 0) < coalesce(elpv_i.estoqueminimo, 1)
)
and coalesce(es_d.saldoquantidade, 0) = 0
*/
order by m.marca, p.produto

--select
select 
	p.codproduto as "#"
	--, pv.codprodutovariacao
	, p.produto || coalesce(' | ' || pv.variacao, '') as produto
	,(
		select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pb.codprodutovariacao = pv.codprodutovariacao
		order by pe.quantidade nulls first, pb.barras
		limit 1
	) as barras
	, round(es.saldoquantidade) as origem
	--, round(es_dep.saldoquantidade) as saldo_deposito
	, round(es_dest.saldoquantidade) as destino
	--, elpv.estoquemaximo as max_origem
	--, elpv_dest.estoquemaximo as max_destino
	, round(
			(
				(coalesce(es.saldoquantidade, 0) + coalesce(es_dest.saldoquantidade, 0)) -- estoque total
				* cast(coalesce(elpv_dest.estoquemaximo, 1.0) / (coalesce(elpv.estoquemaximo, 1.0) + coalesce(elpv_dest.estoquemaximo, 1.0)) as float) -- proporcao estoque destino pelo maximo
			) - coalesce(es_dest.saldoquantidade, 0)
		) as transferir
from tblproduto p
inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = 103001 and elpv.codprodutovariacao = pv.codprodutovariacao)
left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
left join tblestoquelocalprodutovariacao elpv_dest on (elpv_dest.codestoquelocal = 102001 and elpv_dest.codprodutovariacao = pv.codprodutovariacao)
left join tblestoquesaldo es_dest on (es_dest.codestoquelocalprodutovariacao = elpv_dest.codestoquelocalprodutovariacao and es_dest.fiscal = false)
left join tblestoquelocalprodutovariacao elpv_dep on (elpv_dep.codestoquelocal = 101001 and elpv_dep.codprodutovariacao = pv.codprodutovariacao)
left join tblestoquesaldo es_dep on (es_dep.codestoquelocalprodutovariacao = elpv_dep.codestoquelocalprodutovariacao and es_dep.fiscal = false)
where p.produto ilike '%gitex%'
and (
	(
		es.saldoquantidade > coalesce(elpv.estoquemaximo, 0) -- Sobrando na Origem
		and coalesce(es_dest.saldoquantidade, 0) < coalesce(elpv_dest.estoqueminimo, 1) -- Faltando no Destino
	)
	OR
	(
		es.saldoquantidade > 1 -- Tem mais de 1 na origem
		and coalesce(es_dest.saldoquantidade, 0) = 0 -- Nenhum no destino
	)
)
and coalesce(es_dep.saldoquantidade, 0) < 1
order by p.produto, pv.variacao
--limit 100
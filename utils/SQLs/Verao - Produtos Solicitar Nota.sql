with nota as (
	select 
		elpv.codestoquelocalprodutovariacao,
		p.produto,
		pv.variacao,		
		p.codproduto || coalesce(' ' || pv.codprodutovariacao, '') as produto,
		(select pb.codprodutobarra from tblprodutobarra pb where pb.codprodutovariacao = pv.codprodutovariacao and pb.codprodutoembalagem is null order by (case when barras ilike '234%' then 1 else 0 end), barras limit 1) as codprodutobarra,
		fiscal.saldoquantidade as saldofiscal, 
		fisico.saldoquantidade as saldofisico, 
		case when (fisico.saldoquantidade > 0) then coalesce(fisico.saldoquantidade, 0) else 0 end - coalesce(fiscal.saldoquantidade, 0) as saldocobrir,
		round(p.preco * 0.5, 2) as valorunitario
	from tblestoquelocalprodutovariacao elpv
	inner join tblestoquesaldo fiscal on (fiscal.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and fiscal.fiscal = true)
	left join tblestoquesaldo fisico on (fisico.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and fisico.fiscal = false)
	inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
	inner join tblproduto p on (p.codproduto = pv.codproduto)
	where elpv.codestoquelocal  = :codestoquelocaldest
	--and p.codmarca != :codmarcaignorar
	and fiscal.saldoquantidade < 0
)
select nota.*, saldocobrir * valorunitario as valortotal, pb.barras
from nota
inner join tblprodutobarra pb on (pb.codprodutobarra = nota.codprodutobarra)
order by nota.produto, barras

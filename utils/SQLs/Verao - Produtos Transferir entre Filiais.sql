with transf as (
	with dest as (
		select 
			elpv.codestoquelocalprodutovariacao,
			pv.codprodutovariacao,
			pv.codproduto,
			(select min(pb.codprodutobarra) from tblprodutobarra pb where pb.codprodutovariacao = pv.codprodutovariacao and pb.codprodutoembalagem is null) as codprodutobarra,
			fiscal.saldoquantidade as saldofiscal, 
			fisico.saldoquantidade as saldofisico, 
			case when (fisico.saldoquantidade > 0) then coalesce(fisico.saldoquantidade, 0) else 0 end - coalesce(fiscal.saldoquantidade, 0) as saldocobrir,
			round(p.preco * 0.7, 2) as valorunitario
		from tblestoquelocalprodutovariacao elpv
		inner join tblestoquesaldo fiscal on (fiscal.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and fiscal.fiscal = true)
		left join tblestoquesaldo fisico on (fisico.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and fisico.fiscal = false)
		inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
		inner join tblproduto p on (p.codproduto = pv.codproduto)
		where elpv.codestoquelocal  = :codestoquelocaldest
		and p.codmarca != :codmarcaignorar
		and fiscal.saldoquantidade < 0
	), orig as (
		select 
			elpv.codestoquelocalprodutovariacao,
			elpv.codprodutovariacao,
			fiscal.saldoquantidade as saldofiscal, 
			fisico.saldoquantidade as saldofisico, 
			coalesce(fiscal.saldoquantidade, 0) - coalesce(fisico.saldoquantidade, 0) as saldosobrando
		from tblestoquelocalprodutovariacao elpv
		inner join tblestoquesaldo fiscal on (fiscal.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and fiscal.fiscal = true)
		left join tblestoquesaldo fisico on (fisico.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and fisico.fiscal = false)
		where elpv.codestoquelocal  = :codestoquelocalorig
		and fiscal.saldoquantidade > 0
		and coalesce(fiscal.saldoquantidade, 0) - coalesce(fisico.saldoquantidade, 0) > 0
		and elpv.codprodutovariacao in (39954, 39951, 39953)
	)
	select 
		dest.codproduto, 
		dest.codprodutovariacao, 
		dest.codprodutobarra,
		dest.valorunitario,
		dest.saldocobrir, 
		orig.saldosobrando, 
		least(dest.saldocobrir, orig.saldosobrando) as transferir
	from dest 
	inner join orig on (orig.codprodutovariacao = dest.codprodutovariacao)
	where dest.codprodutobarra is not null
)
insert into tblnotafiscalprodutobarra (
	codnotafiscal, 
	codprodutobarra, 
	codcfop, 
	descricaoalternativa, 
	quantidade, 
	valorunitario, 
	valortotal, 
	icmsbase, 
	icmspercentual, 
	icmsvalor, 
	ipibase, 
	ipipercentual, 
	ipivalor, 
	icmsstbase, 
	icmsstpercentual, 
	icmsstvalor, 
	csosn, 
	codnegocioprodutobarra, 
	alteracao, 
	codusuarioalteracao, 
	criacao, 
	codusuariocriacao, 
	icmscst, 
	ipicst, 
	piscst, 
	pisbase, 
	pispercentual, 
	pisvalor, 
	cofinscst, 
	cofinsbase, 
	cofinsvalor, 
	csllbase, 
	csllpercentual, 
	csllvalor, 
	irpjbase, 
	irpjpercentual, 
	irpjvalor, 
	cofinspercentual, 
	codnotafiscalprodutobarraorigem, 
	pedido, 
	pedidoitem, 
	certidaosefazmt, 
	fethabkg, 
	fethabvalor, 
	iagrokg, 
	iagrovalor, 
	funruralpercentual, 
	funruralvalor, 
	senarpercentual, 
	senarvalor, 
	observacoes, 
	icmsbasepercentual, 
	valorfrete, 
	valorseguro, 
	valordesconto, 
	valoroutras
)
select 
	:codnotafiscal, 
	codprodutobarra, 
	5152 as codcfop, 
	null descricaoalternativa, 
	transferir as quantidade, 
	valorunitario, 
	transferir * valorunitario as valortotal, 
	null as icmsbase, 
	null as icmspercentual, 
	null as icmsvalor, 
	null as ipibase, 
	null as ipipercentual, 
	null as ipivalor, 
	null as icmsstbase, 
	null as icmsstpercentual, 
	null as icmsstvalor, 
	null as csosn, 
	null as codnegocioprodutobarra, 
	null as alteracao, 
	null as codusuarioalteracao, 
	null as criacao, 
	null as codusuariocriacao, 
	null as icmscst, 
	null as ipicst, 
	null as piscst, 
	null as pisbase, 
	null as pispercentual, 
	null as pisvalor, 
	null as cofinscst, 
	null as cofinsbase, 
	null as cofinsvalor, 
	null as csllbase, 
	null as csllpercentual, 
	null as csllvalor, 
	null as irpjbase, 
	null as irpjpercentual, 
	null as irpjvalor, 
	null as cofinspercentual, 
	null as codnotafiscalprodutobarraorigem, 
	null as pedido, 
	null as pedidoitem, 
	null as certidaosefazmt, 
	null as fethabkg, 
	null as fethabvalor, 
	null as iagrokg, 
	null as iagrovalor, 
	null as funruralpercentual, 
	null as funruralvalor, 
	null as senarpercentual, 
	null as senarvalor, 
	null as observacoes, 
	null as icmsbasepercentual, 
	null as valorfrete, 
	null as valorseguro, 
	null as valordesconto, 
	null as valoroutras
from transf
where transf.transferir > 0
--limit 1

with prods as (
	select n.ncm, p.* 
	from tblproduto p
	inner join tblncm n on (n.codncm = p.codncm)
	where n.ncm ILIKE '6301%' OR 
	n.ncm ILIKE '6302%' OR 
	n.ncm ILIKE '5007%' OR 
	n.ncm ILIKE '5111%' OR 
	n.ncm ILIKE '5112%' OR 
	n.ncm ILIKE '5113%' OR 
	n.ncm ILIKE '5208%' OR 
	n.ncm ILIKE '5209%' OR 
	n.ncm ILIKE '5210%' OR 
	n.ncm ILIKE '5211%' OR 
	n.ncm ILIKE '5212%' OR 
	n.ncm ILIKE '5309%' OR 
	n.ncm ILIKE '5310%' OR 
	n.ncm ILIKE '5311%' OR 
	n.ncm ILIKE '5407%' OR 
	n.ncm ILIKE '5408%' OR 
	n.ncm ILIKE '5512%' OR 
	n.ncm ILIKE '5513%' OR 
	n.ncm ILIKE '5514%' OR 
	n.ncm ILIKE '5515%' OR 
	n.ncm ILIKE '5516%' OR 
	n.ncm ILIKE '5603%' OR 
	n.ncm ILIKE '5801%' OR 
	n.ncm ILIKE '5802%' OR 
	n.ncm ILIKE '5803%' OR 
	n.ncm ILIKE '5804%' OR 
	n.ncm ILIKE '5805%' OR 
	n.ncm ILIKE '5806%' OR 
	n.ncm ILIKE '5809%' OR 
	n.ncm ILIKE '5811%' OR 
	n.ncm ILIKE '6001%' OR 
	n.ncm ILIKE '6002%' OR 
	n.ncm ILIKE '6003%' OR 
	n.ncm ILIKE '6004%' OR 
	n.ncm ILIKE '6005%' OR 
	n.ncm ILIKE '6006%' OR 
	n.ncm ILIKE '4203%' OR 
	n.ncm ILIKE '4303%' OR 
	n.ncm ILIKE '6101%' OR 
	n.ncm ILIKE '6102%' OR 
	n.ncm ILIKE '6104%' OR 
	n.ncm ILIKE '6105%' OR 
	n.ncm ILIKE '6106%' OR 
	n.ncm ILIKE '6107%' OR 
	n.ncm ILIKE '6108%' OR 
	n.ncm ILIKE '6109%' OR 
	n.ncm ILIKE '6110%' OR 
	n.ncm ILIKE '6111%' OR 
	n.ncm ILIKE '6112%' OR 
	n.ncm ILIKE '6113%' OR 
	n.ncm ILIKE '6114%' OR 
	n.ncm ILIKE '6115%' OR 
	n.ncm ILIKE '6116%' OR 
	n.ncm ILIKE '6117%' OR 
	n.ncm ILIKE '6201%' OR 
	n.ncm ILIKE '6202%' OR 
	n.ncm ILIKE '6203%' OR 
	n.ncm ILIKE '6204%' OR 
	n.ncm ILIKE '6205%' OR 
	n.ncm ILIKE '6206%' OR 
	n.ncm ILIKE '6207%' OR 
	n.ncm ILIKE '6208%' OR 
	n.ncm ILIKE '6209%' OR 
	n.ncm ILIKE '6210%' OR 
	n.ncm ILIKE '6211%' OR 
	n.ncm ILIKE '6212%' OR 
	n.ncm ILIKE '6213%' OR 
	n.ncm ILIKE '6214%' OR 
	n.ncm ILIKE '6215%' OR 
	n.ncm ILIKE '6216%' OR 
	n.ncm ILIKE '6217%' OR 
	n.ncm ILIKE '6401%' OR 
	n.ncm ILIKE '6402%' OR 
	n.ncm ILIKE '6403%' OR 
	n.ncm ILIKE '6404%' OR 
	n.ncm ILIKE '6405%'
	--order by p.produto
) 
select 
	sum(nfpb.valortotal) as venda, 
	sum(nfpb.icmsbase) as base_icms, 
--	sum(nfpb.icmspercentual) as aliq, 
	sum(nfpb.icmsvalor) as valor_icms,
	count(nfpb.codnotafiscalprodutobarra) as quantidade_de_vendas
from prods p
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto)
inner join tblnotafiscalprodutobarra nfpb on (nfpb.codprodutobarra = pb.codprodutobarra)
inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal)
where nf.emissao >= (now() - '1 year'::interval)
and nf.nfeinutilizacao is null
and nf.nfecancelamento is null
and nf.nfeautorizacao is not null 
and nf.codnaturezaoperacao in (1, 5)


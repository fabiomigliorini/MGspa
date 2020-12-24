
with itens as (
	select 
		nfpb.codnotafiscalprodutobarra, 
		nfpb.icmscst as icmscst_nota, 
		tno.icmscst as icmscst_correto, 
		nfpb.codcfop as codcfop_nota, 
		tno.codcfop as codcfop_correto
	from tblnotafiscal nf
	inner join tblnotafiscalprodutobarra nfpb on (nf.codnotafiscal = nfpb.codnotafiscal) 
	inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
	inner join tblproduto p on (p.codproduto = pb.codproduto)
	inner join tblncm ncm on (ncm.codncm = p.codncm)
	left join tbltributacaonaturezaoperacao tno on (
		tno.codestado is not null 
		and tno.codnaturezaoperacao = nf.codnaturezaoperacao 
		and tno.codtributacao = p.codtributacao 
		and p.codtipoproduto = tno.codtipoproduto 
		and tno.bit = ncm.bit
	)
	where nf.codnotafiscal = 1800370
)
select *, 
	'UPDATE tblnotafiscalprodutobarra SET codcfop = ' || codcfop_correto || ', icmscst = ' || icmscst_correto || ' WHERE codnotafiscalprodutobarra = ' || itens.codnotafiscalprodutobarra || ';'
from itens
where itens.codcfop_nota != itens.codcfop_correto;

UPDATE tblnotafiscalprodutobarra SET codcfop = 1409, icmscst = 60 WHERE codnotafiscalprodutobarra = 7125589;
UPDATE tblnotafiscalprodutobarra SET codcfop = 1409, icmscst = 60 WHERE codnotafiscalprodutobarra = 7125601;
UPDATE tblnotafiscalprodutobarra SET codcfop = 1409, icmscst = 60 WHERE codnotafiscalprodutobarra = 7125602;

update tblnotafiscalprodutobarra set icmscst = 10  where codnotafiscal = 1803459
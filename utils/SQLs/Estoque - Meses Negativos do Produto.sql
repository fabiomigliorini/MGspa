
select * 
from tblnegocioprodutobarra npb 
where npb.codprodutobarra in (select pb.codprodutobarra from tblprodutobarra pb where pb.codproduto = 39989) 
and npb.valorunitario < 5
and npb.codprodutobarra != 989646

select * 
from tblnotafiscalprodutobarra nfpb
where nfpb.codprodutobarra in (select pb.codprodutobarra from tblprodutobarra pb where pb.codproduto = 39989) 
and nfpb.valorunitario < 5
and nfpb.codprodutobarra != 989646


update tblnegocioprodutobarra npb 
set codprodutobarra = 989646
where npb.codprodutobarra in (select pb.codprodutobarra from tblprodutobarra pb where pb.codproduto = 39989) 
and npb.valorunitario < 5
and npb.codprodutobarra != 989646


update tblnotafiscalprodutobarra npb 
set codprodutobarra = 989646
where npb.codprodutobarra in (select pb.codprodutobarra from tblprodutobarra pb where pb.codproduto = 39989) 
and npb.valorunitario < 5
and npb.codprodutobarra != 989646


select em.saldoquantidade, em.*
from tblprodutovariacao pv
inner join tblestoquelocalprodutovariacao elpv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
where pv.codproduto = 45117
and em.saldoquantidade < 0
order by mes


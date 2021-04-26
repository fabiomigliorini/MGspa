
-- Meses negativos do produto
select em.codestoquemes, es.fiscal, elpv.codestoquelocal, pv.variacao, em.mes, em.saldoquantidade, em.*
from tblprodutovariacao pv
inner join tblestoquelocalprodutovariacao elpv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
where pv.codproduto = 308426 -- :codproduto 
and em.saldoquantidade < 0
order by fiscal, elpv.codestoquelocal, pv.variacao, mes

-- Cria Ajuste para cada mes negativo zerando produto
insert into tblestoquemovimento (codestoquemes, manual, data, codestoquemovimentotipo, entradaquantidade, entradavalor, criacao, codusuariocriacao, alteracao, codusuarioalteracao)
select em.codestoquemes, true, date_trunc('month', em.mes) + interval '1 month' - interval '1 second', 1002, LEAST(coalesce(em.saidaquantidade, 0) - coalesce(em.entradaquantidade, 0), em.saldoquantidade * -1), LEAST(coalesce(em.saidaquantidade, 0) - coalesce(em.entradaquantidade, 0), em.saldoquantidade * -1) * :custo, date_trunc('second', now()) , 1, date_trunc('second', now()) , 1
from tblprodutovariacao pv
inner join tblestoquelocalprodutovariacao elpv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
inner join tblestoquemes em on (em.codestoquesaldo = es.codestoquesaldo)
where pv.codproduto = :codproduto
and (em.saldoquantidade - coalesce(em.inicialquantidade, 0)) < 0
and (coalesce(em.saidaquantidade, 0) - coalesce(em.entradaquantidade, 0)) > 0
--and em.codestoquemes  = 2283524
order by fiscal, elpv.codestoquelocal, pv.variacao, mes

-- Manda recalcular mes onde criou movimento pra zerar negativo
select 'wget https://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/' || codestoquemes
from tblestoquemovimento em
where em.codusuariocriacao = 1
and em.criacao >= '2021-04-21 11:19'
order by codestoquemovimento 

update tblestoquemovimento 
set manual = true
where codusuariocriacao = 1
and criacao >= '2021-04-21 10:54'


select * from tblestoquemovimento where codestoquemes = 2283621


/*

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

*/
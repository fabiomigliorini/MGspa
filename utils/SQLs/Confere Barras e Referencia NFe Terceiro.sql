-- codigo do pacote cadastrado
/*
select * 
from tblnfeterceiroitem i
inner join tblprodutobarra pb on (pb.codprodutobarra = i.codprodutobarra)
where codnfeterceiro = 16609
and pb.barras != i.ceantrib

select * 
from tblnfeterceiroitem i
inner join (
    select pb.codprodutobarra, pb_un.barras
    from tblprodutobarra pb 
    inner join tblprodutobarra pb_un on (pb_un.codprodutovariacao = pb.codprodutovariacao and pb_un.codprodutoembalagem is null)
    --where pb.codprodutobarra = 62979
    ) iq_barra on (iq_barra.codprodutobarra = i.codprodutobarra)
where codnfeterceiro = 16609
and iq_barra.barras != i.cean
*/


/**

CREDEAL

CODIGO BARRA DA NOTA É DE UNIDADE
PACOTE = 2 + CODIGO UNIDADE
CAIXA = 1 + CODIGO DA UNIDADE

*/
select 
    p.codproduto,
    --i.cprod, 
    coalesce(pv.referencia, p.referencia) as referencia,
    p.produto,
    --xprod,
    p.preco,
    iq_barra_un.barras as unidade,
    (
        select pb_pt.barras
        from tblprodutobarra pb_pt
        where pb_pt.codprodutovariacao = pb.codprodutovariacao
        and pb_pt.barras not in (pb.barras, iq_barra_un.barras)
        limit 1
    ) as pacote,
    (
        select cast(pe_pt.quantidade as int)
        from tblprodutobarra pb_pt
        inner join tblprodutoembalagem pe_pt on (pe_pt.codprodutoembalagem = pb_pt.codprodutoembalagem)
        where pb_pt.codprodutovariacao = pb.codprodutovariacao
        and pb_pt.barras not in (pb.barras, iq_barra_un.barras)
        limit 1
    ) as pacote,
    pb.barras as caixa,
    cast(i.qcom as bigint) as compra,
    cast(pe.quantidade as bigint)  as caixa, 
    cast(i.qcom * pe.quantidade as bigint)  as total
from tblnfeterceiroitem i
inner join tblprodutobarra pb on (i.codprodutobarra = pb.codprodutobarra)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = pb.codprodutovariacao)
left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join (
    select pb2.codprodutobarra, pb_un.barras
    from tblprodutobarra pb2
    inner join tblprodutobarra pb_un on (pb_un.codprodutovariacao = pb2.codprodutovariacao and pb_un.codprodutoembalagem is null)
    ) iq_barra_un on (iq_barra_un.codprodutobarra = i.codprodutobarra)
where codnfeterceiro in (16465, 16466)
--and i.cean != pb.barras
--and i.cean = iq_barra_un.barras
--and cprod = coalesce(pv.referencia, p.referencia)
order by produto
    
/*
select * 
from tblnfeterceiroitem i
where codnfeterceiro = 16609


*/
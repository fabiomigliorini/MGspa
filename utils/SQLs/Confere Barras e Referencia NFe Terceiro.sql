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
where codnfeterceiro = 20757
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
    i.cprod, 
    --pv.codprodutovariacao,
    --coalesce(pv.referencia, p.referencia) as referencia,
    p.produto || coalesce(' | ' || pv.variacao, '') as produto,
    --xprod,
    p.preco,
    iq_barra_un.barras as unidade,
    /*
    pb.barras as pacote,
    (
        select pb_pt.barras
        from tblprodutobarra pb_pt
        where pb_pt.codprodutovariacao = pb.codprodutovariacao
        and pb_pt.barras not in (pb.barras, iq_barra_un.barras)
        limit 1
    ) as caixa,
    cast(pe.quantidade as bigint)  as pacote, 
    (
        select cast(pe_pt.quantidade as int)
        from tblprodutobarra pb_pt
        inner join tblprodutoembalagem pe_pt on (pe_pt.codprodutoembalagem = pb_pt.codprodutoembalagem)
        where pb_pt.codprodutovariacao = pb.codprodutovariacao
        and pb_pt.barras not in (pb.barras, iq_barra_un.barras)
        limit 1
    ) as caixa,
    cast(i.qcom as bigint) as compra,
    */
    cast(i.qcom * coalesce(pe.quantidade, 1) as bigint)  as total
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
where codnfeterceiro in (20822)
--and i.cean != pb.barras -- Barras Embalagem Compra nao bate
--and i.ceantrib != iq_barra_un.barras -- Barras Unidade nao bate
--and cprod != coalesce(pv.referencia, p.referencia) -- Referencia nao bate
order by produto, pv.variacao, pv.codprodutovariacao
    
/*
select * 
from tblnfeterceiroitem i
where codnfeterceiro = 16609


*/
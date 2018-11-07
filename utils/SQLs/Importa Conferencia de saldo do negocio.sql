-- Cria registros EstoqueSaldoConferencia
insert into tblestoquesaldoconferencia (codestoquesaldo, quantidadesistema, quantidadeinformada, customediosistema, customedioinformado, data, alteracao, codusuarioalteracao, criacao, codusuariocriacao, observacoes)
select 
    sld.codestoquesaldo, 
    coalesce(sld.saldoquantidade, 0) as quantidadesistema,
    coalesce(neg.quantidade, 0) + coalesce((
            select sum(coalesce(mov.entradaquantidade, 0) - coalesce(mov.saidaquantidade, 0)) as mov
            from tblestoquesaldo es
            inner join tblestoquemes mes on (mes.codestoquesaldo = es.codestoquesaldo)
            inner join tblestoquemovimento mov on (mes.codestoquemes = mov.codestoquemes)
            where es.codestoquesaldo = sld.codestoquesaldo
            and mov.data >= neg.data
        ), 0) as quantidadeinformada,
    coalesce(sld.customedio, 0) as customediosistema, 
    coalesce(sld.customedio, 0) as customedioinformado,
    '2017-11-16 18:00'::timestamp as data,
    '2017-11-16 18:00'::timestamp as alteracao,
    301932 as codusuarioalteracao,
    '2017-11-16 18:00'::timestamp as criacao,
    301932 as codusuariocriacao,
    'Importado do Negocio 924306' as observacoes
from (
        select n.codestoquelocal, pb.codprodutovariacao, pb.codproduto, sum(npb.quantidade * coalesce(pe.quantidade, 1)) as quantidade, max(npb.alteracao) as data
        from tblnegocioprodutobarra npb
        inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
        left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
        inner join tblnegocio n on (n.codnegocio = npb.codnegocio)
        where npb.codnegocio = 924306 -- CODIGO DO NEGOCIO
        group by n.codestoquelocal, pb.codproduto, pb.codprodutovariacao
    ) neg
full join (
        select elpv.codestoquelocal, elpv.codprodutovariacao, es.codestoquesaldo, es.saldoquantidade, es.customedio
        from tblestoquelocalprodutovariacao elpv
        inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
        where elpv.codestoquelocal = 104001 
        and elpv.codprodutovariacao in (
            select var_pv.codprodutovariacao
            from tblprodutovariacao var_pv
            where var_pv.codproduto in (
                select distinct var_pb.codproduto
                from tblnegocioprodutobarra var_npb
                inner join tblprodutobarra var_pb on (var_pb.codprodutobarra = var_npb.codprodutobarra)
                where var_npb.codnegocio = 924306  -- CODIGO DO NEGOCIO
            )        
        )
    ) sld on (sld.codestoquelocal = neg.codestoquelocal and sld.codprodutovariacao = neg.codprodutovariacao)

-- Atualiza Data da Conferencia na EstoqueSaldo
update tblestoquesaldo set ultimaconferencia = (select max(esc.alteracao) from tblestoquesaldoconferencia esc where esc.codestoquesaldo = tblestoquesaldo.codestoquesaldo)

-- Cria registros de EstoqueMes necessarios para o movimento
insert into tblestoquemes (codestoquesaldo, mes)
select esc.codestoquesaldo, '2017-11-01'
from tblestoquesaldoconferencia esc
left join tblestoquemes mes on (mes.codestoquesaldo = esc.codestoquesaldo and mes.mes = '2017-11-01')
where esc.alteracao = '2017-11-16 18:00'
and esc.codusuarioalteracao = 301932
and esc.quantidadesistema != esc.quantidadeinformada
and mes.codestoquemes is null

-- Cria registros de EstoqueMovimento pro que tinha saldo diferente
insert into tblestoquemovimento (codestoquemovimentotipo, entradaquantidade, entradavalor, saidaquantidade, saidavalor, codestoquemes, manual, data, alteracao, codusuarioalteracao, criacao, codusuariocriacao, codestoquesaldoconferencia)
select 
    1002 as codestoquemovimentotipo,
    case when (esc.quantidadesistema < esc.quantidadeinformada) then (esc.quantidadeinformada - esc.quantidadesistema) else null end as entradaquantidade,
    case when (esc.quantidadesistema < esc.quantidadeinformada) then (esc.quantidadeinformada - esc.quantidadesistema) else null end * esc.customedioinformado as entradavalor,
    case when (esc.quantidadesistema > esc.quantidadeinformada) then (esc.quantidadesistema - esc.quantidadeinformada) else null end as saidaquantidade,
    case when (esc.quantidadesistema > esc.quantidadeinformada) then (esc.quantidadesistema - esc.quantidadeinformada) else null end * esc.customedioinformado as saidavalor,
    (select mes.codestoquemes from tblestoquemes mes where mes.codestoquesaldo = esc.codestoquesaldo and mes.mes = '2017-11-01') as codestoquemes,
    false as manual,
    esc.data,
    esc.alteracao,
    esc.codusuarioalteracao,
    esc.criacao,
    esc.codusuariocriacao,
    esc.codestoquesaldoconferencia
from tblestoquesaldoconferencia esc
where esc.alteracao = '2017-11-16 18:00'
and esc.codusuarioalteracao = 301932
and esc.quantidadesistema != esc.quantidadeinformada

-- Recalcula Custo Medio
select 
    'wget http://192.168.1.205/MGLara/estoque/calcula-custo-medio/' || cast(mov.codestoquemes as varchar)
from tblestoquesaldoconferencia esc
inner join tblestoquemovimento mov on (mov.codestoquesaldoconferencia = esc.codestoquesaldoconferencia)
where esc.alteracao = '2017-11-16 18:00'
and esc.codusuarioalteracao = 301932
and esc.quantidadesistema != esc.quantidadeinformada


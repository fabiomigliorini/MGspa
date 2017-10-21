-- alter table tblmarca add controlada boolean not null default false

-- update tblmarca set controlada = true where marca ilike '3m'
-- PARA SEPARAR DO DEPOSITO PRAS LOJAS
/*
select 
	-- m.marca, 
	p.codproduto, 
	-- p.produto, 
	-- pv.variacao, 
	p.produto || coalesce(' | ' || pv.variacao, ''),
	um.sigla,
	-- p.preco,
	--coalesce(pv.referencia, p.referencia), 
	(
		select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
		from tblprodutobarra pb
		left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
		left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
		where pb.codprodutovariacao = pv.codprodutovariacao
		order by pe.quantidade nulls first, pb.barras
		limit 1
	),
	-- elpv_deposito.corredor, 
	-- elpv_deposito.prateleira, 
	-- elpv_deposito.coluna, 
	-- elpv_deposito.bloco,
    cast(es.saldoquantidade as bigint) as loja,
	elpv.estoqueminimo as min, 
	elpv.estoquemaximo as max, 
	cast(es_deposito.saldoquantidade as bigint) as deposito,
	cast(elpv.estoquemaximo - (case when coalesce(es.saldoquantidade, 0) <= 0 then 0 else es.saldoquantidade end) as bigint) as separar
from tblestoquelocalprodutovariacao elpv
left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
inner join tblproduto p on (p.codproduto = pv.codproduto)
inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
inner join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codestoquelocal = 101001 and elpv_deposito.codprodutovariacao = elpv.codprodutovariacao)
inner join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
where elpv.codestoquelocal = 103001
--and m.marca not ilike 'gitex'
and m.controlada = true
and coalesce(es.saldoquantidade, 0) <= coalesce(elpv.estoqueminimo, 0)
and es_deposito.saldoquantidade > 0
--and es.saldoquantidade is null
order by m.marca, p.produto, pv.variacao
*/

-- PARA COMPRAR
select 
    * 
    , case when (x.repor > 0) then ceil(x.repor::float / x.lote::float) * x.lote else 0 end as comprar
from 
(
    select 
        --m.marca,
        p.codproduto as "#",
        pv.codprodutovariacao as "# Var",
        --p.produto,
        --pv.variacao,
        --pv.descontinuado,
        p.produto || coalesce(' | ' || pv.variacao, '') as produto,
        coalesce(pv.referencia, p.referencia) as referencia,
        --p.preco,
        pv.custoultimacompra as custo,
        pv.dataultimacompra as data,
        cast(sld.saldoquantidade as bigint) as sld,
        sld.vendadiaquantidadeprevisao as vda_dia,
        cast(case when sld.vendadiaquantidadeprevisao != 0 then sld.saldoquantidade / sld.vendadiaquantidadeprevisao else null end as bigint) as dias,
        sld.estoqueminimo as min,
        sld.estoquemaximo as max,
        coalesce((select min(pe.quantidade) from tblprodutoembalagem pe where pe.codproduto = pv.codproduto)::int, 1) as lote,
        cast(sld.estoquemaximo - case when sld.saldoquantidade > 0 then sld.saldoquantidade else 0 end as bigint) as repor
    from tblproduto p
    inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
    inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
    inner join (
        select 
            elpv.codprodutovariacao
            , sum(elpv.estoqueminimo) as estoqueminimo
            , sum(elpv.estoquemaximo) as estoquemaximo
            , sum(es.saldoquantidade) as saldoquantidade
            , sum(es.saldovalor) as saldovalor
            , sum(case when el.deposito then 0 else coalesce(elpv.vendadiaquantidadeprevisao, 0) end) as vendadiaquantidadeprevisao
        from tblestoquelocalprodutovariacao elpv
        inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
        left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
        where el.inativo is null
        group by elpv.codprodutovariacao
        --limit 50
        ) sld on (sld.codprodutovariacao = pv.codprodutovariacao)
    where m.controlada = true
    --and coalesce(sld.saldoquantidade, 0) < sld.estoqueminimo
    --and coalesce(sld.saldoquantidade, 0) < sld.estoquemaximo
    and m.marca ilike '%acrimet%'
    --and pv.codprodutovariacao = 15218
    and pv.descontinuado is null
    and p.inativo is null
    --and pv.codprodutovariacao = 20121
    order by m.marca, p.produto, pv.variacao
) x
--order by sld nulls first


/*
update tblprodutovariacao set descontinuado = date_trunc('second', now()) where codprodutovariacao in (
	70403
,	49753
,	49754
,	49961
,	49962
,	31891
,	2704
,	2705
,	2706
,	2707
,	2708
,	2709
,	2710
,	2711
,	2712
,	2713
,	2714
,	2715
,	2716
,	2717
,	2718
,	2719
,	2720
,	2721
,	2722
,	2723
,	2724
,	2725
,	2726
,	2727
,	2728
,	2729
,	2730
,	2731
,	2732
,	2733
,	2734
,	2735
,	2736
,	2737
,	2738
,	2739
,	2740
,	2741
,	2742
,	2743
,	2744
,	2745
,	2746
,	2747
,	2748
,	2749
,	2750
,	2751
,	2752
,	2753
,	2754
,	2755
,	2756
,	2757
,	2758
,	2759
,	2760
,	2761
,	2762
,	2763
,	2764
,	2765
,	2766
,	2767
,	2768
,	2769
,	2770
,	2771
,	2772
,	2773
,	2774
,	2775
,	2776
,	2777
,	2778
,	2779
,	2780
,	2781
,	2782
,	2783
,	2784
,	3585
,	5705
,	16605
,	16606
,	16607
,	16608
,	16609
,	16610
,	16611
,	16612
,	16613
,	12191
,	12192
,	12193
,	12194
,	12195
,	12196
,	39760
,	52171
,	21299
,	21300
,	21301
,	21302
,	21303
,	21304
,	21305
,	21307
,	21308
,	21309
,	21310
,	21311
,	21312
,	21313
,	21314
,	21316
,	21317
,	21318
,	21319
,	21320
,	21322
,	21324
,	32647
,	32649
,	64510
,	43015
,	43016
,	43017
,	43018
,	43019
,	43020
,	43021
,	43022
,	43023
,	43024
,	43025
,	43026
,	43027
,	43028
,	43029
,	43030
,	43031
,	43032
,	43033
,	43034
,	43035
,	43036
,	43037
,	43038
,	43039
,	43040
,	43041
,	43042
,	43043
,	43044
,	43045
,	43007
,	43008
,	43009
,	43010
,	43011
,	43012
,	43013
,	43014
,	32027
,	32028
,	32029
,	32030
,	32031
,	32032
,	32033
,	32034
,	32035
,	32036
,	32037
,	32038
,	32039
,	32040
,	32041
,	32042
,	32043
,	32044
,	32045
,	32046
,	32047
,	32048
,	32049
,	32050
,	32051
,	32052
,	32053
,	32054
,	32055
,	32056
,	32057
,	32058
,	32059
,	32060
,	32061
,	32062
,	32063
,	32064
,	32065
,	32066
,	32067
,	32068
,	32069
,	32070
,	32071
,	32072
,	32073
,	32074
,	32075
,	32076
,	32077
,	32078
,	32079
,	32080
,	32081
,	32082
,	32083
,	32084
,	32085
,	32086
,	32087
,	32088
,	32089
,	32090
,	32091
,	32092
,	32093
,	59216
,	59217
,	59218
,	59219
,	59220
,	59221
,	59222
,	59223
,	59224
,	59225
,	59226
,	59227
,	59228
,	59229
,	59230
,	59231
,	59232
,	59233
,	59234
,	59235
,	59236
,	59237
,	59238
,	59239
,	59240
,	59241
,	59242
,	59243
,	59244
,	59245
,	59246
,	59247
,	50220
,	50221
,	50222
,	50223
,	50224
,	50225
,	50226
,	50227
,	50228
,	50229
,	50230
,	50231
,	50232
,	50233
,	50234
,	50235
,	50236
,	50237
,	50238
,	50239
,	50240
,	50241
,	50242
,	50243
,	50244
,	50245
,	50246
,	50247
,	50248
,	50249
,	50250
,	50251
,	50252
,	50253
,	50254
,	50255
,	50256
,	50257
,	50258
,	50259
,	50260
,	50261
,	50262
,	50263
,	50264
,	50265
,	50266
,	50267
,	50268
,	50269
,	50270
,	50271
,	50272
,	50273
,	50274
,	50275
,	50276
,	50277
,	50278
,	50279
,	50280
,	2588
,	2597
,	2599
,	2600
,	2602
,	2603
,	2607
,	2608
,	2609
,	2610
,	2611
,	2612
,	2613
,	2614
,	2631
,	2632
,	2639
,	2641
,	2642
,	2643
,	2644
,	2645
,	2646
,	2657
,	2664
,	2665
,	2667
,	2671
,	2674
,	2677
,	2678
,	2679
,	2680
,	2681
,	2682
,	2683
,	2685
,	2686
,	2687
,	2688
,	31874
,	31875
,	31876
,	31877
,	31878
,	31879
,	31880
,	31881
,	31882
,	31883
,	31884
,	31885
,	31886
,	31887
,	32094
,	32095
,	32096
,	32097
,	32098
,	32099
,	32100
,	32101
,	32102
,	32103
,	32104
,	32105
,	32106
,	32107
,	32108
,	32109
,	32110
,	32111
,	32112
,	32113
,	32114
,	32115
,	32116
,	32117
,	32118
,	32119
,	32120
,	32121
,	32122
,	32123
,	32124
,	32125
,	32126
,	32127
,	32128
,	32129
,	32130
,	32131
,	32132
,	32133
,	714
,	715
,	716
,	721
,	722
,	723
,	724
,	725
,	726
,	727
,	728
,	729
,	730
,	731
,	732
,	733
,	734
,	735
,	736
,	737
,	738
,	739
,	740
,	741
,	47871
,	47872
,	47873
,	47874
,	47875
,	47876
,	47877
,	47878
,	47879
,	47880
,	47881
,	47882
,	47883
,	47884
,	47885
,	47886
,	47887
,	47888
,	47889
,	47890
,	75166
,	47891
,	47892
,	47893
,	47894
,	47895
,	47896
,	47897
,	47898
,	47899
,	47900
,	47901
,	47902
,	47903
,	47904
,	47905
,	47906
,	47907
,	47908
,	497
,	501
,	502
,	506
,	507
,	516
,	517
,	522
,	523
,	527
,	533
,	538
,	540
,	541
,	542
,	545
,	547
,	548
,	550
,	551
,	552
,	553
,	554
,	556
,	1190
,	1198
,	1200
,	1203
,	1208
,	1210
,	1211
,	1213
,	1214
,	1219
,	1220
,	1227
,	1228
,	1229
,	1230
,	1231
,	1232
,	1233
,	1234
,	1235
,	1236
,	1237
,	1238
,	1239
,	1240
,	1241
,	1242
,	1243
,	1244
,	1245
,	1246
,	1247
,	1248
,	1249
,	1250
,	1251
,	1252
,	1253
,	1254
,	1255
,	1256
,	1257
,	1258
,	1259
,	1260
,	42992
,	42993
,	42999
,	43000
,	28390
,	28393
,	28395
,	28396
,	28397
,	28400
,	28401
,	28403
,	28410
,	28411
,	28412
,	28413
,	28414
,	28415
,	28416
)
*/

--update tblestoquelocal set inativo = '2016-12-31' where codestoquelocal in (201001, 301001)

-- PARA RECOLHER PRO DEPOSITO
/*
select 
    iq.*
    , iq.loja - iq.deixar as recolher
from (
    select 
        -- m.marca, 
        p.codproduto, 
        -- p.produto, 
        -- pv.variacao, 
        p.produto || coalesce(' | ' || pv.variacao, ''),
        um.sigla as um,
        --p.preco,
        --coalesce(pv.referencia, p.referencia), 
        (
            select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
            from tblprodutobarra pb
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
            where pb.codprodutovariacao = pv.codprodutovariacao
            order by pe.quantidade nulls first, pb.barras
            limit 1
        ) as barras,
        -- elpv_deposito.corredor, 
        -- elpv_deposito.prateleira, 
        -- elpv_deposito.coluna, 
        -- elpv_deposito.bloco,
        cast(es_deposito.saldoquantidade as bigint) as deposito,
        --elpv_deposito.estoqueminimo as min, 
        --elpv_deposito.estoquemaximo as max, 
        cast(es.saldoquantidade as bigint) as loja, 
        case when elpv.vendadiaquantidadeprevisao > 0 then cast(es.saldoquantidade / elpv.vendadiaquantidadeprevisao as bigint) else null end as dias, 
        elpv.estoqueminimo as min, 
        elpv.estoquemaximo as max, 
        --elpv.vendadiaquantidadeprevisao,
        case when cast(coalesce(elpv.vendadiaquantidadeprevisao, 0) * 90 as bigint) < 2 then 2 else cast(elpv.vendadiaquantidadeprevisao * 90 as bigint) end as deixar
    from tblestoquelocalprodutovariacao elpv
    inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
    inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
    inner join tblproduto p on (p.codproduto = pv.codproduto)
    inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
    inner join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codestoquelocal = 101001 and elpv_deposito.codprodutovariacao = elpv.codprodutovariacao)
    inner join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
    inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
    where elpv.codestoquelocal = 102001
    and m.controlada = true
    and m.marca not ilike 'pilot'
    --and (es.saldoquantidade - (coalesce(elpv.vendadiaquantidadeprevisao, 0) * 90) > 1)
    and coalesce(es_deposito.saldoquantidade, 0) <= coalesce(elpv_deposito.estoqueminimo, 0)
    --and es_deposito.saldoquantidade > 0
    order by m.marca, p.produto, pv.variacao
    ) iq 
where iq.deixar < iq.loja
*/

-- Transferir de uma loja para outra
/*
select 
    iq.codproduto,
    iq.produto,
    iq.um,
    iq.barras,
    iq.origem,
    --iq.origem_90dd,
    iq.destino,
    --iq.destino_90dd,
    case when (iq.origem - iq.origem_90dd) < (iq.destino_90dd - iq.destino) then (iq.origem - iq.origem_90dd) else (iq.destino_90dd - iq.destino) end  as transferir
from (
    select 
        -- m.marca, 
        p.codproduto, 
        -- p.produto, 
        -- pv.variacao, 
        p.produto || coalesce(' | ' || pv.variacao, '') as produto,
        um.sigla as um,
        --p.preco,
        --coalesce(pv.referencia, p.referencia), 
        (
            select pb.barras || coalesce(' ' || pe_um.sigla || ' C/' || cast(pe.quantidade as bigint), '')
            from tblprodutobarra pb
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida pe_um on (pe_um.codunidademedida = pe.codunidademedida)
            where pb.codprodutovariacao = pv.codprodutovariacao
            order by pe.quantidade nulls first, pb.barras
            limit 1
        ) as barras,
        -- elpv_destino.corredor, 
        -- elpv_destino.prateleira, 
        -- elpv_destino.coluna, 
        -- elpv_destino.bloco,

        cast(coalesce(es_destino.saldoquantidade, 0) as bigint) as destino,
        --case when elpv_destino.vendadiaquantidadeprevisao > 0 then cast(es_destino.saldoquantidade / elpv_destino.vendadiaquantidadeprevisao as bigint) else null end as destino_dias, 
        --elpv_destino.estoqueminimo as destino_min, 
        --elpv_destino.estoquemaximo as destino_max, 
        case when cast(coalesce(elpv_destino.vendadiaquantidadeprevisao, 0) * 90 as bigint) < 2 then 2 else cast(coalesce(elpv_destino.vendadiaquantidadeprevisao, 0) * 90 as bigint) end as destino_90dd,

        cast(coalesce(es.saldoquantidade, 0) as bigint) as origem, 
        --case when elpv.vendadiaquantidadeprevisao > 0 then cast(es.saldoquantidade / elpv.vendadiaquantidadeprevisao as bigint) else null end as origem_dias, 
        --elpv.estoqueminimo as origem_min, 
        --elpv.estoquemaximo as origem_max, 
        --elpv.vendadiaquantidadeprevisao,
        case when cast(coalesce(elpv.vendadiaquantidadeprevisao, 0) * 90 as bigint) < 2 then 2 else cast(coalesce(elpv.vendadiaquantidadeprevisao, 0) * 90 as bigint) end as origem_90dd
        
    from tblestoquelocalprodutovariacao elpv
    inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
    inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
    inner join tblproduto p on (p.codproduto = pv.codproduto)
    inner join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
    -- DESTINO
    inner join tblestoquelocalprodutovariacao elpv_destino on (elpv_destino.codestoquelocal = 103001 and elpv_destino.codprodutovariacao = elpv.codprodutovariacao)
    inner join tblestoquesaldo es_destino on (es_destino.codestoquelocalprodutovariacao = elpv_destino.codestoquelocalprodutovariacao and es_destino.fiscal = false)
    inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
    -- ORIGEM
    where elpv.codestoquelocal = 102001 
    and m.controlada = true
    and m.marca not ilike 'pilot'
    --and (es.saldoquantidade - (coalesce(elpv.vendadiaquantidadeprevisao, 0) * 90) > 1)
    and coalesce(es_destino.saldoquantidade, 0) <= coalesce(elpv_destino.estoqueminimo, 0)
    --and es_destino.saldoquantidade > 0
    order by m.marca, p.produto, pv.variacao
) iq
where iq.origem > iq.origem_90dd
*/
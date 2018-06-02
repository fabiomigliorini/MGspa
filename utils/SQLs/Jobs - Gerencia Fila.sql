-- delete from tbljobs where payload ilike '%EstoqueCalculaEstatisticas%';
-- delete from tbljobs where queue ilike '%parado_cm%';

select queue, count(*) from tbljobs group by queue order by queue
--

/*

update tbljobs set queue = 'low' where queue = 'parado_cm_2'

-- apaga jobs duplicadas
delete from tbljobs where tbljobs.id not in (select min(id) from tbljobs dup group by dup.payload)


select * from tbljobs where queue = 'high' limit 100 

select * from tbljobs 

delete from tbljobs where id = 10241019


update tbljobs set queue = 'parado_cm_2' where payload ilike '%EstoqueCalculaCustoMedio%'

update tbljobs set queue = 'parado_nfpb' where payload ilike '%EstoqueGeraMovimentoNotaFiscalProdutoBarra%'

update tbljobs set queue = 'parado_nf' where payload ilike '%EstoqueGeraMovimentoNotaFiscal%'

update tbljobs set queue = 'parado_n' where payload ilike '%EstoqueGeraMovimentoNegocio%'


update tbljobs set queue = 'parado_npb' where payload ilike '%EstoqueGeraMovimentoNegocioProdutoBarra%'

update tbljobs set queue = 'low' where tbljobs.id in (select j2.id from tbljobs j2 where j2.queue = 'CONFERENCIA' order by j2.payload limit 10)


select queue, count(*) from tbljobs group by queue order by queue

delete from tbljobs where id in (
7160352
)

'high';39
'medium';1538
'urgent_custo_medio_fiscal';113630

select * from tbljobs where queue in ('high', 'medium')


delete from tbljobs where id in (
10345545
,10345546
,10345547
,10345548
,10345549
,10347332
,10347333
,10347335
,10347337
,10349961
,10349964
,10349966
,10349967
,10349968
,10349970
,10352592
,10352594
,10352596
,10352597
,10352681
,10354299
,10354322
,10354339
,10354344
,10354377
,10354378
,10354463
,10354470
,10354473
,10354474
,10354475
,10356028
,10356029
,10356031
,10356556
,10356557
,10356558
,10356559
,10356560
,10364294
,10364295
,10364296
,10364297
,10364298
,10367664
,10367665
,10367667
,10367669
,10367670
,10367671
,10367672
,10370253
,10370255
,10370260
,10271246
,10270098
,10354402
,10354403
,10354404
,10354406
)
*/


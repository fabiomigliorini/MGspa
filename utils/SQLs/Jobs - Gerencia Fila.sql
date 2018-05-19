-- delete from tbljobs where payload ilike '%EstoqueCalculaEstatisticas%';
-- delete from tbljobs where queue ilike '%parado_cm%';

select queue, count(*) from tbljobs group by queue order by queue
--select * from tbljobs where queue = 'parado_cm_2' limit 100 

/*
delete from tbljobs where id = 8678400

update tbljobs set queue = 'urgent' where queue = 'parado_cm_2'

-- apaga jobs duplicadas
delete from tbljobs where tbljobs.id not in (select min(id) from tbljobs dup group by dup.payload)

update tbljobs set queue = 'parado_nfpb' where payload ilike '%EstoqueGeraMovimentoNotaFiscalProdutoBarra%'

update tbljobs set queue = 'parado_nf' where payload ilike '%EstoqueGeraMovimentoNotaFiscal%'

update tbljobs set queue = 'parado_n' where payload ilike '%EstoqueGeraMovimentoNegocio%'

update tbljobs set queue = 'parado_cm_2' where payload ilike '%EstoqueCalculaCustoMedio%' and queue = 'urgent'


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
7160379
)
*/


--delete from tbljobs where payload ilike '%EstoqueCalculaEstatisticas%';

select queue, count(*) from tbljobs group by queue order by queue


--select * from tbljobs limit 100

/*
update tbljobs set queue = 'low' where queue = 'urgent_custo_medio_fiscal'

-- apaga jobs duplicadas
delete from tbljobs where tbljobs.id not in (select min(id) from tbljobs dup group by dup.payload)

update tbljobs set queue = 'parado_nfpb' where payload ilike '%EstoqueGeraMovimentoNotaFiscalProdutoBarra%'

update tbljobs set queue = 'parado_nf' where payload ilike '%EstoqueGeraMovimentoNotaFiscal%'

update tbljobs set queue = 'parado_n' where payload ilike '%EstoqueGeraMovimentoNegocio%'

update tbljobs set queue = 'low' where tbljobs.id in (select j2.id from tbljobs j2 where j2.queue = 'parado_n' order by j2.payload limit 50)


select queue, count(*) from tbljobs group by queue order by queue


'high';39
'medium';1538
'urgent_custo_medio_fiscal';113630

select * from tbljobs where queue in ('high', 'medium')


delete from tbljobs where id in (3577560
,3577559
,3577556
,3281850
,3281849
,3281846
,3281845
,3281844
,3281842
)
*/


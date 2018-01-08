delete from tbljobs where payload ilike '%EstoqueCalculaEstatisticas%';

select queue, count(*) from tbljobs group by queue order by queue

--select * from tbljobs limit 100

--select * from tbljobs where payload ilike '%EstoqueGeraMovimentoNotaFiscalProdutoBarra\\%'

-- update tbljobs set queue = 'parado_nf' where payload ilike '%EstoqueGeraMovimentoNotaFiscal%'

--update tbljobs set queue = 'parado_n' where payload ilike '%EstoqueGeraMovimentoNegocioProdutoBarra\\%'

-- update tbljobs set queue = 'low' where tbljobs.id in (select j2.id from tbljobs j2 where j2.queue = 'parado_nf' order by j2.payload desc limit 100)


select p.codpessoa, p.fantasia, p.pessoa, p.telefone1, sum(n.valortotal) as valortotal
from tblnegocio n
inner join tblpessoa p on (p.codpessoa = n.codpessoa)
where n.codnegociostatus = 2 
and n.codfilial in (101, 102, 103, 104)
--and n.codnaturezaoperacao = 00000004 -- Compra
and n.codnaturezaoperacao = 00000001 -- Venda
and n.lancamento >= '2017-04-01'
group by p.codpessoa, p.fantasia, p.pessoa, p.telefone1
order by 5 desc
--limit 30
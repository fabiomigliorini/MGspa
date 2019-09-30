select sum(nf.valortotal) as comprado, p.codpessoa, p.fantasia, p.pessoa, p.contato, p.telefone1, p.telefone2, p.telefone3
from tblnotafiscal nf
inner join tblpessoa p on (p.codpessoa = nf.codpessoa)
where nf.codnaturezaoperacao = 4
and emissao >= '2018-09-01'
and nf.codfilial = 101
group by p.codpessoa, p.fantasia, p.pessoa, p.contato, p.telefone1, p.telefone2, p.telefone3
order by 1 desc
--limit 50
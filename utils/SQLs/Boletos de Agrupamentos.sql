﻿select 
  extract(year from emissao)
, extract(month from emissao)
, sum(debito)
, max(debito)
, min(debito)
, sum(saldo)
, count(codtitulo)
from tbltitulo 
where numero like 'A%' 
and emissao >= '2019-01-01'
and estornado is null
and boleto
--and debito > 0
group by 
  extract(year from emissao)
, extract(month from emissao)
order by 1 desc, 2 desc

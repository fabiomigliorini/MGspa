select codfilial, nfechave, valortotal, emissao
from tblnotafiscal
where cpf is null
and emissao between '2021-10-01 00:00:00' and '2021-10-01 23:59:59'
and emitida = true
and modelo = 65
and nfecancelamento is null
and nfeinutilizacao is null
and nfeautorizacao is not null
order by codfilial, nfechave
select codportador, dataretorno, linha, arquivo, count(*) 
from tblboletoretorno
group by codportador, dataretorno, linha, arquivo
having count(*) > 1

/*
select * from tbltitulo where nossonumero = '13010004766'

update tbltitulo set boleto = true, codportador = 3941 where codtitulo = 163934

select * from tblboletoretorno where codtitulo is null limit 5
*/

/*
delete from tblboletoretorno 
where codportador = 210
and dataretorno = '2015-01-06'
and arquivo = 'CB060100.RET'

delete from tblmovimentotitulo where codboletoretorno in (
select codboletoretorno
from tblboletoretorno
where codportador = 210
and dataretorno = '2015-01-06'
and arquivo = 'CB060100.RET'
)
*/
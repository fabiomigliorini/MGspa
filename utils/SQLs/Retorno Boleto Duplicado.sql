/*

--select * from tblboletoretorno limit 10
--

--delete from tblmovimentotitulo where codboletoretorno in (
delete from tblboletoretorno where codboletoretorno in (
select codboletoretorno 
from tblboletoretorno 
where arquivo = 'CB211200.RET' and dataretorno = '2019-12-23' and codportador = 3941
order by linha
)
*/
/*
select 
	codportador, dataretorno, arquivo, linha
	, count(*)
from	tblboletoretorno
group by
	codportador, dataretorno, arquivo, linha
having count(*) > 1
order by 
	codportador, dataretorno, arquivo, linha



*/

--select * from tblboletoretorno where nossonumero ilike '%13010003914%' and codportador = 3941

select 
	codportador, dataretorno, arquivo
	, count(*)
from	tblboletoretorno
where 
    dataretorno >= '2017-01-01'
    and codportador = 210
group by
	codportador, dataretorno, arquivo
order by 
	codportador, dataretorno, arquivo

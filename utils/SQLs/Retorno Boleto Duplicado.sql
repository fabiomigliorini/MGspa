/*

--select * from tblboletoretorno limit 10
--

--delete from tblmovimentotitulo where codboletoretorno in (
delete from tblboletoretorno where codboletoretorno in (
	select codboletoretorno 
	from tblboletoretorno 
	where arquivo = 'CB120200.RET' and dataretorno = '2020-02-12' and codportador = 3941
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
	codportador, 
	dataretorno, 
	arquivo,
	count(codportador) as registros,
	count(codtitulo) as sucesso,
	sum(pagamento) as pagamento,
	sum(valor) as valor,
	null
from	tblboletoretorno
where 
    dataretorno >= '2017-01-01'
    --and codportador = 210
group by
	codportador, dataretorno, arquivo
order by 
	codportador, dataretorno, arquivo


--update tblboletoretorno set codtitulo = null where arquivo = 'CB190600.RET' and codportador = 3943 and dataretorno = '2020-06-19'
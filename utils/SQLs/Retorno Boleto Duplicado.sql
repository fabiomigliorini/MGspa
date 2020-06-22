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
	br.dataretorno, 
	br.arquivo,
	br.codportador, 
	p.portador,
	count(br.codportador) as registros,
	count(br.codtitulo) as sucesso,
	count(br.codportador) - count(br.codtitulo) as falha,
	sum(br.pagamento) as pagamento,
	sum(br.valor) as valor,
	null
from	tblboletoretorno br
inner join tblportador p on (p.codportador = br.codportador)
where 
    br.dataretorno >= date_trunc('year', now() - '1 year'::interval)
    --and codportador = 210
group by
	br.codportador, p.portador, br.dataretorno, br.arquivo
order by 
	dataretorno DESC, arquivo DESC, codportador


--update tblboletoretorno set codtitulo = null where arquivo = 'CB200600.RET' and codportador = 3940 and dataretorno = '2020-06-22'
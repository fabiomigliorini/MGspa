insert into tblestoquemovimento 
	(codestoquemes, codestoquemovimentotipo, entradaquantidade, entradavalor, saidaquantidade, saidavalor, manual, data, criacao, codusuariocriacao, alteracao, codusuarioalteracao, observacoes)
with regs as (
	select 
		mes.codestoquemes, 
		mes.customedio,
		round(mes.saldoquantidade, 0) - mes.saldoquantidade as arredondar,
		mes.mes
	from tblprodutovariacao pv 
	inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao)
	inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = true)
	inner join tblestoquemes mes on (mes.codestoquemes = (select iq.codestoquemes from tblestoquemes iq where iq.codestoquesaldo = es.codestoquesaldo and iq.mes <= '2019-12-31' order by iq.mes desc limit 1) )
	where mes.saldoquantidade != round(mes.saldoquantidade, 0)
	order by codproduto
)
select 
	regs.codestoquemes, 
	1002 as codestoquemovimentotipo,
	case when arredondar > 0 then abs(arredondar) else null end as entradaquantidade,
	case when arredondar > 0 then abs(arredondar * customedio) else null end as entradavalor,
	case when arredondar < 0 then abs(arredondar) else null end as saidaquantidade,
	case when arredondar < 0 then abs(arredondar * customedio) else null end as saidavalor,
	true as manual,
	(date_trunc('month', mes) + interval '1 month' - interval '1 second') as data,
	date_trunc('second', now()) as criacao,
	1 as codusuariocriacao,
	date_trunc('second', now()) as alteracao,
	1 as codusuarioalteracao,
	'Arredondamento automatico dos produtos com quantidade fracionada do estoque fiscal de dezembro de 2019.' as observacoes
from regs

select 'wget https://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/' || codestoquemes from tblestoquemovimento where observacoes = 'Arredondamento automatico dos produtos com quantidade fracionada do estoque fiscal de dezembro de 2019.' order by codestoquemes

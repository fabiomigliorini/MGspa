select n.codnegocio, no.naturezaoperacao, f.filial, fd.filial, n.valortotal, n.lancamento
from tblnegocio n 
inner join tblnaturezaoperacao no on (no.codnaturezaoperacao = n.codnaturezaoperacao)
inner join tblfilial f on (f.codfilial = n.codfilial)
inner join tblfilial fd on (fd.codpessoa = n.codpessoa)
where n.codnegociostatus = 2 -- fechado
and n.codnaturezaoperacao not in (15, 19, 18, 17) -- transferencia de saida - uso consumo - perda - bonificacao
and f.codempresa = 1
and f.codempresa = fd.codempresa
and n.lancamento >= '2016-04-01'
--and n.lancamento > current_date - 300
--limit 100
order by n.lancamento desc, n.codnegocio desc

/*
-- TRANSFERENCIA DE SAIDA
update tblnegocio set codnaturezaoperacao = 15 where codnegocio = 932522;

update tbltitulo 
set codtipotitulo = 00000922, codcontacontabil = 00000014
where tbltitulo.codnegocioformapagamento in (select nfp.codnegocioformapagamento from tblnegocioformapagamento nfp where nfp.codnegocio = 932522);

http://sistema.mgpapelaria.com.br/MGLara/estoque/gera-movimento-negocio/1094398

-- SAIDA BONIFICACAO
update tblnegocio set codnaturezaoperacao = 17 where codnegocio = 1090922;

update tbltitulo 
set codtipotitulo = 924, codcontacontabil = 15
where tbltitulo.codnegocioformapagamento in (select nfp.codnegocioformapagamento from tblnegocioformapagamento nfp where nfp.codnegocio = 1090922);

http://sistema.mgpapelaria.com.br/MGLara/estoque/gera-movimento-negocio/1094398

*/
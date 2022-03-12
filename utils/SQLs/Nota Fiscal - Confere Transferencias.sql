
update tblnotafiscal set numero = 0, emitida = true, nfechave = null where codnotafiscal = 2090080

--select * from tblfilial

select * from tblnaturezaoperacao t 

with lancadas as (
	select
		f2.filial as origem,
		f.filial as destino,
		nat.naturezaoperacao as natureza,
		nat.codnaturezaoperacao,
		count(*) as qtd,
		sum(nf.valortotal) as valor
	from tblnotafiscal nf
	inner join tblfilial f on (f.codfilial = nf.codfilial)
	inner join tblfilial f2 on (f2.codpessoa = nf.codpessoa)
	inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
	where nf.emitida = false
	and nf.emissao >= date_trunc('month', now() - '2 months'::interval)
	and nf.codpessoa in (select f2.codpessoa from tblfilial f2)
	and nf.nfeinutilizacao is null
	and nf.nfecancelamento is null
	group by f.filial, f2.filial, nat.naturezaoperacao, nat.codnaturezaoperacao
	order by 1, 2, 3, 4
),
emitidas as (
	select
		f.filial as origem,
		f2.filial as destino,
		nat.naturezaoperacao as natureza,
		nat.codnaturezaoperacao,
		nat.codnaturezaoperacaodevolucao,
		count(*) as qtd,
		sum(nf.valortotal) as valor
	from tblnotafiscal nf
	inner join tblfilial f on (f.codfilial = nf.codfilial)
	inner join tblfilial f2 on (f2.codpessoa = nf.codpessoa)
	inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
	where nf.emitida = true
	and nf.emissao >= date_trunc('month', now() - '2 months'::interval)
	and nf.codpessoa in (select f2.codpessoa from tblfilial f2)
	and nf.nfeinutilizacao is null
	and nf.nfecancelamento is null
	group by f.filial, f2.filial, nat.naturezaoperacao, nat.codnaturezaoperacao, nat.codnaturezaoperacaodevolucao
	order by 1, 2, 3, 4, 5
)
select
	e.valor as valor_e,
	l.valor as valor_l,
	coalesce(e.valor, 0) - coalesce(l.valor, 0) as valor_dif,
	coalesce(e.origem, l.origem) as origem,
	coalesce(e.destino, l.destino) as destino,
	e.natureza as natureza_e,
	l.natureza as natureza_l,
	e.qtd as qtd_e,
	l.qtd as qtd_l,
	coalesce(e.qtd, 0) - coalesce(l.qtd, 0) as qtd_dif
from emitidas e
full outer join lancadas l on (l.origem = e.origem and l.destino = e.destino and l.codnaturezaoperacao = e.codnaturezaoperacaodevolucao)


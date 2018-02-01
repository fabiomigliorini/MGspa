select 
	p.codpessoa
	, p.pessoa
	, fp.formapagamento
	, f.filial
	, sum(t.saldo)
	/*
	*/
from tblpessoa p
inner join tblformapagamento fp on (fp.codformapagamento = p.codformapagamento)
inner join tbltitulo t on (t.codpessoa = p.codpessoa)
inner join tblfilial f on (f.codfilial = t.codfilial)
where t.saldo <> 0
and fp.fechamento = true
and t.codnegocioformapagamento is not null
group by 
	p.codpessoa
	, p.pessoa
	, fp.formapagamento
	, f.filial
--limit 100

--select * from tbltitulo limit 50

--select * from tblformapagamento
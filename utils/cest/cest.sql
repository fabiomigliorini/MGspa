/* 
truncate table tblcest_2017_52;
select setval('tblcest_2017_52_id_seq', 1, false);
select ncm, ascii(substr(ncm, 11, 1)), * from tblcest_2017_52 where cest ilike '28.061.00%' order by ncm
select * from tblcest_2017_52 where ncm ilike '%,%'
*/

-- LIMPA AMARRACAO PRODUTO X CEST
update tblproduto set codcest = null where codcest is not null;

-- LIMPA TABELA CEST
delete from tblcest;
select setval('tblcest_codcest_seq', 1, false);

-- CARREGA TABELA CEST NOVA
insert into tblcest (ncm, cest, descricao, codncm)
SELECT replace(c.ncm, '.', ''), replace(c.cest, '.', ''), cast(c.descricao as varchar(600)), n.codncm
  FROM tblcest_2017_52 c
  left join tblncm n on (replace(c.ncm, '.', '') = n.ncm)
  order by c.cest
  ;
  
-- PASSA PARA TRIBUTADO TUDO QUE NAO TEM NO REGULAMENTO DE ICMS/ST DO MT	
update tblproduto
set codtributacao = 1, codcest = null 
where codproduto in (
	select p.codproduto
	from tblproduto p
	inner join tblncm n on (n.codncm = p.codncm)
	left join (
		select nri.ncm || '%' as ncm, ri.codregulamentoicmsstmt
		from tblregulamentoicmsstmt ri
		inner join tblncm nri on (nri.codncm = ri.codncm)
		) reg on (n.ncm ilike reg.ncm)
	where p.codtributacao = 3
	and reg.codregulamentoicmsstmt is null
)

-- PASSA PARA SUBSTITUICAO TUDO QUE TEM NO REGULAMENTO DE ICMS/ST DO MT	
update tblproduto
set codtributacao = 3
where codproduto in (
	select p.codproduto
	from tblproduto p
	inner join tblncm n on (n.codncm = p.codncm)
	inner join (
		select nri.ncm || '%' as ncm, ri.codregulamentoicmsstmt
		from tblregulamentoicmsstmt ri
		inner join tblncm nri on (nri.codncm = ri.codncm)
		) reg on (n.ncm ilike reg.ncm)
	where p.codtributacao != 3
	and reg.codregulamentoicmsstmt is not null
)

-- COPIA CEST DE UM PRODUTO, DO MESMO NCM, QUE JA TENHA O CEST PREENCHIDO
/*
update tblproduto
set codcest = (
	select p.codcest
	from tblproduto p
	where p.codncm = tblproduto.codncm
	and p.codcest is not null
	order by alteracao desc nulls last
	limit 1
)
where codtributacao = 3
and codcest is null
*/

-- PREENCHE CODCEST COMO PRIMEIRO DISPONIVEL COM A MELHOR COMBINACAO DE NCM
update tblproduto 
set codcest = (
	select c.codcest
	from tblcest c
	inner join tblncm n on (n.codncm = tblproduto.codncm and n.ncm ilike c.ncm || '%')
	order by char_length(c.ncm) desc
	limit 1
	)
where codtributacao = 3 
and codcest is null


-- LIMPA CEST DE TUDO QUE NAO FOR SUBSTITUICAO
update tblproduto set codcest = null where codtributacao != 3 and codcest is not null


-- PRODUTOS SEM CEST
select codncm, count(codproduto), min(codproduto) as codproduto, max(codproduto) as codproduto
from tblproduto
where codcest is null
and codtributacao = 3
group by codncm
order by 2 desc


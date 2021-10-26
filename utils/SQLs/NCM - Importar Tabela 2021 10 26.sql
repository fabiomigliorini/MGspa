-- bater quantidades de registros com XLS baixado da sefaz
select count(*) from tblncm2021 t 

-- retirar o . do NCM importado
update tblncm2021 set ncm = replace(ncm, '.', '') where ncm ilike '%.%' 

-- verificar se nao tem nenhum NCM duplicado
select ncm, count(*) from tblncm2021 group by ncm having count(*) > 1

-- cria novos ncms
with novos as (
	select n.ncm, concatenada, date_trunc('second', now()) as criacao, date_trunc('second', now()) as alteracao, false as bit, false as fecep 
	from tblncm2021 n
	left join tblncm a on (a.ncm = n.ncm)
	where a.codncm is null
)
INSERT INTO mgsis.tblncm
	(ncm, descricao, criacao, alteracao, "bit", fecep)
select *
from novos

-- marca NCMS que nao existem mais na tabela como inativos
update tblncm 
set inativo = date_trunc('second', now())
where tblncm.codncm in (
	select a.codncm 
	from tblncm a
	left join tblncm2021 n on (n.ncm = a.ncm)
	where n.ncm is null
)

-- atualiza descricao e marca como ativos ncms novos
with novos as (
	select a.codncm, a.descricao, n.concatenada 
	from tblncm a
	inner join tblncm2021 n on (n.ncm = a.ncm)
	where a.descricao != n.concatenada 
)
update tblncm
set descricao = novos.concatenada
, inativo = null
from novos
where tblncm.codncm = novos.codncm

-- limpa relacionamento de cest
update tblcest set codncm = null;

-- limpa relacionamento de ncmpai
update tblncm set codncmpai  = null;

-- apaga ncms inativos que nao estao amarrados a nenhum produto
delete from tblncm where tblncm.codncm in (
	select n.codncm
	from tblncm n 
	where n.inativo is not null
	and n.codncm not in (
		select p.codncm
		from tblproduto p 
		where p.codncm = n.codncm
	)
)

-- RODAR Ajusta Relacionamento Arvore NCM
Ajusta Relacionamentos Arvore NCM.sql

-- confere se quantidade de registros ativos está igual a tabela importada 
select 'tblncm' as tabela, inativo is not null as inativo, count(*) as registros 
from tblncm
group by inativo is not null
union all 
select 'tblncm2021', false, count(*) from tblncm2021 t
order by 1, 2

alter table tblncm add bit boolean not null default false;

update tblncm set bit = false;

update tblncm set bit = true where tblncm.ncm in (
	select b.ncm from tblbit b
)

alter table tblproduto drop column bit;

select * 
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
where n.bit = true
and p.codtributacao = 1
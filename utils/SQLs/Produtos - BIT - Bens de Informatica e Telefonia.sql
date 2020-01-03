/*
alter table tblproduto add bit boolean not null default false;

update tblproduto 
set bit = true
where produto ilike 'Toner %'
and codncm in (select n.codncm from tblncm n where n.ncm ilike '84%');

select SUBSTR(produto, 1, POSITION(' ' IN produto)) as primeira, min(codproduto) as codproduto, max(codproduto) as codproduto, count(*) as qtd
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
where p.codtipoproduto = 0
and n.ncm ilike '84%'
and p.bit = false
group by SUBSTR(produto, 1, POSITION(' ' IN produto)) 
order by 1 asc
limit 100

update tblproduto 
set bit = true
where codncm in (select n.codncm from tblncm n where n.ncm ilike '84099140')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8423%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8443%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '84702%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8470501%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8471%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '84723090%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '84729010%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8472902%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '84729030%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8472905%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8472909%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8473%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '84795000%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '84798999%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '84799090%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8501101%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '850440%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '850490%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8507%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85118030%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85123000%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8517%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85235%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '852550%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '852560%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8526%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '852841%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '852851%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8529101%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8529901%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85299020%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85299030%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85299040%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85299090%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85301010%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85308010%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8531%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8532211%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85322310%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85322410%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85322510%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85322910%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85323010%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85332120%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85340000%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85363000%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85364%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '853650%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85369030%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85369040%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8537101%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85371020%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85371030%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '85389010%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8541%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8542%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '8543%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '854470%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '900110%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '90138010%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9018%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9019%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '90221%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '90229090%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '90251990%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9026%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9027%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9028%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9029%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9030%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '9031%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '903289%')
or codncm in (select n.codncm from tblncm n where n.ncm ilike '90329010%')

select SUBSTR(produto, 1, POSITION(' ' IN produto)) as primeira, min(codproduto) as codproduto, max(codproduto) as codproduto, count(*) as qtd
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
where p.codtipoproduto = 0
and p.bit = true
group by SUBSTR(produto, 1, POSITION(' ' IN produto)) 
order by 1 asc
limit 100

update tblproduto set bit = true where produto ilike 'apresentador %'

*/

with bit as (
	select SUBSTR(produto, 1, POSITION(' ' IN produto)) as primeira, min(codproduto) as codproduto, max(codproduto) as codproduto, count(*) as qtd
	from tblproduto p
	inner join tblncm n on (n.codncm = p.codncm)
	where p.codtipoproduto = 0
	and p.bit = true
	group by SUBSTR(produto, 1, POSITION(' ' IN produto)) 
)
select p2.codproduto, p2.produto
from bit 
inner join tblproduto p2 on (p2.produto ilike bit.primeira || '%' and p2.bit = false)



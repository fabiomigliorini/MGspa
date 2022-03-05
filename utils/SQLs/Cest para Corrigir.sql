/*
select 
	p.codproduto, 
	n.ncm, 
	(
		select cest
		from tblcest c 
		where length(ncm) >= 4
		and n.ncm ilike c.ncm || '%' 
		order by length(ncm) DESC, cest
		limit 1
	)
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
where p.codtributacao = 1
limit 50


--update tblproduto set codtributacao = 2 where codncm = 7235

alter table tblproduto add codcestanterior bigint

update tblproduto set codcestanterior = codcest
*/

/*
update tblproduto set codcest = null;

update tblproduto set codcest = (
		select c.codcest
		from tblcest c 
		where length(c.ncm) >= 4
		and (
			select n.ncm
			from tblncm n 
			where n.codncm = tblproduto.codncm
		) ilike c.ncm || '%' 
		order by length(c.ncm) DESC, c.cest
		limit 1
	)
where codproduto between 54855 and 62021;

update tblproduto set codtributacao = 3 where codcest is not null;

update tblproduto set codtributacao = 1, codcest = null where codtributacao != 1 and codncm in (
14153
);

select count(*), n.ncm, n.codncm
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
where p.codtributacao = 3
and p.codcest is null
group by n.ncm, n.codncm 
order by 1 desc

select codtributacao, count(*) from tblproduto group by codtributacao

select count(*), n.ncm, n.codncm
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
where p.codtributacao != 3
and p.codcest is not null
group by n.ncm, n.codncm 
order by 1 desc



with p as (
	select p.codproduto, p.produto, p.codtributacao, n.ncm, c.cest
	from tblproduto p
	inner join tblncm n on (n.codncm = p.codncm)
	left join tblcest c on (c.codcest = p.codcest)
)
select 
	*, 
	(
		select count(c.codcest)
		from tblcest c 
		where p.ncm ilike c.ncm || '%' 
	) as opcoes
from p

select *
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
left join tblcest c on (c.codcest = p.codcest)
where p.codtributacao = 3 
and p.codcest is null

select *
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
left join tblcest c on (c.codcest = p.codcest)
where p.codtributacao != 3 
and p.codcest is not null
*/

-- update tblproduto set codcest = 134 where codncm = 17379

/*

update tblproduto 
set codcest = null, 
codtributacao = 1 --Tributado
where codncm in (select n.codncm from tblncm n where n.ncm ilike '96085000');

update tblproduto 
set codcest = (select c.codcest from tblcest c where c.cest ilike '2006100' and '96151100' ilike c.ncm || '%'), 
codtributacao = 3 --Substituicao
where codncm in (select n.codncm from tblncm n where n.ncm ilike '96151100');

select * from tbltributacao
*/

select p.codproduto, p.produto, n.codncm, n.ncm, c.ncm, c.cest, c.descricao
from tblproduto p
inner join tblncm n on (n.codncm = p.codncm)
inner join tblcest c on (c.codcest = p.codcest)
where n.ncm != c.ncm
and p.codtipoproduto = 0
and p.inativo is null
and p.codncm not in (
14214,
14213,
14212,
14188,
14187,
14181,
14060,
14059,
14058,
14055,
14054,
14053,
14052,
13729,
13451,
13450,
13447,
12933,
12932,
12710,
12718,
12709,
12700,
17568,
12533,
14448,
12421,
12419,
12415,
12414,
12377,
12372,
12370,
12311,
12301,
12201,
12300,
12197,
12190,
12183,
11925,
11781,
11729,
11728,
11706,
11704,
11703,
11688,
11693,
11178,
11173,
10295,
10285,
10283,
10284,
10236,
10232,
10227,
10229,
10216,
9898,
9736,
9729,
9726,
9099,
9094,
9092,
9090,
9087,
9085,
9082,
9060,
7223,
7222,
7180,
7179,
7081,
7000,
6996,
6989,
6988,
6984,
6976,
6714,
6713,
6700,
6698,
6696,
1461,
457,
1341,
1346,
1349,
1350,
1443,
1489,
6699,
1836,
5035,
5040,
5042,
5170,
5192,
5245,
5243,
5242,
5244,
5249,
5257,
5261,
5265,
5384,
5389,
5397,
17380,
17379,
0
)
order by n.ncm, c.cest, p.produto
--limit 100
/*
select pe.fantasia, nti.cest, nti.ncm, nt.emissao, nti.cprod, nti.xprod, nti.codnfeterceiroitem
from tblncm n 
inner join tblproduto p on (p.codncm = n.codncm)
inner join tblprodutobarra pb on (pb.codproduto = p.codproduto)
inner join tblnfeterceiroitem nti on (nti.codprodutobarra = pb.codprodutobarra)
inner join tblnfeterceiro nt on (nt.codnfeterceiro = nti.codnfeterceiro)
inner join tblpessoa pe on (pe.codpessoa = nt.codpessoa)
where n.ncm = '96159000'
order by nt.emissao desc, nti.cprod, nti.xprod
*/
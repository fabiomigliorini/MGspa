
delete from tblveiculo

-- Cavalos
insert into tblveiculo (codveiculotipo, veiculo, placa, codestado)
select distinct
	1 as codveiculotipo,
	'Cavalo ' || placa as veiculo,
	placa as placa,
	t.codestadoplaca as codestado
from tblnotafiscal t
where placa is not null 
and placa != 'BWY9890'
order by placa

-- Reboque Dianteiro
insert into tblveiculo (codveiculotipo, veiculo, placa, codestado)
select distinct
	2 as codveiculotipo,
	'Reboque Dianteiro ' || placa as veiculo,
	'RED' || right(placa, 4) as placa,
	t.codestadoplaca as codestado
from tblnotafiscal t
where placa is not null 
and placa != 'BWY9890'
order by placa

-- Reboque Traseiro
insert into tblveiculo (codveiculotipo, veiculo, placa, codestado)
select distinct
	2 as codveiculotipo,
	'Reboque Traseiro ' || placa as veiculo,
	'RET' || right(placa, 4) as placa,
	t.codestadoplaca as codestado
from tblnotafiscal t
where placa is not null 
and placa != 'BWY9890'
order by placa

-- Conjunto
insert into tblveiculoconjunto (veiculoconjunto)
select distinct
	'Conjunto ' || placa as veiculo
from tblnotafiscal t
where placa is not null 
and placa != 'BWY9890'

-- Conjunto / Veiculo
insert into tblveiculoconjuntoveiculo (codveiculoconjunto, codveiculo)
select c.codveiculoconjunto, v.codveiculo
from tblveiculoconjunto c 
inner join tblveiculo v on (right(v.placa, 4) = right(veiculoconjunto, 4) )


update tblnotafiscal set pesobruto = 300 where codnotafiscal = 1709467
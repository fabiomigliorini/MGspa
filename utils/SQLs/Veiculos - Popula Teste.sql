-- Cria Tipo Veiculo
insert into tblveiculotipo (codveiculotipo, veiculotipo, tracao, reboque, tipocarroceria, tiporodado, criacao, alteracao) 
values (1, 'Cavalo Mecanico', true, false, 0, 3, date_trunc('second', now()), date_trunc('second', now()));

insert into tblveiculotipo (codveiculotipo, veiculotipo, tracao, reboque, tipocarroceria, tiporodado, criacao, alteracao) 
values (2, 'Reboque Graneleiro', false, true, 3, 6, date_trunc('second', now()), date_trunc('second', now()));

-- STatus MDFe
insert into tblmdfestatus (codmdfestatus, mdfestatus, sigla, criacao, alteracao)
values (1, 'Em Digitação', 'DIG', date_trunc('second', now()), date_trunc('second', now()));

insert into tblmdfestatus (codmdfestatus, mdfestatus, sigla, criacao, alteracao)
values (2, 'Transmitido', 'TRA', date_trunc('second', now()), date_trunc('second', now()));

insert into tblmdfestatus (codmdfestatus, mdfestatus, sigla, criacao, alteracao)
values (3, 'Autorizado', 'AUT', date_trunc('second', now()), date_trunc('second', now()));

insert into tblmdfestatus (codmdfestatus, mdfestatus, sigla, criacao, alteracao)
values (4, 'Não Autorizado', 'NAO', date_trunc('second', now()), date_trunc('second', now()));

insert into tblmdfestatus (codmdfestatus, mdfestatus, sigla, criacao, alteracao)
values (5, 'Encerrado', 'ENC', date_trunc('second', now()), date_trunc('second', now()));

insert into tblmdfestatus (codmdfestatus, mdfestatus, sigla, criacao, alteracao)
values (9, 'Cancelado', 'CAN', date_trunc('second', now()), date_trunc('second', now()));

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


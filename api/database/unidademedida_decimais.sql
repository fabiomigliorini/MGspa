-- Casas decimais da quantidade por unidade de medida (TASK-53).
--
-- O PDV aceitava fracao em qualquer produto, e unidade, pacote, rolo etc.
-- recebiam 1,3 ou 0,2 por erro de digitacao. Com a coluna, a quantidade
-- digitada no negocios respeita a unidade: 0 recusa fracao, 2 aceita 0,53 MT
-- (53 cm), 3 aceita 0,712 KG (712 g). O maximo e' 3, as casas da quantidade
-- em tblnegocioprodutobarra (numeric(14,3)).
--
-- A trava e' so' nas telas: a nota de terceiro importada continua entrando com
-- a quantidade do fornecedor.
--
-- Idempotente: ADD COLUMN IF NOT EXISTS; o UPDATE so' acerta as siglas
-- fracionaveis. Transacional.

\set ON_ERROR_STOP on
BEGIN;

alter table tblunidademedida add column if not exists decimais smallint not null default 0;

update tblunidademedida set decimais = 2 where sigla in ('MT', 'M2', 'R$');
update tblunidademedida set decimais = 3 where sigla in ('KG', 'LT', 'TO');

-- Conferencia (esperado: MT, M2 e R$ com 2; KG, LT e TO com 3; o resto 0)
select sigla, unidademedida, decimais from tblunidademedida order by decimais desc, sigla;

COMMIT;

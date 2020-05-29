/*
-- VERIFICA SE VARIACAO ESTA ATIVA
select * 
from tblprodutovariacao pv
inner join tblproduto p on (p.codproduto = pv.codproduto)
where coalesce(pv.inativo, p.inativo) is null
and pv.codprodutovariacao in (
47088
, 16165
, 40301
)

-- INATIVA VARIACAO
UPDATE TBLPRODUTOVARIACAO SET INATIVO = date_trunc('seconds', NOW()) WHERE INATIVO IS NULL AND CODPRODUTOVARIACAO IN (
47088
, 16165
, 40301
)

-- VERIFICA SE VARIACAO ESTA DESCONTINUADA
select * 
from tblprodutovariacao pv
where pv.descontinuado is null
and pv.codprodutovariacao in (
18598
, 18599
, 7331
)
*/

-- DESCONTINUA VARIACAO
UPDATE TBLPRODUTOVARIACAO SET DESCONTINUADO = date_trunc('seconds', NOW()) WHERE DESCONTINUADO IS NULL AND CODPRODUTOVARIACAO IN (
00005702
,00000080
,00016102
,00023145
,00002026
,00015840
,00019364
)
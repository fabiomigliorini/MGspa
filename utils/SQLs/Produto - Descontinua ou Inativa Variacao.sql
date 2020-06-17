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
74865
)

/*
-- INATIVA VARIACAO
UPDATE TBLPRODUTOVARIACAO SET INATIVO = date_trunc('seconds', NOW()) WHERE INATIVO IS NULL AND CODPRODUTOVARIACAO IN (
62696,
62698,
62700,
62701,
62702,
62703
)
*/
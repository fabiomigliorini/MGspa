/*
select * 
from tblproduto p
where p.codncm in (select n.codncm from tblncm n where n.ncm = '39199000')
*/

update tblproduto 
set codncm = (select n.codncm from tblncm n where n.ncm = '85061020')
where codncm in (select n.codncm from tblncm n where n.ncm = '85061010')

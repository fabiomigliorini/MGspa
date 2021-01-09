update tblliquidacaotitulo 
set codportador = (select p.codportador from tblportador p where p.portador ilike '%carteira%') 
where codliquidacaotitulo = 72440

update tblmovimentotitulo
set codportador = lt.codportador
from tblliquidacaotitulo lt
where lt.codliquidacaotitulo = tblmovimentotitulo.codliquidacaotitulo
and lt.codportador != tblmovimentotitulo.codportador

commit
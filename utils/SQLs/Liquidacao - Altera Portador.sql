update tblliquidacaotitulo 
set codportador = (select p.codportador from tblportador p where p.portador ilike '%gere%') 
where codliquidacaotitulo = 80852

update tblmovimentotitulo
set codportador = lt.codportador
from tblliquidacaotitulo lt
where lt.codliquidacaotitulo = tblmovimentotitulo.codliquidacaotitulo
and lt.codportador != tblmovimentotitulo.codportador

commit
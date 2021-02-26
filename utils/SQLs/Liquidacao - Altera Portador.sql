update tblliquidacaotitulo 
set codportador = (select p.codportador from tblportador p where p.portador ilike '%387%') 
where codliquidacaotitulo = 75067

update tblmovimentotitulo
set codportador = lt.codportador
from tblliquidacaotitulo lt
where lt.codliquidacaotitulo = tblmovimentotitulo.codliquidacaotitulo
and lt.codportador != tblmovimentotitulo.codportador

commit
--delete from tblnfeterceiroitem where codnfeterceiro  = 16717

-- DESCONTO e FRETE
select * from tblnfeterceiroitem where codnfeterceiro  = 16717
-- OUtRAS
select * from tblnfeterceiroitem where codnfeterceiro  = 26791
-- SEGURO
select * from tblnfeterceiroitem where codnfeterceiro  = 22839

update tblnfeterceiroitem set modalidadeicmsgarantido = false,  margem = 30 where codnfeterceiro  = 22839


-- OUTRAS
select * from tblnotafiscal where emitida = false and valoroutras > 0 order by emissao desc
select * from tblnotafiscal where emitida = false and valorseguro > 0 order by emissao desc
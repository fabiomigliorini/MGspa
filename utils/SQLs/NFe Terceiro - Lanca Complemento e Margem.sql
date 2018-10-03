select * from tblnfeterceiroitem where codnfeterceiro = 20214

-- ISSAM - Material de Natal, Valor Pedido / 0.35
update tblnfeterceiroitem set complemento = (vprod / 0.245) - vprod, margem = 90 where codnfeterceiro = 20214

select sum(vprod) as prod, sum(ipivipi) as ipi, sum(complemento) as comlemento from tblnfeterceiroitem where codnfeterceiro = 20214
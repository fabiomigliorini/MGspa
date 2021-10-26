
select * from tblcest where codncm is null

update tblproduto 
set codncm = (select n.codncm from tblproduto n where n.ncm = tblproduto.ncm)
where tblproduto.codncm is null

select ncm, count(*) from tblncm group by ncm having count(*) > 1

update tblproduto 
set codncm = (select n.codncm from tblncm n where n.ncm = lpad(cast(tblproduto.ncm as varchar), 8, '0'))

select * from tbltributacao
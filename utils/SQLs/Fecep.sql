--update tblnotafiscal set nfeautorizacao =null, nfedataautorizacao = null where codfilial = 102 and modelo = 65 and emitida = true and numero in (516903)

-- cria campo fecep
alter table tblncm add fecep boolean not null default false

-- de acordo com listagem que Vanessa Passou
update tblncm set fecep = false where fecep = true; 
update tblncm set fecep = true where ncm ilike '330300%';
update tblncm set fecep = true where ncm ilike '3304%';
update tblncm set fecep = true where ncm ilike '3305%';
update tblncm set fecep = true where ncm ilike '3307%';
update tblncm set fecep = false where ncm ilike '33049990';
update tblncm set fecep = false where ncm ilike '33051000';
update tblncm set fecep = false where ncm ilike '33071000';
update tblncm set fecep = false where ncm ilike '33079000';
update tblncm set fecep = false where ncm ilike '330720%';

-- Produtos que se enquadram
select p.codproduto, p.produto
from tblproduto p 
inner join tblncm n on (n.codncm = p.codncm)
where n.fecep = true
order by p.produto

select nf.codfilial, nf.codnotafiscal, nf.nfechave, nat.naturezaoperacao, nf.emissao, nfpb.valortotal, nfpb.valordesconto, p.codproduto, p.produto, n.ncm 
from tblnotafiscal nf
inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnotafiscal = nf.codnotafiscal)
inner join tblprodutobarra pb on (pb.codprodutobarra = nfpb.codprodutobarra)
inner join tblproduto p on (p.codproduto = pb.codproduto)
inner join tblncm n on (n.codncm = p.codncm)
where nf.emissao >= '2020-01-01'
and nf.emitida = true 
and nf.nfeautorizacao is not null 
and nf.nfecancelamento is null
and nf.nfeinutilizacao is null
and n.fecep = true
and p.codtributacao = 1 -- tributado
and (nat.venda = true or nat.vendadevolucao = true)
order by nf.codfilial, nf.emissao asc, nfpb.codnotafiscalprodutobarra 



/* 
insert into tblproduto
SELECT 200000, produto, referencia, codunidademedida, codsubgrupoproduto,
       codmarca, preco, importado, codtributacao, inativo, codtipoproduto,
       site, descricaosite, alteracao, codusuarioalteracao, criacao,
       codusuariocriacao, codncm, codcest, observacoes, codopencart,
       codopencartvariacao, peso, altura, largura, profundidade, vendesite,
       metakeywordsite, metadescriptionsite, codprodutoimagem, dataultimacompra,
       estoqueminimo, estoquemaximo, vendadiaquantidadeprevisao, vendaultimocalculo,
       vendabimestrequantidade, vendabimestrevalor, vendasemestrequantidade,
       vendasemestrevalor, vendaanoquantidade, vendaanovalor, vendaanopercentual,
       abcignorar, abcposicao, abccategoria, custoultimacompra
  FROM tblproduto
 where codproduto = 20000000;

update tblprodutoimagem set codproduto = 200000 where codproduto = 20000000;

update tblprodutovariacao set codproduto = 200000 where codproduto = 20000000;

update tblprodutobarra set codproduto = 200000 where codproduto = 20000000;

update tblpranchetaproduto set codproduto = 200000 where codproduto = 20000000;

update tblprodutoembalagem set codproduto = 200000 where codproduto = 20000000;
 
delete from tblproduto where codproduto = 20000000;
 
select * from tblproduto where codproduto > 900000 order by codproduto;


*/

/*
DO
$$
DECLARE 
	codigo_novo int;
BEGIN
 FOR i IN 1..10 LOOP
  SELECT codigo_novo = i;
  RAISE NOTICE '%', codigo_novo; -- i will take on the values 1,2,3,4,5,6,7,8,9,10 within the loop
 END LOOP;
END
$$
*/
/*
do $$
declare
    codigo_novo int = null;
    codigo_velho int = null;
begin

	FOR i IN 5..303 LOOP
		codigo_novo = 200000 + i;
		codigo_velho = 20000000 + i;
		RAISE NOTICE 'Alterando de % para %!', codigo_velho, codigo_novo; -- i will take on the values 1,2,3,4,5,6,7,8,9,10 within the loop

		insert into tblproduto
		SELECT codigo_novo, produto, referencia, codunidademedida, codsubgrupoproduto,
		       codmarca, preco, importado, codtributacao, inativo, codtipoproduto,
		       site, descricaosite, alteracao, codusuarioalteracao, criacao,
		       codusuariocriacao, codncm, codcest, observacoes, codopencart,
		       codopencartvariacao, peso, altura, largura, profundidade, vendesite,
		       metakeywordsite, metadescriptionsite, codprodutoimagem, dataultimacompra,
		       estoqueminimo, estoquemaximo, vendadiaquantidadeprevisao, vendaultimocalculo,
		       vendabimestrequantidade, vendabimestrevalor, vendasemestrequantidade,
		       vendasemestrevalor, vendaanoquantidade, vendaanovalor, vendaanopercentual,
		       abcignorar, abcposicao, abccategoria, custoultimacompra
		  FROM tblproduto
		 where codproduto = codigo_velho;

		update tblprodutoimagem set codproduto = codigo_novo where codproduto = codigo_velho;

		update tblprodutovariacao set codproduto = codigo_novo where codproduto = codigo_velho;

		update tblprodutobarra set codproduto = codigo_novo where codproduto = codigo_velho;

		update tblpranchetaproduto set codproduto = codigo_novo where codproduto = codigo_velho;

		update tblprodutoembalagem set codproduto = codigo_novo where codproduto = codigo_velho;
		 
		delete from tblproduto where codproduto = codigo_velho;
		
	END LOOP;

    -- do stuff;

end $$;
*/

/*
select p.codproduto, p2.codproduto
from tblproduto p 
inner join tblproduto p2 on (p2.codproduto = (p.codproduto - 19800000))
where p.codproduto >= 20000000 
and p.codproduto <= 29999999
*/
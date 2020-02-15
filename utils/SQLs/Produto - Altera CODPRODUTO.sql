-- busca primeiro CODPRODUTO Disponivel
SELECT  codproduto + 1
FROM    tblproduto mo
WHERE   NOT EXISTS
        (
        SELECT  NULL
        FROM    tblproduto mi 
        WHERE   mi.codproduto = mo.codproduto + 1
        )
ORDER BY
        codproduto
LIMIT 1

-- altera
-- update tblproduto set codproduto = 20 where codproduto = 931000
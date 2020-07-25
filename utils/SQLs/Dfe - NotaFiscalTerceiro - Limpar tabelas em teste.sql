delete from tbldistribuicaodfeevento where 1=1;
delete from tbldfeevento where 1=1;
delete from tbldistribuicaodfe where 1=1;
delete from tbldfetipo where 1=1;
delete from tblnotafiscalterceiroduplicata where 1=1;
delete from tblnotafiscalterceiropagamento where 1=1;
delete from tblnotafiscalterceiroprodutobarra where 1=1;
delete from tblnotafiscalterceiroitem where 1=1;
delete from tblnotafiscalterceirogrupo where 1=1;
delete from tblnotafiscalterceiro where 1=1;
UPDATE tblfilial SET dfe = false where dfe = true;
UPDATE tblfilial SET dfe = TRUE WHERE codfilial IN (101, 102, 103, 104, 201, 401);
commit

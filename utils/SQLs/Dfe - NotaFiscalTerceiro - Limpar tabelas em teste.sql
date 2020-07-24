delete from tbldistribuicaodfeevento ;
delete from tbldfeevento ;
delete from tbldistribuicaodfe ;
delete from tbldfetipo;
delete from tblnotafiscalterceiroduplicata ;
delete from tblnotafiscalterceiropagamento;
delete from tblnotafiscalterceiroprodutobarra ;
delete from tblnotafiscalterceiroitem ;
delete from tblnotafiscalterceirogrupo;
delete from tblnotafiscalterceiro;
UPDATE tblfilial SET dfe = FALSE;
UPDATE tblfilial SET dfe = TRUE WHERE codfilial IN (101, 102, 103, 104, 201, 401, 402);
commit

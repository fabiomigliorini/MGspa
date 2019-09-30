update tblnfeterceiroitem set modalidadeicmsgarantido = true where modalidadeicmsgarantido = false

ALTER TABLE tblnfeterceiroitem
   ALTER COLUMN modalidadeicmsgarantido SET DEFAULT false;

update tblnfeterceiroitem set modalidadeicmsgarantido = false where codnfeterceiroitem in (
	select i.codnfeterceiroitem
	from tblnfeterceiro n
	left join tblnfeterceiroitem i on (i.codnfeterceiro = n.codnfeterceiro)
	where n.codnotafiscal is null
	and n.ignorada = false
	and coalesce(n.indmanifestacao, 0) not in (210240, 210220)
	and coalesce(n.indsituacao, 0) not in (2, 3)
)
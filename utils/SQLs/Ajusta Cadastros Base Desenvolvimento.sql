--altera caminho monitor ACBR
update tblfilial 
set acbrnfemonitorcaminho = null
, acbrnfemonitorcaminhorede = null
, acbrnfemonitorip = null
, acbrnfemonitorporta = null
, nfeambiente = 2;

-- altera token nfce
update tblfilial 
set nfcetoken = 'fafb6194ce514a0da33e01ce0d717b51'
, nfcetokenid = '000002'
where codempresa = 1;

--altera token ibpt
update tblfilial set tokenibpt = '3XQBW5YSinDuYoknE4wmFR4UylZiX0BpidbqhA4BJ3dJVz31A8z4PvvTAmpgzNe8' where codfilial = 199;
update tblfilial set tokenibpt = '3XQBW5YSinDuYoknE4wmFR4UylZiX0BpidbqhA4BJ3dJVz31A8z4PvvTAmpgzNe8' where codfilial = 101;
update tblfilial set tokenibpt = 'esMRMBvcrErSLwCOnPk6gxBz6wBU_hDH589s5Wyv4FD-7KoWYyF1dxX7tH-KJRI9' where codfilial = 102;
update tblfilial set tokenibpt = 'LtZ60kISB3lrEZAEAaTGl5oJawDp0ikCvNB8J6satr7_cAhf9-M_t-X6B3pB3lsb' where codfilial = 103;
update tblfilial set tokenibpt = 'b9puOsEqfpwJq4YpLpREDs34qazA_c6GBwcEqyYXSjd-QDobWciFzZw9p_TsQtFK' where codfilial = 104;

--marca como inutilizadas notas pendentes
update tblnotafiscal 
set nfeinutilizacao = '999', nfedatainutilizacao = '2018-12-28'
where emitida = true and nfeinutilizacao is null and nfeautorizacao is null and nfecancelamento is null;

--altera email clientes para envio xml
update tblpessoa set email = 'nfe@mgpapelaria.com.br', emailnfe = null, emailcobranca = null;

--altera senha para "baseteste" e para impressora matricial do deposito
update tblusuario set senha = '$1$k8wt4L/C$/xxhrvZ2z4DroCR6dUszJ/', impressoramatricial = null, impressoratermica = null, impressoratelanegocio = null;

--limpa jobs
delete from tbljobs;
delete from tbljobsspa;

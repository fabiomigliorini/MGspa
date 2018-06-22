--altera caminho monitor ACBR
update tblfilial 
set acbrnfemonitorcaminho = null
, acbrnfemonitorcaminhorede = null
, acbrnfemonitorip = null
, acbrnfemonitorporta = null
, nfeambiente = 2;

update tblfilial 
set nfcetoken = 'fafb6194ce514a0da33e01ce0d717b51'
, nfcetokenid = '000002'
where codempresa = 1;

--altera email clientes para envio xml
update tblpessoa set email = 'nfe@mgpapelaria.com.br', emailnfe = null, emailcobranca = null;

--altera senha para "baseteste" e para impressora matricial do deposito
update tblusuario set senha = '$1$k8wt4L/C$/xxhrvZ2z4DroCR6dUszJ/', impressoramatricial = null, impressoratermica = null, impressoratelanegocio = null;

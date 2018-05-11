--altera senha para "baseteste" e para impressora matricial do deposito
update tblusuario set senha = '$1$k8wt4L/C$/xxhrvZ2z4DroCR6dUszJ/', impressoramatricial = 'deposito-faturamento-epson-matricial';

--altera caminho monitor ACBR
update tblfilial 
set acbrnfemonitorcaminho = 'C:\ACBrNFeMonitor'
, acbrnfemonitorcaminhorede = 'http://192.168.1.198:8080/'
, acbrnfemonitorip = '192.168.1.198'
, acbrnfemonitorporta = '3436'
, nfeambiente = 2
;

--altera email clientes para envio xml
update tblpessoa set email = 'nfe@mgpapelaria.com.br', emailnfe = null, emailcobranca = null;

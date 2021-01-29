select codpessoa, pessoa, fantasia, endereco, numero
from tblpessoa 
where numero is null 
order by codpessoa

select codpessoa, pessoa, fantasia, telefone1, telefone2, telefone3 
from tblpessoa 
where char_length(telefone1) <= 8 
or char_length(telefone2) <= 8
or char_length(telefone3) <= 8
order by codpessoa

select codpessoa, pessoa, fantasia, email, emailcobranca, emailnfe 
from tblpessoa 
where email not ilike '%@%.%'
or email is null
or emailcobranca not ilike '%@%.%'
or emailnfe not ilike '%@%.%'
order by codpessoa

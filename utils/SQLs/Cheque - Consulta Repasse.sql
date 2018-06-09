select c.contacorrente, c.valor, c.vencimento, cr.codportador, cr.data--, cr.*
from tblcheque c 
left join tblchequerepassecheque crc on (crc.codcheque = c.codcheque)
left join tblchequerepasse cr on (cr.codchequerepasse = crc.codchequerepasse)
where c.contacorrente = '01033-0'
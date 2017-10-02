select 
    t.codtitulo
    , t.numero
    , t.debito
    , t.vencimento
    , bmo.motivo
    , br.dataretorno
    , br.despesas
    , br.jurosmora
    , br.pagamento
from tblboletoretorno br
inner join tbltitulo t on (t.codtitulo = br.codtitulo)
left join tblboletomotivoocorrencia bmo on (bmo.codboletomotivoocorrencia = br.codboletomotivoocorrencia)
where t.codpessoa = 1355
--where bmo.codboletotipoocorrencia = 40
order by t.vencimento, t.numero, t.codtitulo, br.codboletomotivoocorrencia
limit 50

/*
select * from tblboletomotivoocorrencia order by codboletomotivoocorrencia

insert into tblboletomotivoocorrencia (codboletomotivoocorrencia, motivo, codboletotipoocorrencia) values (129078, 'Pagador alega que faturamento e indevido', 29)

select * 
from tblboletoretorno br
left join tblboletomotivoocorrencia bmo on (bmo.codboletomotivoocorrencia = br.codboletomotivoocorrencia)
where bmo.codboletomotivoocorrencia is null


select * from tblboletotipoocorrencia order by codboletotipoocorrencia

--insert into tblboletotipoocorrencia (codboletotipoocorrencia, ocorrencia) values (29, 'Ocorrência do Pagador')

*/

-- Observacao do titulo: de 255 para 2000 caracteres (TASK-247).
--
-- 255 nao cabia uma lista de materiais na observacao (titulo 645726 ficou com
-- exatamente 255; em 21/10/2026, 108 titulos estavam com 250 ou mais).
--
-- Aumentar varchar no Postgres e' so' metadado: nao reescreve a tabela nem
-- trava por tempo. Nenhuma view depende da coluna.

begin;

alter table tbltitulo alter column observacao type varchar(2000);

-- Conferencia (esperado: 2000)
select character_maximum_length
from information_schema.columns
where table_name = 'tbltitulo' and column_name = 'observacao';

commit;

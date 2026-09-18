-- TASK-100 / TASK-43: wizard Receber do PDV, maquininha no pagamento e cheque
-- Rodar em produção antes do deploy do app negocios + api.

-- maquininha usada (serial) e dados do cheque ficam no próprio pagamento (sync offline)
alter table tblnegocioformapagamento
  add column serialmaquineta varchar(50),
  add column cmc7 varchar(50),
  add column chequevencimento date,
  add column chequecnpj numeric(14, 0),
  add column chequeemitente varchar(100);

-- cheque criado no fechamento aponta para o pagamento que o originou
alter table tblcheque add column codnegocioformapagamento bigint;
alter table tblcheque
  add constraint fk_tblcheque_tblnegocioformapagamento
  foreign key (codnegocioformapagamento) references tblnegocioformapagamento (codnegocioformapagamento)
  on update cascade;
create index idx_tblcheque_codnegocioformapagamento on tblcheque (codnegocioformapagamento);

-- tblsauruspinpad.serial passa a ser o número de série físico do aparelho
-- (até aqui repetia o id/uuid da Saurus; o PDV pede o serial no primeiro uso)
update tblsauruspinpad set serial = null where serial = id::text;

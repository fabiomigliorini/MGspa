-- TASK-180: sync seguro entre aparelhos do patio. Versao otimista da carga —
-- incrementada a cada gravacao no servidor; o aparelho manda a versao da qual
-- partiu e, se o servidor ja estiver em outra, leva 409 (CargaConflitoException).
-- Ver CargaService::sincronizar.
alter table tblcarga add column if not exists versao integer not null default 1;
comment on column tblcarga.versao is
  'Versao otimista (sync offline do patio). Ver CargaService::sincronizar.';

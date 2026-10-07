-- TASK-201: safra nova copia os talhoes de outra safra. O plantio copiado nasce
-- so com o desenho (nome, geometria, cor, area do cadastro do talhao); a
-- variedade e informada depois. Enquanto nula, o talhao aparece "Variedade
-- pendente" e nao pode ser finalizado (PlantioController::hacolhido).
-- Idempotente: drop not null em coluna ja nullable nao faz nada.
alter table tblplantio alter column codvariedade drop not null;
comment on column tblplantio.codvariedade is
  'Variedade plantada. Nula so no plantio copiado de outra safra, ate ser informada (TASK-201).';

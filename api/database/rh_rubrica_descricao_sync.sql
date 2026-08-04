-- =====================================================================
-- Modulo RH (Mg\Rh) — Backfill: sincroniza a descricao denormalizada dos
-- vinculos com o nome atual do catalogo de rubricas.
--
-- POR QUE:
--   tblcolaboradorrubrica.descricao e' NOT NULL e denormalizada (gravada por
--   snapshot na criacao do vinculo). Ate agora, renomear uma rubrica em
--   tblrubrica NAO propagava para os vinculos, entao o card de rubricas do
--   colaborador continuava exibindo o nome antigo.
--
--   A coluna NAO pode ser abandonada: e' a unica fonte para as rubricas
--   avulsas (codrubrica is null), e' a ordenacao da lista, e' o que sai nos
--   recibos em PDF e e' chave de idempotencia ao duplicar periodo
--   (PeriodoService::duplicar -> firstOrNew por descricao+tipovalor).
--
--   A partir de agora RubricaController::update propaga o rename. Este script
--   corrige o que ja divergiu antes dessa mudanca.
--
-- ESCOPO: TODOS os vinculos, inclusive de periodos ja encerrados/pagos —
--   catalogo e cards nunca devem divergir. Recibos reimpressos de periodos
--   antigos passam a sair com o nome novo.
--
-- Avulsas (codrubrica is null) ficam intocadas: o join nao as alcanca.
--
-- Rodar manualmente no psql (nao ha migrations neste projeto).
-- =====================================================================

begin;

-- Conferencia ANTES: quantos vinculos estao com nome diferente do catalogo
select count(*) as divergentes
from tblcolaboradorrubrica cr
inner join tblrubrica r on r.codrubrica = cr.codrubrica
where cr.descricao is distinct from r.descricao;

update tblcolaboradorrubrica cr
set descricao = r.descricao,
    alteracao = now()
from tblrubrica r
where r.codrubrica = cr.codrubrica
  and cr.descricao is distinct from r.descricao;

-- Conferencia DEPOIS: tem que voltar 0
select count(*) as divergentes
from tblcolaboradorrubrica cr
inner join tblrubrica r on r.codrubrica = cr.codrubrica
where cr.descricao is distinct from r.descricao;

commit;
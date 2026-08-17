-- Saneamento da protocolodata de tblinutilizacao.
--
-- A tela de inutilizacoes agrupa o historico por ano/mes, e a data usada e a do ato na
-- SEFAZ (protocolodata). Duas sujeiras impediam isso:
--
--   1. Linhas sem protocolodata (importadas do sistema antigo), que caem fora de qualquer ano.
--   2. Linhas com protocolodata em 1969 — epoch mal convertido na importacao, que criavam
--      uma aba de ano inteira so de lixo.
--
-- Em ambos os casos a melhor data disponivel e a criacao (a data em que o registro entrou
-- no sistema); alteracao e o ultimo recurso, para as poucas linhas sem criacao.
--
-- Depois disso protocolodata vira NOT NULL e o backend passa a usar a coluna direto, sem
-- coalesce espalhado por consulta.

begin;

-- Conferencia ANTES (esperado em 14/08/2026: 2 sem data, 168 em 1969)
select count(*) filter (where protocolodata is null) as sem_data,
       count(*) filter (where extract(year from protocolodata) < 2000) as ano_lixo,
       count(*) as total
from tblinutilizacao;

update tblinutilizacao
set protocolodata = coalesce(criacao, alteracao)
where protocolodata is null
   or extract(year from protocolodata) < 2000;

alter table tblinutilizacao alter column protocolodata set not null;

-- Conferencia DEPOIS (esperado: 0, 0, mesmo total)
select count(*) filter (where protocolodata is null) as sem_data,
       count(*) filter (where extract(year from protocolodata) < 2000) as ano_lixo,
       count(*) as total
from tblinutilizacao;

commit;

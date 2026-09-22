-- =============================================================================
-- App Agro — Índices da listagem/relatório de romaneios (cargas)
-- =============================================================================
-- A tela /cargas consulta o HISTÓRICO no servidor (todas as safras, qualquer
-- período), diferente do Pátio, que lê o Dexie da safra ativa. Dois recortes
-- novos passam a ser quentes e não tinham índice:
--
--   1) PERÍODO  — `data` é a ordem default da pesquisa (`['-data']`) e agora
--                 também é filtro de range (data_inicio/data_fim). Sem índice,
--                 todo GET vira seq scan + sort da tblcarga inteira.
--
--   2) ORIGEM/DESTINO — o filtro por unidade/talhão/contrato é um EXISTS em
--                 tblcargaponto (whereHas; join duplicaria a linha da carga e
--                 quebraria a paginação). Sem índice na FK, o EXISTS varre a
--                 tabela de pontos a cada carga candidata.
--
-- Os índices de ponto são PARCIAIS porque `contatipo` é excludente: cada linha
-- de tblcargaponto preenche UMA das três FKs e deixa as outras duas NULL
-- (CargaService::sincronizarPontos). Índice parcial guarda só as linhas úteis.
--
-- `placa`/`motorista` não ganham índice de propósito: são filtros `ilike '%x%'`,
-- que não usam B-tree, e o volume não justifica trigram.
--
-- Idempotente (IF NOT EXISTS) — pode rodar quantas vezes precisar.
--
--   docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < api/database/agro_carga_listagem.sql
-- =============================================================================

-- Ordenação default e filtro de período.
CREATE INDEX IF NOT EXISTS ix_carga_data
    ON tblcarga (data);

-- Recorte mais comum da tela: uma safra dentro de um período.
CREATE INDEX IF NOT EXISTS ix_carga_safra_data
    ON tblcarga (codsafra, data);

-- Filtros de origem/destino (EXISTS em tblcargaponto).
CREATE INDEX IF NOT EXISTS ix_cargaponto_unidade
    ON tblcargaponto (codunidadearmazenadora)
    WHERE codunidadearmazenadora IS NOT NULL;

CREATE INDEX IF NOT EXISTS ix_cargaponto_plantio
    ON tblcargaponto (codplantio)
    WHERE codplantio IS NOT NULL;

CREATE INDEX IF NOT EXISTS ix_cargaponto_contrato
    ON tblcargaponto (codcontrato)
    WHERE codcontrato IS NOT NULL;

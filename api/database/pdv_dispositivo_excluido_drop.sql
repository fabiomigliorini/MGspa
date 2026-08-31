-- =====================================================================
-- Remove a exclusao logica de dispositivo PDV (tela /config/pdv).
-- A coluna 'excluido' era redundante com 'inativo' que ja existia: os dois
-- diziam "esse dispositivo nao esta em uso". Os conceitos foram colapsados
-- em um so -- a listagem sem filtro passou a esconder os inativos, e o
-- botao Inativar da tela faz o papel do antigo Excluir.
-- Desfaz o pdv_dispositivo_excluido.sql.
--
-- ORDEM: publicar o codigo ANTES de rodar este script. O codigo anterior
-- referencia a coluna no $fillable/$casts e num whereNull('excluido');
-- derrubar a coluna antes do deploy quebra a listagem com SQLSTATE 42703.
--
-- Reaplicavel (DROP COLUMN IF EXISTS). Nao reescreve a tabela: no Postgres
-- o DROP COLUMN e metadado (marca attisdropped em pg_attribute).
-- =====================================================================

BEGIN;

ALTER TABLE tblpdv
  DROP COLUMN IF EXISTS excluido;

COMMIT;

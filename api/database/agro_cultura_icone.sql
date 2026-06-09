-- Emoji por cultura (visual na lista / cabeçalho). Opcional; cai no ícone padrão se vazio.
ALTER TABLE tblcultura ADD COLUMN icone varchar(8);

-- Sugestão de valores iniciais (ajuste os nomes conforme cadastro):
UPDATE tblcultura SET icone = '🌽' WHERE lower(cultura) LIKE '%milho%' AND icone IS NULL;
UPDATE tblcultura SET icone = '🫘' WHERE lower(cultura) LIKE '%soja%'  AND icone IS NULL;

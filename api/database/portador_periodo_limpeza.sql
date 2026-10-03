-- Periodo do portador (TASK-39 R6, processo unico do caixa): intervalo (inicio/fim),
-- fechamento (quando e quem) e a contagem inicial e final. Sai o que o modelo novo
-- nao usa: a conferencia (fechar ja' e' a conferencia; o valor e' o saldo final), o
-- ajuste automatico da abertura e do fechamento (o ajuste e' lancamento avulso) e
-- os subtotais de moedas e cedulas (saem da propria contagem). Idempotente.

do $$
begin
    if exists (select 1 from information_schema.columns
               where table_schema = 'mgsis' and table_name = 'tblportadorperiodo' and column_name = 'contagemabertura') then
        alter table mgsis.tblportadorperiodo rename column contagemabertura to contageminicial;
    end if;
    if exists (select 1 from information_schema.columns
               where table_schema = 'mgsis' and table_name = 'tblportadorperiodo' and column_name = 'contagemfechamento') then
        alter table mgsis.tblportadorperiodo rename column contagemfechamento to contagemfinal;
    end if;
end $$;

drop index if exists mgsis.idx_tblportadorperiodo_pendente;

alter table mgsis.tblportadorperiodo
    drop column if exists conferencia,
    drop column if exists codusuarioconferencia,
    drop column if exists valorconferido,
    drop column if exists codpagamentoabertura,
    drop column if exists codpagamentofechamento,
    drop column if exists moedasabertura,
    drop column if exists cedulasabertura,
    drop column if exists moedasfechamento,
    drop column if exists cedulasfechamento;

-- os periodos nao fechados de cada portador (pendencias do Fechamentos)
create index if not exists idx_tblportadorperiodo_aberto
    on mgsis.tblportadorperiodo (codportador) where fechamento is null;

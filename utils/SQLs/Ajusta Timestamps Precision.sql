
select col.table_schema,
       col.table_name,
       col.ordinal_position as column_id,
       col.column_name,
       col.data_type,
       col.datetime_precision,
       'ALTER TABLE ' || COL.TABLE_NAME || ' ALTER COLUMN ' || COL.column_name || ' TYPE timestamp(0) without time zone;'
from information_schema.columns col
join information_schema.tables tab on tab.table_schema = col.table_schema
                                   and tab.table_name = col.table_name
                                   and tab.table_type = 'BASE TABLE'
where col.data_type ILIKE 'time%STAMP%'
      and col.table_schema not in ('information_schema', 'pg_catalog')
      and col.table_name not ilike '%tbljobs%'
      --and COL.TABLE_NAME ilike 'TBLTESTE'
      and COL.datetime_precision != 0
order by col.table_schema,
         col.table_name,
         col.ordinal_position;
         
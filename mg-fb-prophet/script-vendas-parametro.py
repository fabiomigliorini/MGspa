#!/usr/bin/python3

import sys
import numpy as np
import pandas as pd
import psycopg2
from fbprophet import Prophet

codprodutovariacao = sys.argv[1]
# print(codprodutovariacao)


con = psycopg2.connect(host='192.168.2.205', database='mgsis', user='mgsis', password='mgsis')
# sql = """select mes as ds, cast(sum(quantidade) as bigint) as y
#     from tblestoquelocalprodutovariacao elpv
#     inner join tblestoquelocalprodutovariacaovenda elpvv on (elpvv.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
#     where elpv.codprodutovariacao = """ + codprodutovariacao + """
#     and mes >= \'2018-05-01\'
#     and mes <= \'2018-08-31\'
#     group by mes
#     order by 1
# """
# sql = """
#     select
#         --((date_trunc('month', tblnegocio.lancamento) + '1 month') - '1 day'::interval) as ds
#         date_trunc('day', tblnegocio.lancamento) as ds
#         , sum(tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as y
#     from tblnegocio
#     inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
#     inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
#     inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
#     left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
#     where tblnegocio.codnegociostatus = 2 --Fechado
#     and (tblnaturezaoperacao.venda = true or tblnaturezaoperacao.vendadevolucao = true)
#     and tblprodutobarra.codprodutovariacao = """ + codprodutovariacao + """
#     and tblnegocio.lancamento >= '2012-01-01'
#     group by
#          tblprodutobarra.codprodutovariacao
#         , date_trunc('day', tblnegocio.lancamento)
#         --, (date_trunc('month', tblnegocio.lancamento) + '1 month') - '1 day'::interval
#     order by 1
# """
sql = """
    with datas as (
    	select D::DATE as data
    	from generate_series(coalesce((SELECT vendainicio FROM tblprodutovariacao pv WHERE pv.codprodutovariacao = """ + codprodutovariacao + """), current_date)::date, current_date -1, '1 day') AS S(D)
    ), vendas as (
        select
            --((date_trunc('month', tblnegocio.lancamento) + '1 month') - '1 day'::interval) as ds
            date_trunc('day', tblnegocio.lancamento) as data
            , sum(tblnegocioprodutobarra.quantidade * coalesce(tblprodutoembalagem.quantidade, 1) * (case when tblnaturezaoperacao.codoperacao = 1 then -1 else 1 end)) as venda
        from tblnegocio
        inner join tblnaturezaoperacao on (tblnaturezaoperacao.codnaturezaoperacao = tblnegocio.codnaturezaoperacao)
        inner join tblnegocioprodutobarra on (tblnegocioprodutobarra.codnegocio = tblnegocio.codnegocio)
        inner join tblprodutobarra on (tblprodutobarra.codprodutobarra = tblnegocioprodutobarra.codprodutobarra)
        left join tblprodutoembalagem on (tblprodutoembalagem.codprodutoembalagem = tblprodutobarra.codprodutoembalagem)
        where tblnegocio.codnegociostatus = 2 --Fechado
        and (tblnaturezaoperacao.venda = true or tblnaturezaoperacao.vendadevolucao = true)
        and tblprodutobarra.codprodutovariacao = """ + codprodutovariacao + """
        and tblnegocio.lancamento >= '2012-01-01'
        group by
             tblprodutobarra.codprodutovariacao
            , date_trunc('day', tblnegocio.lancamento)
        order by 1
    )
    SELECT d.data as ds, v.venda as y
    FROM datas d
    LEFT JOIN vendas v ON (v.data = d.data)
"""
sales_df = pd.read_sql_query(sql, con)

sales_df['y_orig'] = sales_df['y']
sales_df['y'] = np.log(sales_df['y'])
records = len(sales_df)
# if records > 3:
#     sales_df = sales_df.drop(sales_df.index[records-1])
model = Prophet()
model.fit(sales_df)
# future_data = model.make_future_dataframe(periods=12, freq = 'm')
# future_data = model.make_future_dataframe(periods=48, freq = 'w')
future_data = model.make_future_dataframe(periods=365)
forecast_data = model.predict(future_data)
# forecast_data[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].tail(12)
forecast_data_orig = forecast_data[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].copy()
forecast_data_orig['yhat'] = np.exp(forecast_data_orig['yhat'])
forecast_data_orig['yhat_lower'] = np.exp(forecast_data_orig['yhat_lower'])
forecast_data_orig['yhat_upper'] = np.exp(forecast_data_orig['yhat_upper'])
forecast_data_orig['y_orig'] = None
# forecast_data_orig = pd.merge(forecast_data_orig, sales_df, on=['ds'], how='left')
forecast_data_orig = forecast_data_orig.where(pd.notnull(forecast_data_orig), None)

sql = """
    INSERT INTO tblprodutovariacaovenda (
        codprodutovariacao, data, previsao, previsaominima, previsaomaxima, venda
    ) VALUES (
        %s,
        --date_trunc('month', %s + '1 DAY'),
        %s,
        %s,
        %s,
        %s,
        %s
    ) ON CONFLICT (codprodutovariacao, data) DO UPDATE SET
        previsao=EXCLUDED.previsao,
        previsaominima=EXCLUDED.previsaominima,
        previsaomaxima=EXCLUDED.previsaomaxima,
        venda=EXCLUDED.venda
"""

forecast_data_orig.to_csv('saida.csv')

cur = con.cursor()
for index, row in forecast_data_orig.iterrows():
    cur.execute(sql,(codprodutovariacao, row['ds'], row['yhat'], row['yhat_lower'], row['yhat_upper'], row['y_orig']))
    con.commit()

# print(row)
# forecast_data_orig.to_sql(name='tblprodutovariacaovenda', schema='mgsis', con='con', if_exists='append', index=False)
con.close()

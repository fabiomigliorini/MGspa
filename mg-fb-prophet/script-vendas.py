from fbprophet import Prophet
import numpy as np
import pandas as pd
sales_df = pd.read_csv('vendas-fita-larga-adelbras-100m.csv')
sales_df['y_orig'] = sales_df['y']
sales_df['y'] = np.log(sales_df['y'])
model = Prophet()
model.fit(sales_df);
future_data = model.make_future_dataframe(periods=6, freq = 'm')
forecast_data = model.predict(future_data)
forecast_data[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].tail(12)
forecast_data_orig = forecast_data # make sure we save the original forecast data
forecast_data_orig['yhat'] = np.exp(forecast_data_orig['yhat'])
forecast_data_orig['yhat_lower'] = np.exp(forecast_data_orig['yhat_lower'])
forecast_data_orig['yhat_upper'] = np.exp(forecast_data_orig['yhat_upper'])
forecast_data_orig.to_csv('vendas-fita-larga-adelbras-saida-100m.csv')


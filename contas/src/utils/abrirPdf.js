import { api } from 'src/services/api'
import { abrirPdf as abrirPdfShared } from '@components/abrirPdf'

export const abrirPdf = (url, params = {}, options = {}) =>
  abrirPdfShared(api, url, params, options)

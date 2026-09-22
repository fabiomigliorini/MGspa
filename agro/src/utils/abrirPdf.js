import { api } from 'src/services/api'
import { abrirPdf as abrirPdfShared } from '@components/abrirPdf'

// Abre um PDF protegido por Bearer em modal (desktop) ou nova aba (mobile).
export const abrirPdf = (url, params = {}, options = {}) =>
  abrirPdfShared(api, url, params, options)

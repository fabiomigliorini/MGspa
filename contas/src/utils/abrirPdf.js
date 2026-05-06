import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'

/**
 * Baixa um PDF protegido por Bearer e abre o blob em nova aba.
 * Usar para rotas que estão dentro do grupo auth:api do Laravel
 * — caso contrário, window.open(url) iria redirecionar pro SSO.
 */
export async function abrirPdf(url, params = {}) {
  try {
    const { data } = await api.get(url, {
      params,
      responseType: 'blob',
      skipLoading: true,
    })
    const blobUrl = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    const win = window.open(blobUrl, '_blank')
    // libera memória depois que a aba abriu
    setTimeout(() => URL.revokeObjectURL(blobUrl), 30000)
    return win
  } catch (e) {
    notifyError(e, 'Erro ao gerar PDF')
  }
}

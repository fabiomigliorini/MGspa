import { Dialog, Notify, Platform } from 'quasar'
import MgRelatorioPdfDialog from './MgRelatorioPdfDialog.vue'

function extrairErro(error, fallback) {
  const data = error?.response?.data
  if (!data) return error?.message || fallback
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0]
    if (primeiro) return primeiro
  }
  return data.message || data.mensagem || fallback
}

/**
 * Baixa um PDF protegido por Bearer e abre num modal (desktop) ou nova aba (mobile).
 *
 * @param {Object} api - instância axios autenticada do app
 * @param {string} url - rota relativa do PDF
 * @param {Object} [params] - query params
 * @param {Object} [options]
 * @param {string} [options.title] - título exibido no header do modal
 * @param {('a4'|'cupom')} [options.size] - tamanho do modal: 'a4' (padrão) ou 'cupom' (~500px)
 * @param {Function} [options.onImprimir] - se definido, exibe botão "Imprimir" no rodapé que dispara essa função
 * @param {string} [options.imprimirLabel] - label do botão imprimir (default: 'Imprimir')
 */
export async function abrirPdf(api, url, params = {}, options = {}) {
  try {
    const { data } = await api.get(url, {
      params,
      responseType: 'blob',
      skipLoading: true,
    })
    const blobUrl = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))

    if (Platform.is.mobile) {
      window.open(blobUrl, '_blank')
      setTimeout(() => URL.revokeObjectURL(blobUrl), 30000)
      return
    }

    Dialog.create({
      component: MgRelatorioPdfDialog,
      componentProps: {
        pdfUrl: blobUrl,
        title: options.title || 'Relatório',
        size: options.size || 'a4',
        onImprimir: options.onImprimir || null,
        imprimirLabel: options.imprimirLabel || 'Imprimir',
      },
    }).onDismiss(() => {
      URL.revokeObjectURL(blobUrl)
    })
  } catch (e) {
    Notify.create({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(e, 'Erro ao gerar PDF'),
    })
  }
}

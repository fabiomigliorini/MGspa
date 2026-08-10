import { Dialog, Notify } from 'quasar'
import MgXmlDialog from './MgXmlDialog.vue'
import { extrairErro } from './abrirPdf'

// Com responseType blob o corpo de uma resposta de ERRO tambem vem como Blob, entao
// error.response.data.message fica undefined. Desembrulha antes para nao perder a
// mensagem boa do Laravel (ex: "Arquivo ja expirou (retencao de 2 anos)").
async function mensagemErro(error, fallback) {
  const data = error?.response?.data
  if (data instanceof Blob) {
    try {
      error.response.data = JSON.parse(await data.text())
    } catch {
      // nao era JSON, segue com o fallback
    }
  }
  return extrairErro(error, fallback)
}

/**
 * Baixa um XML protegido por Bearer e abre num dialog com árvore navegável.
 *
 * Aceita tanto XML puro quanto o log de conversa com a SEFAZ (que traz requisição e
 * resposta) — o dialog decide o layout.
 *
 * @param {Object} api - instância axios autenticada do app
 * @param {string} url - rota relativa do XML
 * @param {Object} [params] - query params
 * @param {Object} [options]
 * @param {string} [options.titulo] - título exibido no header do dialog
 * @param {string} [options.nomeArquivo] - nome sugerido no download
 * @param {string} [options.erroFallback] - mensagem quando a API não informa uma
 */
export async function abrirXml(api, url, params = {}, options = {}) {
  try {
    const { data } = await api.get(url, { params, responseType: 'blob', skipLoading: true })
    Dialog.create({
      component: MgXmlDialog,
      componentProps: {
        conteudo: await data.text(),
        titulo: options.titulo || 'XML',
        nomeArquivo: options.nomeArquivo || 'documento.xml',
      },
    })
  } catch (e) {
    Notify.create({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: await mensagemErro(e, options.erroFallback || 'Erro ao abrir o XML'),
    })
  }
}

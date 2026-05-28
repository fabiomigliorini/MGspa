/**
 * Baixa um arquivo protegido por Bearer e retorna uma blob URL pronta para uso
 * em iframe :src, <q-img :src>, window.open, <a :href>, etc.
 *
 * @param {Object} api - instância axios autenticada do app
 * @param {string} url - rota relativa do arquivo
 * @param {string} mimeType - mime type do blob (ex: 'application/pdf', 'application/xml', 'image/*')
 * @param {Object} [params] - query params
 * @returns {Promise<string>} blob URL — chamar URL.revokeObjectURL(...) quando não precisar mais
 */
export async function blobUrlFromApi(api, url, mimeType, params = {}) {
  const { data } = await api.get(url, {
    params,
    responseType: 'blob',
    skipLoading: true,
  })
  return URL.createObjectURL(new Blob([data], { type: mimeType }))
}

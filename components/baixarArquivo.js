/**
 * Baixa um conteúdo que já está em memória, sem nova ida ao servidor.
 *
 * @param {string} conteudo
 * @param {string} nome - nome sugerido do arquivo
 * @param {string} [mime]
 */
export function baixarArquivo(conteudo, nome, mime = 'text/plain;charset=utf-8') {
  const url = URL.createObjectURL(new Blob([conteudo], { type: mime }))
  const link = document.createElement('a')
  link.href = url
  link.download = nome
  link.click()
  URL.revokeObjectURL(url)
}

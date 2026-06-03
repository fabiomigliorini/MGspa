/**
 * Extrai mensagem amigável de um erro axios.
 * Ordem: primeiro erro de validação (422) → message → mensagem → fallback.
 */
export function extrairErro(error, fallback = 'Ocorreu um erro') {
  const data = error?.response?.data
  if (!data) return error?.message || fallback
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0]
    if (primeiro) return primeiro
  }
  return data.message || data.mensagem || fallback
}

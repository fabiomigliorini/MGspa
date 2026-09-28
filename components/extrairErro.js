// Mensagem de erro de uma resposta da API: primeiro erro de validação, senão
// a mensagem geral, senão o fallback.
export function extrairErro(error, fallback) {
  const data = error?.response?.data
  if (!data) return error?.message || fallback
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0]
    if (primeiro) return primeiro
  }
  return data.message || data.mensagem || fallback
}

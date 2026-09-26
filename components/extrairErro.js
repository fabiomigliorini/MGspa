export function extrairErro(error, fallback) {
  const data = error?.response?.data
  if (!data) return error?.message || fallback
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0]
    if (primeiro) return traduzirErroValidacao(primeiro)
  }
  const mensagem = data.message || data.mensagem
  return mensagem ? traduzirErroValidacao(mensagem) : fallback
}

function traduzirErroValidacao(mensagem) {
  if (/^the ate field must be a date after or equal to de\.?$/i.test(mensagem)) {
    return 'A data “Até” deve ser igual ou posterior à data “De”.'
  }
  return mensagem
}

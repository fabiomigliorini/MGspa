import { Notify } from 'quasar'

// Extrai a melhor mensagem de erro de uma resposta da API.
// Trata errors[campo][] (validação), message e mensagem.
export function extrairErro(error, fallback = 'Ocorreu um erro inesperado') {
  const data = error?.response?.data
  if (data?.errors) {
    const mensagens = []
    for (const campo of Object.values(data.errors)) {
      if (Array.isArray(campo)) mensagens.push(...campo)
      else mensagens.push(campo)
    }
    if (mensagens.length) return mensagens.join('\n')
  }
  return data?.message || data?.mensagem || error?.message || fallback
}

export function notificarSucesso(message) {
  Notify.create({ type: 'positive', message, icon: 'done' })
}

export function notificarErro(error, fallback = 'Ocorreu um erro inesperado') {
  Notify.create({
    type: 'negative',
    message: extrairErro(error, fallback),
    icon: 'error',
    multiLine: true,
    timeout: 5000,
  })
}

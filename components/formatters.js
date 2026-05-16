export function formataNumero(value, casas = 2) {
  return new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: casas,
    maximumFractionDigits: casas,
  }).format(value || 0)
}

export function formataData(data) {
  if (!data) return ''
  const d = new Date(data)
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function formataDataSemHora(data) {
  if (!data) return ''
  const d = new Date(data)
  return d.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

export function formataCPF(cpf) {
  if (cpf == null) return null
  const s = String(cpf).padStart(11, '0')
  return s.slice(0, 3) + '.' + s.slice(3, 6) + '.' + s.slice(6, 9) + '-' + s.slice(9, 11)
}

export function formataCNPJ(cnpj) {
  if (cnpj == null) return null
  const s = String(cnpj).padStart(14, '0')
  return (
    s.slice(0, 2) +
    '.' +
    s.slice(2, 5) +
    '.' +
    s.slice(5, 8) +
    '/' +
    s.slice(8, 12) +
    '-' +
    s.slice(12, 14)
  )
}

export const formataCnpjCpf = (cnpjcpf, fisica) => {
  if (cnpjcpf == null) return null
  return fisica ? formataCPF(cnpjcpf) : formataCNPJ(cnpjcpf)
}

export function formataTelefone(t) {
  if (!t) return ''
  return `+${t.pais} (${t.ddd}) ${t.telefone}`
}

const rtf = new Intl.RelativeTimeFormat(navigator.language || 'pt-BR', { numeric: 'auto' })

export function tempoRelativo(dataStr) {
  const diff = new Date(dataStr) - new Date()
  const seconds = Math.round(diff / 1000)
  const minutes = Math.round(diff / 60000)
  const hours = Math.round(diff / 3600000)
  const days = Math.round(diff / 86400000)
  const months = Math.round(days / 30)
  const years = Math.round(days / 365)

  if (Math.abs(seconds) < 60) return rtf.format(seconds, 'second')
  if (Math.abs(minutes) < 60) return rtf.format(minutes, 'minute')
  if (Math.abs(hours) < 24) return rtf.format(hours, 'hour')
  if (Math.abs(days) < 30) return rtf.format(days, 'day')
  if (Math.abs(months) < 12) return rtf.format(months, 'month')
  return rtf.format(years, 'year')
}

export const corProgresso = (percentual) => {
  if (!percentual) return 'grey'
  if (percentual >= 100) return 'green'
  if (percentual >= 70) return 'orange'
  return 'red'
}

export const tipoIndicadorLabel = (tipo) => {
  const map = { U: 'Unidade', S: 'Setor', V: 'Vendedor', C: 'Caixa' }
  return map[tipo] || tipo
}

export const tipoIndicadorColor = (tipo) => {
  const map = { V: 'blue', C: 'purple', U: 'orange', S: 'teal' }
  return map[tipo] || 'grey'
}

export const extrairErro = (error, fallback) => {
  const data = error.response?.data
  if (!data) return fallback
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0]
    if (primeiro) return primeiro
  }
  return data.mensagem || data.message || fallback
}

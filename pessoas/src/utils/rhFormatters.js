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

// Status do PeriodoColaborador: A=Aberto, E=Encerrado.
export const statusColaboradorLabel = (status) => (status === 'A' ? 'Aberto' : 'Encerrado')

export const statusColaboradorColor = (status) => (status === 'A' ? 'green' : 'blue')

// Atingimento em %. Aceita o campo já calculado pela API ou deriva de vendas/meta.
// Sem meta não há atingimento — devolve null para a célula virar em-dash.
export const calcAtingimento = (vendas, meta, prontinho = null) => {
  if (prontinho != null) return parseFloat(prontinho)
  const m = parseFloat(meta) || 0
  if (!m) return null
  return ((parseFloat(vendas) || 0) / m) * 100
}

// Quanto o custo representa das vendas. Sem vendas não há razão — null.
export const calcPctVendas = (total, vendas) => {
  const v = parseFloat(vendas) || 0
  if (!v) return null
  return ((parseFloat(total) || 0) / v) * 100
}

// Soma campos numéricos de uma lista: somaCampos(linhas, { salario: 'totalsalario' })
// devolve { salario: <soma de totalsalario> }. Uma passada só pela lista.
export const somaCampos = (linhas, mapa) => {
  const total = {}
  for (const alias of Object.keys(mapa)) total[alias] = 0
  for (const linha of linhas || []) {
    for (const [alias, campo] of Object.entries(mapa)) {
      total[alias] += parseFloat(linha?.[campo]) || 0
    }
  }
  return total
}

export const extrairErro = (error, fallback) => {
  const data = error.response?.data
  if (!data) return fallback
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0]
    if (primeiro) return primeiro
  }
  // Os controllers do RH respondem { erro: '...' } em 422 — sem esta linha toda
  // mensagem de regra de negócio do módulo virava o fallback genérico. O typeof
  // é porque dois endpoints fora do RH usam `erro` como flag booleana/objeto em
  // respostas de sucesso (206/200), que nunca chegam aqui mas não custam nada.
  if (typeof data.erro === 'string') return data.erro
  return data.mensagem || data.message || fallback
}

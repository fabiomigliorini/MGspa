// Cálculo de peso/desconto da carga — REPLICA o CargaService do backend pra
// mostrar o líquido na hora, offline. O backend recalcula no sync (autoridade);
// aqui é só pro operador ver o resultado na balança sem internet.
//
// Nomenclatura: pbt = caminhão+carga; tara = caminhão vazio; bruto = pbt - tara
// (grão); desconto = classificação; liquido = bruto - desconto (seco).
//
// O desconto é uma FÓRMULA EM CASCATA dirigida pela tabela de classificação:
// para cada parâmetro (em ordem) desconta sobre a base corrente; parâmetros com
// reduzbase (impureza, umidade) reduzem a base dos próximos.

function arredondar(valor, casas = 3) {
  const f = 10 ** casas
  return Math.round((Number(valor) + Number.EPSILON) * f) / f
}

function temValor(v) {
  return v !== null && v !== undefined && v !== ''
}

// Percentual (fração) de desconto de um item conforme o método do parâmetro.
// FATOR: (leitura-tol) × fator/100 (secagem, por ponto).
// NORMALIZADO: (leitura-tol)/(100-tol) × (100-desagio)/100.
function percentualItem(item, leitura) {
  const tol = Number(item.tolerancia) || 0
  const excesso = Number(leitura) - tol
  if (!(excesso > 0)) return 0
  if (item.metodo === 'FATOR') {
    return (excesso * (Number(item.fator) || 0)) / 100
  }
  const den = 100 - tol
  if (den <= 0) return 0
  return (excesso / den) * ((100 - (Number(item.desagio) || 0)) / 100)
}

// Recebe a carga + os itens RESOLVIDOS da tabela (já com metodo/reduzbase/ordem
// e tolerancia/fator/desagio) e devolve os campos calculados + o array
// `classificacao` com o desconto (kg) de cada parâmetro preenchido.
// bruto/desconto/liquido ficam null enquanto faltam os pesos (etapa inicial).
export function calcularCarga(carga, itens) {
  const classificacao = (carga.classificacao || []).map((c) => ({ ...c, desconto: null }))

  if (!temValor(carga.pbt) || !temValor(carga.tara)) {
    return { bruto: null, desconto: null, liquido: null, classificacao }
  }

  const bruto = arredondar(Number(carga.pbt) - Number(carga.tara))

  // sem itens (tabela não resolvida) => sem desconto
  const ordenados = [...(itens || [])].sort(
    (a, b) => (Number(a.ordem) || 0) - (Number(b.ordem) || 0),
  )
  const porParam = new Map(classificacao.map((c) => [c.codparametroclassificacao, c]))

  // zera o desconto das leituras presentes (as sem item ou sem leitura ficam 0)
  classificacao.forEach((c) => {
    c.desconto = 0
  })

  let base = bruto
  let total = 0
  for (const item of ordenados) {
    const cc = porParam.get(item.codparametroclassificacao)
    if (!cc || !temValor(cc.leitura)) continue
    const desc = arredondar(base * percentualItem(item, cc.leitura))
    cc.desconto = desc
    total += desc
    if (item.reduzbase) base = arredondar(base - desc)
  }

  const desconto = arredondar(total)
  const liquido = arredondar(bruto - desconto)
  return { bruto, desconto, liquido, classificacao }
}

// Converte kg -> sacas (peso da saca por cultura, default 60).
export function sacas(kg, pesosaca = 60) {
  if (!temValor(kg)) return null
  return arredondar(Number(kg) / Number(pesosaca || 60), 2)
}

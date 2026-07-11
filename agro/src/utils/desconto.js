// Cálculo de peso/desconto da carga — REPLICA o CargaService do backend pra
// mostrar o líquido na hora, offline. O backend recalcula no sync (autoridade);
// aqui é só pro operador ver o resultado na balança sem internet.
//
// Nomenclatura: pbt = caminhão+carga; tara = caminhão vazio; bruto = pbt - tara
// (grão); desconto = classificação; liquido = bruto - desconto (seco).

function arredondar(valor, casas = 3) {
  const f = 10 ** casas
  return Math.round((Number(valor) + Number.EPSILON) * f) / f
}

function temValor(v) {
  return v !== null && v !== undefined && v !== ''
}

// Desconto em kg de um tipo (UMIDADE/IMPUREZA/AVARIADOS) a partir da faixa que
// contém a leitura, sobre o BRUTO. Sem leitura => null; sem faixa => 0.
export function descontoKg(faixas, tipo, leitura, bruto) {
  if (!temValor(leitura)) return null
  const candidatas = (faixas || [])
    .filter(
      (f) =>
        f.tipo === tipo &&
        Number(f.faixainicio) <= Number(leitura) &&
        Number(f.faixafim) >= Number(leitura),
    )
    .sort((a, b) => Number(b.faixainicio) - Number(a.faixainicio))

  if (!candidatas.length) return 0
  return arredondar(bruto * (Number(candidatas[0].percentualdesconto) / 100))
}

// Recebe a carga + as faixas da cultura e devolve os campos calculados.
// bruto/desconto/liquido ficam null enquanto faltam os pesos (etapa inicial).
export function calcularCarga(carga, faixas) {
  if (!temValor(carga.pbt) || !temValor(carga.tara)) {
    return {
      bruto: null,
      descontoumidade: null,
      descontoimpureza: null,
      descontoavariados: null,
      desconto: null,
      liquido: null,
    }
  }

  const bruto = arredondar(Number(carga.pbt) - Number(carga.tara))
  const descontoumidade = descontoKg(faixas, 'UMIDADE', carga.umidade, bruto)
  const descontoimpureza = descontoKg(faixas, 'IMPUREZA', carga.impureza, bruto)
  const descontoavariados = descontoKg(faixas, 'AVARIADOS', carga.avariados, bruto)
  const desconto = arredondar(
    Number(descontoumidade || 0) + Number(descontoimpureza || 0) + Number(descontoavariados || 0),
  )
  const liquido = arredondar(bruto - desconto)

  return { bruto, descontoumidade, descontoimpureza, descontoavariados, desconto, liquido }
}

// Converte kg -> sacas (peso da saca por cultura, default 60).
export function sacas(kg, pesosaca = 60) {
  if (!temValor(kg)) return null
  return arredondar(Number(kg) / Number(pesosaca || 60), 2)
}

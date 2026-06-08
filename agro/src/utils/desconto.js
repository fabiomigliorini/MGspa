// Calculo de desconto por faixa — REPLICA o CargaColheitaService do backend
// pra mostrar o liquido seco na hora, offline. O backend recalcula no sync
// (autoridade); aqui e so pro operador ver o resultado na balanca sem internet.

function arredondar(valor, casas = 3) {
  const f = 10 ** casas
  return Math.round((Number(valor) + Number.EPSILON) * f) / f
}

// Desconto em kg de um tipo (UMIDADE/IMPUREZA/AVARIADOS) a partir da faixa
// que contem a leitura. Sem leitura => null; sem faixa => 0 (igual backend).
export function descontoKg(faixas, tipo, leitura, pesoliquido) {
  if (leitura === null || leitura === undefined || leitura === '') return null
  const candidatas = (faixas || [])
    .filter(
      (f) =>
        f.tipo === tipo &&
        Number(f.faixainicio) <= Number(leitura) &&
        Number(f.faixafim) >= Number(leitura),
    )
    .sort((a, b) => Number(b.faixainicio) - Number(a.faixainicio))

  if (!candidatas.length) return 0
  return arredondar(pesoliquido * (Number(candidatas[0].percentualdesconto) / 100))
}

// Recebe a carga + as faixas da cultura e devolve os campos calculados.
// pesoliquido/seco ficam null enquanto faltam os pesos (etapa inicial).
export function calcularCarga(carga, faixas) {
  const temPesos = carga.pesobruto !== null && carga.pesobruto !== undefined && carga.pesobruto !== '' &&
    carga.tara !== null && carga.tara !== undefined && carga.tara !== ''

  if (!temPesos) {
    return {
      pesoliquido: null,
      descontoumidade: null,
      descontoimpureza: null,
      descontoavariados: null,
      pesoliquidoseco: null,
    }
  }

  const pesoliquido = arredondar(Number(carga.pesobruto) - Number(carga.tara))
  const descontoumidade = descontoKg(faixas, 'UMIDADE', carga.umidade, pesoliquido)
  const descontoimpureza = descontoKg(faixas, 'IMPUREZA', carga.impureza, pesoliquido)
  const descontoavariados = descontoKg(faixas, 'AVARIADOS', carga.avariados, pesoliquido)
  const pesoliquidoseco = arredondar(
    pesoliquido -
      Number(descontoumidade || 0) -
      Number(descontoimpureza || 0) -
      Number(descontoavariados || 0),
  )

  return { pesoliquido, descontoumidade, descontoimpureza, descontoavariados, pesoliquidoseco }
}

// Converte kg -> sacas (peso da saca por cultura, default 60).
export function sacas(kg, pesosaca = 60) {
  if (kg === null || kg === undefined || kg === '') return null
  return arredondar(Number(kg) / Number(pesosaca || 60), 2)
}

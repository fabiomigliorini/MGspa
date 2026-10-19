// =====================================================================
// Contas com data compartilhadas MGspa, em JavaScript puro (sem biblioteca)
// Importar como: import { ... } from '@components/utils/datas'
//
// Recebem 'YYYY-MM-DD', 'YYYY-MM-DD HH:mm[:ss]' (o formato da API e do MgInputData) ou Date e
// devolvem 'YYYY-MM-DD', sempre no horário local; null quando o que chegou não é uma data.
// Mostrar data na tela é com o @components/formatters.
// =====================================================================

const pad = (n) => String(n).padStart(2, '0')

const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

// 'YYYY-MM-DD[ HH:mm[:ss]]' e Date viram Date no horário local: new Date('2026-10-18') seria
// meia-noite UTC, a noite do dia 17 em Cuiabá. Com fuso ('...Z', '-04:00') vale o new Date.
// Data que não existe (31/02) dá null
export function paraDate(data) {
  if (data === null || data === undefined || data === '') return null
  if (data instanceof Date) return isNaN(data.getTime()) ? null : new Date(data.getTime())
  const m = String(data).match(
    /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?)?$/,
  )
  if (!m) {
    const d = new Date(data)
    return isNaN(d.getTime()) ? null : d
  }
  const d = new Date(+m[1], +m[2] - 1, +m[3], +(m[4] ?? 0), +(m[5] ?? 0), +(m[6] ?? 0))
  return d.getMonth() === +m[2] - 1 && d.getDate() === +m[3] ? d : null
}

export function hoje() {
  return iso(new Date())
}

// 'YYYY-MM-DD HH:mm:ss'; sem segundos, 'YYYY-MM-DD HH:mm' (o MgInputData com :seconds="false")
export function agora(segundos = true) {
  const d = new Date()
  const hora = `${pad(d.getHours())}:${pad(d.getMinutes())}`
  return `${iso(d)} ${segundos ? `${hora}:${pad(d.getSeconds())}` : hora}`
}

// n dias depois; n negativo, antes (n pode vir como texto do formulário: '30')
export function somaDias(data, n) {
  const d = paraDate(data)
  return d ? iso(new Date(d.getFullYear(), d.getMonth(), d.getDate() + Number(n))) : null
}

// n meses depois, no mesmo dia; o dia que não existe no mês cai no último (31/01 + 1 = 28/02)
export function somaMeses(data, n) {
  const d = paraDate(data)
  if (!d) return null
  n = Number(n)
  const ultimo = new Date(d.getFullYear(), d.getMonth() + n + 1, 0).getDate()
  return iso(new Date(d.getFullYear(), d.getMonth() + n, Math.min(d.getDate(), ultimo)))
}

// n anos depois; 29/02 cai em 28/02 fora do bissexto
export function somaAnos(data, n) {
  return somaMeses(data, n * 12)
}

export function inicioMes(data) {
  const d = paraDate(data)
  return d ? iso(new Date(d.getFullYear(), d.getMonth(), 1)) : null
}

export function fimMes(data) {
  const d = paraDate(data)
  return d ? iso(new Date(d.getFullYear(), d.getMonth() + 1, 0)) : null
}

export function inicioAno(data) {
  const d = paraDate(data)
  return d ? iso(new Date(d.getFullYear(), 0, 1)) : null
}

// dias de inicio até fim, contando só o dia (a hora não entra); negativo quando fim vem antes
export function diasEntre(inicio, fim) {
  const a = paraDate(inicio)
  const b = paraDate(fim)
  if (!a || !b) return null
  const dia = (d) => Date.UTC(d.getFullYear(), d.getMonth(), d.getDate())
  return Math.round((dia(b) - dia(a)) / 86400000)
}

// meses completos de inicio até fim, ambos 'YYYY-MM-DD'
function mesesCompletos(inicio, fim) {
  const a = paraDate(inicio)
  const b = paraDate(fim)
  const meses = (b.getFullYear() - a.getFullYear()) * 12 + b.getMonth() - a.getMonth()
  return somaMeses(inicio, meses) > fim ? meses - 1 : meses
}

// anos, meses e dias completos de inicio até fim (tempo de casa); fim antes do início dá zero
export function duracao(inicio, fim) {
  const a = paraDate(inicio)
  const b = paraDate(fim)
  if (!a || !b) return null
  const ini = iso(a)
  const f = iso(b)
  if (f <= ini) return { anos: 0, meses: 0, dias: 0 }
  const anos = Math.floor(mesesCompletos(ini, f) / 12)
  const aposAnos = somaAnos(ini, anos)
  const meses = mesesCompletos(aposAnos, f)
  return { anos, meses, dias: diasEntre(somaMeses(aposAnos, meses), f) }
}

// 'DD/MM/YYYY' digitado -> 'YYYY-MM-DD'; null se não for uma data que existe (31/02, campo pela
// metade). É o valor que as :rules recebem do MgInputData
export function lerDataBr(texto) {
  const m = String(texto ?? '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/)
  if (!m) return null
  const data = `${m[3]}-${m[2]}-${m[1]}`
  return paraDate(data) ? data : null
}

// a data (ou o instante) já passou? null sem data
export function verificaPassadoFuturo(data) {
  const d = paraDate(data)
  return d ? d.getTime() < Date.now() : null
}

// idade em anos completos hoje
export function verificaIdade(dataNascimento) {
  const d = duracao(dataNascimento, hoje())
  return d ? d.anos : null
}

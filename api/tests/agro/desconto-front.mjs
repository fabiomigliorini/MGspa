#!/usr/bin/env node
// Paridade pátio × servidor nos descontos e no ticket.
//
// Roda o desconto.js e o ticket.js REAIS do pátio (agro/src/utils) com os
// vetores que `run.php desconto` gravou (servidor + regra decidida):
//
//   node api/tests/agro/desconto-front.mjs [vetores.json]
//
// O ticket montado pelo PÁTIO ainda mora dentro do CargaForm.vue e não é
// testável aqui; ele passa a entrar quando virar utils/ticket.js::ticketDoPatio
// (TASK-182). Hoje entra o ticket montado a partir do servidor (ficha/2ª via).

import { readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { calcularCarga, sacas } from '../../../agro/src/utils/desconto.js'
import { imprimirTicket, ticketDoServidor } from '../../../agro/src/utils/ticket.js'

const aqui = dirname(fileURLToPath(import.meta.url))
const arquivo = process.argv[2] || resolve(aqui, '../../storage/app/agro-bateria/vetores.json')
const vetores = JSON.parse(readFileSync(arquivo, 'utf8'))

let falhas = 0
function linha(nivel, id, titulo, detalhe = '') {
  if (nivel === 'FALHA') falhas++
  console.log(`[${nivel.padEnd(5)}] ${id.padEnd(6)} ${titulo}${detalhe ? ' — ' + detalhe : ''}`)
}
const kg = (v) => Number(v).toLocaleString('pt-BR', { maximumFractionDigits: 3 })
const nomes = (ids) => ids.slice(0, 4).join(', ') + (ids.length > 4 ? ` (+${ids.length - 4})` : '')

// imprimirTicket() abre uma janela; aqui a janela só guarda o HTML.
let html = ''
globalThis.window = {
  open: () => ({ document: { open() {}, write(h) { html = h }, close() {} } }),
}
function impresso(t) {
  html = ''
  imprimirTicket(t)
  return html
}
function numeroDaLinha(h, rotulo) {
  const m = h.match(new RegExp(`${rotulo}</td><td class="v">(?:<b>)?\\s*([\\d.,-]+)`))
  return m ? Number(m[1].replace(/\./g, '').replace(',', '.')) : null
}

console.log(`Paridade pátio × servidor — ${vetores.length} vetores de ${arquivo}\n`)

// 1) desconto.js × servidor de hoje (gramas) e × regra decidida (kg inteiro)
const difServidor = []
const difRegra = []
for (const v of vetores) {
  const itens = v.parametros.map((p) => ({ ...p, codparametroclassificacao: p.cod }))
  const r = calcularCarga({ pbt: v.pbt, tara: v.tara, classificacao: v.leituras }, itens)
  if (Math.abs(r.liquido - v.servidor.liquido) > 0.0005) difServidor.push(v.id)
  if (Math.abs(r.liquido - v.regra_kg.liquido) > 0.0005) difRegra.push(v.id)
}
linha(
  difServidor.length ? 'FALHA' : 'OK',
  'F1',
  'Pátio calcula o mesmo líquido que o servidor',
  `${vetores.length - difServidor.length} de ${vetores.length} iguais` +
    (difServidor.length ? `; diferem: ${nomes(difServidor)}` : ''),
)
linha(
  difRegra.length ? 'FALHA' : 'OK',
  'F2',
  'Pátio calcula em kg inteiro (regra de 28/09)',
  `${vetores.length - difRegra.length} de ${vetores.length} na regra`,
)

// 2) ticket impresso a partir do servidor fecha a conta: bruto − desconto = líquido
const naoFecha = []
for (const v of vetores) {
  const porCod = Object.fromEntries(v.parametros.map((p) => [p.cod, p.nome]))
  const carga = {
    sentido: 'ENTRADA',
    codcarga: 1,
    data: '2026-08-03T06:00:00',
    Safra: { safra: 'ZZTESTE', cultura: { cultura: 'Soja', pesosaca: 60 } },
    CargaPontoS: [],
    pbt: v.pbt,
    tara: v.tara,
    bruto: v.servidor.bruto,
    desconto: v.servidor.desconto,
    liquido: v.servidor.liquido,
    classificacao: v.leituras.map((l) => ({
      ...l,
      desconto: v.servidor.itens[l.codparametroclassificacao] ?? 0,
      ParametroClassificacao: { parametroclassificacao: porCod[l.codparametroclassificacao] },
    })),
  }
  const h = impresso(ticketDoServidor(carga))
  const bruto = numeroDaLinha(h, 'Bruto \\(carga\\)')
  const desconto = v.leituras.length ? numeroDaLinha(h, 'Desconto') : 0
  const liquido = numeroDaLinha(h, '<b>LÍQUIDO</b>')
  if (bruto === null || liquido === null || bruto - (desconto ?? 0) !== liquido) {
    naoFecha.push(`${v.id} (${kg(bruto)} − ${kg(desconto)} ≠ ${kg(liquido)})`)
  }
}
linha(
  naoFecha.length ? 'FALHA' : 'OK',
  'F3',
  'Ticket impresso fecha a conta (bruto − desconto = líquido)',
  `${vetores.length - naoFecha.length} de ${vetores.length} fecham` +
    (naoFecha.length ? `; não fecham: ${nomes(naoFecha)}` : ''),
)

// 3) sacas: o pátio arredonda a 2 casas antes de mostrar 1; a ficha/ticket não
const fmt1 = (n) => Number(n).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
const difSacas = []
for (const liq of [602.97, ...vetores.map((v) => v.servidor.liquido)]) {
  const patio = fmt1(sacas(liq, 60))
  const ficha = fmt1(liq / 60)
  if (patio !== ficha) difSacas.push(`${kg(liq)} kg: pátio ${patio}, ficha ${ficha}`)
}
linha(
  difSacas.length ? 'FALHA' : 'OK',
  'F4',
  'Sacas iguais no pátio e na ficha/ticket',
  difSacas.length ? `${difSacas.length} diferem; ex.: ${nomes(difSacas)}` : 'uma regra só',
)
linha('INFO', 'F5', 'Ticket montado pelo pátio', 'mora no CargaForm.vue; entra aqui quando virar ticketDoPatio (TASK-182)')

console.log(`\n${falhas ? `${falhas} FALHA` : 'sem falhas'}`)
process.exit(falhas ? 1 : 0)

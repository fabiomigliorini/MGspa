// Pagamento e parcela do negócio no formato novo (M5 do plano doc-3): meio do pagamento
// (código tPag da NF-e) e condição da parcela. Apresentação no drawer de totais e nos dialogs.
import { formataData, formataNumero } from '@components/formatters'
import cartoesManuais from './cartoes-manuais.json'
import { logo } from './logos.js'

export const MEIO = {
  DINHEIRO: 1,
  CHEQUE: 2,
  CREDITO: 3,
  DEBITO: 4,
  VALE: 12,
  BOLETO: 15,
  DEPOSITO: 16,
  PIX: 17,
  TRANSFERENCIA: 18,
  COMPENSACAO: 91,
  OUTROS: 99,
}

export const MEIOS = {
  1: 'Dinheiro',
  2: 'Cheque',
  3: 'Cartão Crédito',
  4: 'Cartão Débito',
  12: 'Vale Compras',
  15: 'Boleto',
  16: 'Depósito',
  17: 'PIX',
  18: 'Transferência',
  91: 'Compensação',
  92: 'Folha',
  93: 'Permuta',
  94: 'Perda',
  99: 'Outros',
}

// mesmas letras do NegocioParcelaService
export const CONDICAO = {
  FECHAMENTO: 'F',
  PARCELADO: 'P',
  BOLETO: 'B',
  ENTREGA: 'E',
  PIX: 'X',
  VALE: 'V',
}

export const CONDICOES = {
  F: 'Fechamento Mensal',
  P: 'Crediário',
  B: 'Boleto',
  E: 'Pagamento na Entrega',
  X: 'PIX / Depósito a Receber',
  V: 'Vale da Devolução',
}

// visual de cada forma: o MESMO no passo do wizard Receber e na linha da listagem,
// pra o operador reconhecer o pagamento pela imagem
export const VISUAL = {
  cartao: { icone: 'credit_card', cor: 'indigo-6' },
  debito: { icone: 'mdi-credit-card-fast-outline', cor: 'indigo-6' },
  credito: { icone: 'mdi-credit-card-clock-outline', cor: 'indigo-6' },
  voucher: { icone: 'mdi-silverware-fork-knife', cor: 'indigo-6' },
  pix: { icone: 'pix', cor: 'teal-6' },
  dinheiro: { icone: 'local_atm', cor: 'green-7' },
  entrega: { icone: 'delivery_dining', cor: 'orange-8' },
  prazo: { icone: 'receipt', cor: 'blue-grey-6' },
  fechamento: { icone: 'calendar_month', cor: 'blue-grey-6' },
  boleto: { icone: 'mdi-barcode', cor: 'blue-grey-6' },
  crediario: { icone: 'wallet', cor: 'blue-grey-6' },
  vale: { icone: 'mdi-ticket', cor: 'purple-6' },
  cheque: { icone: 'mdi-checkbook', cor: 'brown-6' },
  banco: { icone: 'account_balance', cor: 'blue-8' },
  estorno: { icone: 'undo', cor: 'deep-orange-7' },
  compensacao: { icone: 'sync_alt', cor: 'grey-7' },
}

const POR_MEIO = {
  1: 'dinheiro',
  2: 'cheque',
  3: 'credito',
  4: 'debito',
  12: 'vale',
  15: 'boleto',
  16: 'banco',
  17: 'pix',
  18: 'banco',
  91: 'compensacao',
  99: 'cartao',
}

const POR_CONDICAO = {
  F: 'fechamento',
  P: 'crediario',
  B: 'boleto',
  E: 'entrega',
  X: 'pix',
  V: 'vale',
}

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

// total = principal + juros + multa − desconto (o troco fica fora)
export const totalPagamento = (pag) =>
  arredonda((pag.principal || 0) + (pag.juros || 0) + (pag.multa || 0) - (pag.desconto || 0))

// o que aparece na linha: no dinheiro, o que o cliente entregou (o troco aparece no saldo)
export const valorExibido = (pag) => arredonda((pag.total || 0) + (pag.valortroco || 0))

// logo da bandeira (public/bandeiras) quando for cartão com bandeira conhecida;
// parceiro de bandeira única (Brasil Card, Le Card…) grava 99=Outros, então mostra o logo dele
const logoBandeira = (pag) => {
  if (!pag.bandeira) {
    return null
  }
  const parceiro = cartoesManuais.find((p) => p.codpessoa == pag.codpessoa)
  if (pag.bandeira == 99 && parceiro?.bandeiras.length === 1) {
    return logo(parceiro.logo)
  }
  return logo(`/bandeiras/${pag.bandeira}.svg`)
}

// { logo, icone, cor } para o LogoPagamento; logo do banco só existe p/ alguns (senão cai no ícone)
export const visualPagamento = (pag) => {
  const visual = VISUAL[POR_MEIO[pag.meio]] ?? VISUAL.cartao
  const imagem = logoBandeira(pag) ?? (pag.codbanco ? logo(`/bancos/${pag.codbanco}.svg`) : null)
  return { ...visual, logo: imagem }
}

export const visualCondicao = (condicao) => VISUAL[POR_CONDICAO[condicao]] ?? VISUAL.prazo

export const tituloPagamento = (pag) => {
  if (pag.codpixcob) {
    return 'PIX QR Code'
  }
  return MEIOS[pag.meio] ?? 'Pagamento'
}

// uma linha só: parceiro · parcelas · cheque
export const resumoPagamento = (pag) => {
  const partes = []
  if (pag.parceiro) {
    partes.push(pag.parceiro)
  }
  if (pag.parcelas > 1) {
    partes.push(`${pag.parcelas}x ${formataNumero(totalPagamento(pag) / pag.parcelas)}`)
  }
  if (pag.cmc7) {
    partes.push(`${pag.chequeemitente} · bom p/ ${formataData(pag.chequevencimento)}`)
  }
  if (pag.desconto > 0) {
    partes.push(`desconto ${formataNumero(pag.desconto)}`)
  }
  return partes.join(' · ')
}

// parcelas agrupadas por condição (uma linha no drawer por condição)
export const gruposParcelas = (parcelas) => {
  const grupos = {}
  for (const p of parcelas ?? []) {
    if (!grupos[p.condicao]) {
      grupos[p.condicao] = { condicao: p.condicao, parcelas: [], valor: 0 }
    }
    grupos[p.condicao].parcelas.push(p)
    grupos[p.condicao].valor = arredonda(grupos[p.condicao].valor + parseFloat(p.valor))
  }
  return Object.values(grupos).map((g) => ({
    ...g,
    parcelas: [...g.parcelas].sort((a, b) => String(a.vencimento).localeCompare(b.vencimento)),
  }))
}

export const resumoParcelas = (grupo) => {
  const ps = grupo.parcelas
  if (ps.length === 1) {
    return 'vence ' + formataData(ps[0].vencimento)
  }
  const ddmm = (v) => String(v).substr(8, 2) + '/' + String(v).substr(5, 2)
  return `${ps.length}x · ` + ps.map((p) => ddmm(p.vencimento)).join(' · ')
}

// ---- vencimento sugerido pela condição (mesma regra do NegocioParcelaService) ----

const iso = (d) =>
  [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0'),
  ].join('-')

// data local (sem hora) de 'YYYY-MM-DD', Date ou hoje
const dia = (base) => {
  if (!base) {
    const hoje = new Date()
    return new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate())
  }
  if (base instanceof Date) {
    return new Date(base.getFullYear(), base.getMonth(), base.getDate())
  }
  const [a, m, d] = String(base).substr(0, 10).split('-').map(Number)
  return new Date(a, m - 1, d)
}

// mesmo dia `meses` depois; dia que não existe no mês cai no último (31/01 + 1 = 28/02)
const somaMeses = (d, meses) => {
  const ultimo = new Date(d.getFullYear(), d.getMonth() + meses + 1, 0).getDate()
  return new Date(d.getFullYear(), d.getMonth() + meses, Math.min(d.getDate(), ultimo))
}

// último dia útil do mês (segunda a sábado, sem feriado), como o RH conta
export const ultimoDiaUtil = (mes, feriados = []) => {
  const m = dia(mes)
  const d = new Date(m.getFullYear(), m.getMonth() + 1, 0)
  while (d.getDay() === 0 || feriados.includes(iso(d))) {
    d.setDate(d.getDate() - 1)
  }
  return iso(d)
}

// parcela `numero` (1, 2…); dias = 30 conta meses; sem dias vence no dia
export const vencimentoSugerido = (condicao, numero, dias, feriados = [], base = null) => {
  const b = dia(base)
  if (condicao === CONDICAO.FECHAMENTO) {
    return ultimoDiaUtil(new Date(b.getFullYear(), b.getMonth() + numero, 1), feriados)
  }
  if (dias == 30) {
    return iso(somaMeses(b, numero))
  }
  return iso(new Date(b.getFullYear(), b.getMonth(), b.getDate() + (dias ?? 0) * numero))
}

// orçamento impresso: uma linha por pagamento e uma por condição a prazo
export const formasOrcamento = (negocio) => [
  ...(negocio?.pagamentos ?? []).map((pag) => ({
    chave: pag.uuid,
    valor: valorExibido(pag),
    descricao: tituloPagamento(pag) + (pag.parcelas > 1 ? ` ${pag.parcelas}x` : ''),
  })),
  ...gruposParcelas(negocio?.parcelas).map((g) => ({
    chave: 'parcelas' + g.condicao,
    valor: g.valor,
    descricao: `${CONDICOES[g.condicao]} (${resumoParcelas(g)})`,
  })),
]

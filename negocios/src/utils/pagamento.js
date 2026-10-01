// Pagamento e parcela do negócio no formato novo (M5 do plano doc-3): meio do pagamento
// (código tPag da NF-e) e condição da parcela. Apresentação no drawer de totais e nos dialogs.
import moment from 'moment'
import { formataData, formataNumero } from '@components/formatters'
import cartoesManuais from '../data/cartoes-manuais.json'

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
}

const POR_MEIO = {
  1: 'dinheiro',
  2: 'cheque',
  3: 'credito',
  4: 'debito',
  12: 'vale',
  15: 'boleto',
  16: 'pix',
  17: 'pix',
  18: 'pix',
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
    return parceiro.logo
  }
  return `/bandeiras/${pag.bandeira}.svg`
}

// { logo, icone, cor } para o LogoPagamento; logo do banco só existe p/ alguns (senão cai no ícone)
export const visualPagamento = (pag) => {
  const visual = VISUAL[POR_MEIO[pag.meio]] ?? VISUAL.cartao
  const logo = logoBandeira(pag) ?? (pag.codbanco ? `/bancos/${pag.codbanco}.svg` : null)
  return { ...visual, logo }
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
  return `${ps.length}x · ` + ps.map((p) => moment(p.vencimento).format('DD/MM')).join(' · ')
}

// ---- vencimento sugerido pela condição (mesma regra do NegocioParcelaService) ----

// último dia útil do mês (segunda a sábado, sem feriado), como o RH conta
export const ultimoDiaUtil = (mes, feriados = []) => {
  const dia = moment(mes).endOf('month').startOf('day')
  while (dia.day() === 0 || feriados.includes(dia.format('YYYY-MM-DD'))) {
    dia.subtract(1, 'day')
  }
  return dia
}

// parcela `numero` (1, 2…); dias = 30 conta meses; sem dias vence no dia
export const vencimentoSugerido = (condicao, numero, dias, feriados = [], base = null) => {
  const b = moment(base ?? undefined).startOf('day')
  if (condicao === CONDICAO.FECHAMENTO) {
    return ultimoDiaUtil(b.clone().startOf('month').add(numero, 'months'), feriados).format(
      'YYYY-MM-DD',
    )
  }
  if (dias == 30) {
    return b.add(numero, 'months').format('YYYY-MM-DD')
  }
  return b.add((dias ?? 0) * numero, 'days').format('YYYY-MM-DD')
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

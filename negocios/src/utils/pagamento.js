// Apresentação de um pagamento do negócio (drawer de totais e dialog de detalhe)
import { formataData, formataNumero } from '@components/formatters'

// tPag da NFe → ícone
const ICONES = {
  1: 'local_atm',
  2: 'mdi-checkbook',
  3: 'credit_card',
  4: 'credit_card',
  5: 'receipt',
  10: 'mdi-ticket',
  11: 'mdi-ticket',
  12: 'mdi-ticket',
  13: 'mdi-ticket',
  15: 'receipt',
  16: 'account_balance',
  17: 'pix',
  18: 'account_balance',
}

export const iconePagamento = (pag) => ICONES[pag.tipo] ?? 'payments'

// logo da bandeira (public/bandeiras) quando for cartão com bandeira conhecida
export const logoBandeira = (pag) => (pag.bandeira ? `/bandeiras/${pag.bandeira}.svg` : null)

// cartão: "Cartão Crédito" diz mais que a forma "Cartao"; prazo: a forma já traz as condições
export const tituloPagamento = (pag) =>
  [3, 4, 17].includes(pag.tipo) && pag.nometipo ? pag.nometipo : pag.formapagamento

// uma linha só: parceiro · parcelas (ou dias) · cheque
export const resumoPagamento = (pag) => {
  const partes = []
  if (pag.parceiro) {
    partes.push(pag.parceiro)
  }
  if (pag.parcelas > 1) {
    partes.push(`${pag.parcelas}x ${formataNumero(pag.valorparcela)}`)
  } else if (pag.dias && pag.dias != 30) {
    partes.push(`${pag.dias} dias`)
  }
  if (pag.cmc7) {
    partes.push(`${pag.chequeemitente} · bom p/ ${formataData(pag.chequevencimento)}`)
  }
  return partes.join(' · ')
}

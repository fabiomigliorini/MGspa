// Apresentação de um pagamento do negócio (drawer de totais e dialog de detalhe)
import { formataData, formataNumero } from '@components/formatters'
import cartoesManuais from '../data/cartoes-manuais.json'

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

// forma de pagamento do negócio → visual; o que não estiver aqui cai no tPag
const POR_FORMA = {
  [process.env.CODFORMAPAGAMENTO_DINHEIRO]: 'dinheiro',
  [process.env.CODFORMAPAGAMENTO_ENTREGA]: 'entrega',
  [process.env.CODFORMAPAGAMENTO_PIXCHAVE]: 'pix',
  [process.env.CODFORMAPAGAMENTO_FECHAMENTO]: 'fechamento',
  [process.env.CODFORMAPAGAMENTO_BOLETO]: 'boleto',
  [process.env.CODFORMAPAGAMENTO_CARTEIRA]: 'crediario',
  [process.env.CODFORMAPAGAMENTO_VALE]: 'vale',
  [process.env.CODFORMAPAGAMENTO_CHEQUE ?? 1020]: 'cheque',
}

// tPag da NFe → visual
const POR_TIPO = {
  1: 'dinheiro',
  2: 'cheque',
  3: 'credito',
  4: 'debito',
  5: 'prazo',
  10: 'vale',
  11: 'vale',
  12: 'vale',
  13: 'vale',
  15: 'boleto',
  16: 'pix',
  17: 'pix',
  18: 'pix',
}

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
  const visual = VISUAL[POR_FORMA[pag.codformapagamento] ?? POR_TIPO[pag.tipo]] ?? {}
  const logo = logoBandeira(pag) ?? (pag.codbanco ? `/bancos/${pag.codbanco}.svg` : null)
  return { ...visual, logo }
}

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

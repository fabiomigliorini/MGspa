/**
 * Constantes da nota fiscal de servico (NFS-e Nacional, TASK-279).
 * Os status usam os mesmos codigos, cores e icones da NF-e (@components/notaFiscalStatus),
 * mas a NFS-e so tem quatro: nao existe inutilizacao, denegacao nem nota lancada.
 */
import { NOTA_FISCAL_STATUS_OPTIONS } from '@components/notaFiscalStatus'

export const NFSE_STATUS_OPTIONS = NOTA_FISCAL_STATUS_OPTIONS.filter((s) =>
  ['DIG', 'ERR', 'AUT', 'CAN'].includes(s.value),
)

export {
  getNotaFiscalStatusLabel as getNfseStatusLabel,
  getNotaFiscalStatusColor as getNfseStatusColor,
  getNotaFiscalStatusIcon as getNfseStatusIcon,
} from '@components/notaFiscalStatus'

export const ISS_RETIDO_OPTIONS = [
  { label: 'Não retido', value: false },
  { label: 'Retido pelo tomador', value: true },
]

// Codigo de tributacao nacional: 170202 -> 17.02.02
export const formataCodigoTributacaoNacional = (codigo) => {
  const s = String(codigo || '')
  if (s.length !== 6) return s
  return `${s.substring(0, 2)}.${s.substring(2, 4)}.${s.substring(4, 6)}`
}

// NBS: 101011100 -> 1.0101.11.00
export const formataNbs = (codigo) => {
  const s = String(codigo || '')
  if (s.length !== 9) return s
  return `${s.substring(0, 1)}.${s.substring(1, 5)}.${s.substring(5, 7)}.${s.substring(7, 9)}`
}

// Base de calculo, ISS e liquido: espelham o calculo do backend (que devolve os tres
// prontos no detalhe). Aqui so para exibir enquanto o formulario e preenchido.
export const calculaValoresNfse = ({
  valorservico,
  valordescontoincondicionado,
  valordescontocondicionado,
  issaliquota,
  issretido,
}) => {
  const servico = Number(valorservico) || 0
  const incondicionado = Number(valordescontoincondicionado) || 0
  const condicionado = Number(valordescontocondicionado) || 0
  // Simples (ISS no DAS): a nota so leva aliquota e valor do ISS quando ele e retido
  const aliquota = issretido ? Number(issaliquota) || 0 : 0
  const basecalculo = Math.round((servico - incondicionado) * 100) / 100
  const issvalor = issretido ? Math.round(basecalculo * aliquota) / 100 : null
  // Como o Sistema Nacional calcula o vLiq: o ISS retido nao sai do liquido
  const valorliquido = Math.round((servico - incondicionado - condicionado) * 100) / 100
  return { basecalculo, issvalor, valorliquido }
}

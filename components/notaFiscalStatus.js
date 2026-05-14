/**
 * Status da Nota Fiscal (compartilhado entre apps).
 * Valores espelham os constants de NotaFiscalStatusService.php no backend.
 */

export const NOTA_FISCAL_STATUS_OPTIONS = [
  { label: 'Lançada', value: 'LAN', icon: 'description', color: 'blue-grey' },
  { label: 'Em Digitação', value: 'DIG', icon: 'edit_note', color: 'blue' },
  { label: 'Não Autorizada', value: 'ERR', icon: 'error', color: 'deep-orange' },
  { label: 'Autorizada', value: 'AUT', icon: 'check_circle', color: 'secondary' },
  { label: 'Cancelada', value: 'CAN', icon: 'highlight_off', color: 'negative' },
  { label: 'Inutilizada', value: 'INU', icon: 'block', color: 'warning' },
  { label: 'Denegada', value: 'DEN', icon: 'report', color: 'negative' },
]

const findOption = (status) => NOTA_FISCAL_STATUS_OPTIONS.find((opt) => opt.value === status)

export const getNotaFiscalStatusLabel = (status) => findOption(status)?.label ?? status
export const getNotaFiscalStatusColor = (status) => findOption(status)?.color ?? 'grey'
export const getNotaFiscalStatusIcon = (status) => findOption(status)?.icon ?? 'help'

export const isNotaFiscalCanceladaInutilizada = (status) => ['CAN', 'INU', 'DEN'].includes(status)
